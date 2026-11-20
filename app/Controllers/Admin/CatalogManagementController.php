<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\Admin\CatalogManagementModel;

class CatalogManagementController extends BaseController
{
    private CatalogManagementModel $catalog;

    private array $titles = [
        'categories' => 'Categories',
        'subcategories' => 'Subcategories',
        'product-types' => 'Product Types',
        'attributes' => 'Category Attributes',
    ];

    public function initController(
        \CodeIgniter\HTTP\RequestInterface $request,
        \CodeIgniter\HTTP\ResponseInterface $response,
        \Psr\Log\LoggerInterface $logger
    ) {
        parent::initController($request, $response, $logger);
        $this->catalog = new CatalogManagementModel();
    }

    private function validType(string $type): bool
    {
        return isset($this->titles[$type]);
    }

    public function index(string $type = 'categories')
    {
        if (!$this->validType($type)) return redirect()->to(site_url('shipping-admin/catalog/categories'));

        $search = trim((string) $this->request->getGet('search'));
        return view('shipping_admin/catalog/index', [
            'pageTitle' => $this->titles[$type],
            'type' => $type,
            'search' => $search,
            'rows' => $this->catalog->rows($type, $search),
        ]);
    }

    public function add(string $type = 'categories')
    {
        if (!$this->validType($type)) return redirect()->to(site_url('shipping-admin/catalog/categories'));

        return view('shipping_admin/catalog/form', $this->formData($type, [], 'Add ' . rtrim($this->titles[$type], 's')));
    }

    public function edit(string $type, int $id)
    {
        if (!$this->validType($type)) return redirect()->to(site_url('shipping-admin/catalog/categories'));
        $row = $this->catalog->findRow($type, $id);
        if (!$row) return redirect()->to(site_url('shipping-admin/catalog/' . $type))->with('error', 'Record not found.');

        return view('shipping_admin/catalog/form', $this->formData($type, $row, 'Edit ' . rtrim($this->titles[$type], 's')));
    }

    private function formData(string $type, array $row, string $title): array
    {
        return [
            'pageTitle' => $title,
            'type' => $type,
            'row' => $row,
            'categories' => $this->catalog->categories(true),
            'subcategories' => $this->catalog->subcategories(null, true),
            'producttype' => $this->catalog->producttype(null, true),
            'action' => site_url('shipping-admin/catalog/' . $type . '/save' . (!empty($row['id']) ? '/' . $row['id'] : '')),
        ];
    }

    public function save(string $type, ?int $id = null)
    {
        if (!$this->validType($type)) return redirect()->to(site_url('shipping-admin/catalog/categories'));

        $existing = $id ? $this->catalog->findRow($type, $id) : null;
        if ($id && !$existing) return redirect()->to(site_url('shipping-admin/catalog/' . $type))->with('error', 'Record not found.');

        $name = $type === 'attributes'
            ? trim((string) $this->request->getPost('label'))
            : trim((string) $this->request->getPost('name'));
        $description = trim((string) $this->request->getPost('description'));
        $sort = (int) $this->request->getPost('sort_order');
        $errors = [];

        if ($name === '') $errors[] = 'Name is required.';
        if (mb_strlen($name) > ($type === 'categories' || $type === 'attributes' ? 100 : 150)) $errors[] = 'Name is too long.';

        $data = ['name' => $name, 'sort_order' => $sort];

        if ($type === 'categories') {
            $status = (string) $this->request->getPost('status');
            if (!in_array($status, ['active', 'inactive'], true)) $errors[] = 'Invalid status.';
            $data['status'] = $status;
        } elseif ($type === 'subcategories') {
            $categoryId = (int) $this->request->getPost('category_id');
            $category = $this->catalog->findRow('categories', $categoryId);
            if (!$category || ($category['status'] ?? '') !== 'active') $errors[] = 'Please select an active category.';
            $data += ['category_id' => $categoryId, 'description' => $description ?: null];
            $status = (int) $this->request->getPost('status');
            $data['status'] = $status === 1 ? 1 : 0;
        } elseif ($type === 'product-types') {
            $subcategoryId = (int) $this->request->getPost('subcategory_id');
            $subcategory = $this->catalog->findRow('subcategories', $subcategoryId);
            if (!$subcategory || (int)($subcategory['status'] ?? 0) !== 1) $errors[] = 'Please select an active subcategory.';
            elseif (($parentCategory = $this->catalog->findRow('categories', (int)$subcategory['category_id'])) === null || ($parentCategory['status'] ?? '') !== 'active') $errors[] = 'The selected subcategory belongs to an inactive category.';
            $data += ['subcategory_id' => $subcategoryId, 'description' => $description ?: null];
            $status = (int) $this->request->getPost('status');
            $data['status'] = $status === 1 ? 1 : 0;
        
        } else {

            $categoryId = (int) $this->request->getPost('category_id');

            $subcategoryRaw = $this->request->getPost('subcategory_id');

            $subcategoryId = ($subcategoryRaw === '' || $subcategoryRaw === null)
                ? null
                : (int) $subcategoryRaw;

            $productTypeRaw = $this->request->getPost('product_type_id');

            $productTypeId = ($productTypeRaw === '' || $productTypeRaw === null)
                ? null
                : (int) $productTypeRaw;

            // Validate category
            $category = $this->catalog->findRow('categories', $categoryId);

            if (!$category || ($category['status'] ?? '') !== 'active') {
                $errors[] = 'Please select an active category.';
            }

            // Validate subcategory
            if ($subcategoryId) {

                $sub = $this->catalog->findRow('subcategories', $subcategoryId);

                if (!$sub || (int)($sub['status'] ?? 0) !== 1 || (int) $sub['category_id'] !== $categoryId) {
                    $errors[] = 'Selected subcategory does not belong to this category.';
                }
            }

            // Validate product type
            $productType = null;

            if (!$productTypeId) {

                $errors[] = 'Please select a product type.';

            } else {

                $productType = $this->catalog->findRow(
                    'product-types',
                    $productTypeId
                );

                if (!$productType || (int)($productType['status'] ?? 0) !== 1) {

                    $errors[] = 'Please select an active product type.';

                } else {

                    // Product type must belong to the selected subcategory
                    $productTypeSubcategoryId = (int) $productType['subcategory_id'];

                    $productSubcategory = $this->catalog->findRow(
                        'subcategories',
                        $productTypeSubcategoryId
                    );

                    if (!$productSubcategory) {

                        $errors[] = 'Invalid product type subcategory.';

                    } else {

                        // Automatically use product type's subcategory
                        if ($subcategoryId === null) {

                            $subcategoryId = $productTypeSubcategoryId;

                        } elseif ($subcategoryId !== $productTypeSubcategoryId) {

                            $errors[] = 'Selected product type does not belong to the selected subcategory.';

                        }

                        // Verify category relationship
                        if ((int) $productSubcategory['category_id'] !== $categoryId) {

                            $errors[] = 'Selected product type does not belong to the selected category.';

                        }
                    }
                }
            }

            // Attribute information
            $attributeName = strtolower(
                trim((string) $this->request->getPost('attribute_name'))
            );

            $attributeName = preg_replace('/[^a-z0-9_]+/', '_', $attributeName);
            $attributeName = trim($attributeName, '_');

            $label = trim((string) $this->request->getPost('label'));

            $inputType = (string) $this->request->getPost('input_type');

            $allowedTypes = [
                'text',
                'number',
                'textarea',
                'select',
                'date',
                'checkbox'
            ];

            if ($attributeName === '') {
                $errors[] = 'Attribute key is required.';
            }

            if ($label === '') {
                $errors[] = 'Attribute label is required.';
            }

            if (!in_array($inputType, $allowedTypes, true)) {
                $errors[] = 'Invalid input type.';
            }

            $options = trim((string) $this->request->getPost('options'));

            $optionsJson = null;

            if ($inputType === 'select' || $inputType === 'checkbox') {

                $values = array_values(
                    array_filter(
                        array_map('trim', preg_split('/\r\n|\r|\n/', $options))
                    )
                );

                if (!$values) {
                    $errors[] = 'Enter at least one option, one per line.';
                }

                $optionsJson = json_encode($values, JSON_UNESCAPED_UNICODE);
            }

            $data = [
                'category_id'    => $categoryId,
                'subcategory_id' => $subcategoryId,
                'product_type_id' => $productTypeId,

                'attribute_name' => $attributeName,
                'label'          => $label,
                'input_type'     => $inputType,
                'is_required'    => $this->request->getPost('is_required') ? 1 : 0,
                'options_json'   => $optionsJson,

                'status' => $this->request->getPost('status') === 'inactive'
                    ? 'inactive'
                    : 'active',

                'sort_order' => $sort,
            ];
        }


        $parentId = (int) ($data['category_id'] ?? $data['subcategory_id'] ?? 0);
        $slug = $this->slugify($name);
        if ($type !== 'attributes') {
            if ($slug === '') $errors[] = 'Could not generate a valid slug.';
            if ($this->catalog->slugExists($type, $slug, $parentId, $id)) $errors[] = 'This name already exists under the selected parent.';
            $data['slug'] = $slug;
        }

        if ($errors) {
            return redirect()->back()->withInput()->with('errors', $errors);
        }

        if (!$this->catalog->saveRow($type, $data, $id)) {
            return redirect()->back()->withInput()->with('error', 'Unable to save this record. Check duplicate values and database constraints.');
        }

        return redirect()->to(site_url('shipping-admin/catalog/' . $type))
            ->with('success', $id ? 'Record updated successfully.' : 'Record added successfully.');
    }

    public function bulk(string $type)
    {
        if (!$this->validType($type)) return redirect()->to(site_url('shipping-admin/catalog/categories'));
        return view('shipping_admin/catalog/bulk', [
            'type' => $type,
            'pageTitle' => 'Bulk Add ' . $this->titles[$type],
            'categories' => $this->catalog->categories(true),
        ]);
    }

    public function bulkSave(string $type)
    {
        if (!$this->validType($type)) return redirect()->to(site_url('shipping-admin/catalog/categories'));
        $db = db_connect();
        $errors = [];
        $rows = $this->request->getPost('rows');
        if (!is_array($rows)) $rows = [];
        $rows = array_values(array_filter($rows, static function ($r) {
            return is_array($r) && trim((string)($r['name'] ?? $r['label'] ?? '')) !== '';
        }));
        if (!$rows) $errors[] = 'Please enter at least one record.';

        $categoryId = (int)$this->request->getPost('category_id');
        $subcategoryId = (int)$this->request->getPost('subcategory_id');
        $productTypeId = (int)$this->request->getPost('product_type_id');
        if ($type === 'subcategories') {
            $cat = $this->catalog->findRow('categories', $categoryId);
            if (!$cat || ($cat['status'] ?? '') !== 'active') $errors[] = 'Select an active category.';
        } elseif ($type === 'product-types') {
            $sub = $this->catalog->findRow('subcategories', $subcategoryId);
            $cat = $sub ? $this->catalog->findRow('categories', (int)$sub['category_id']) : null;
            if (!$sub || (int)($sub['status'] ?? 0) !== 1 || !$cat || ($cat['status'] ?? '') !== 'active') $errors[] = 'Select a subcategory under an active category.';
        } elseif ($type === 'attributes') {
            $pt = $this->catalog->findRow('product-types', $productTypeId);
            $sub = $pt ? $this->catalog->findRow('subcategories', (int)$pt['subcategory_id']) : null;
            $cat = $sub ? $this->catalog->findRow('categories', (int)$sub['category_id']) : null;
            if (!$pt || (int)($pt['status'] ?? 0) !== 1 || !$sub || (int)($sub['status'] ?? 0) !== 1 || !$cat || ($cat['status'] ?? '') !== 'active') $errors[] = 'Select a product type under an active subcategory and category.';
        }

        $prepared = [];
        foreach ($rows as $i => $row) {
            $sort = (int)($row['sort_order'] ?? 0);
            if ($type === 'attributes') {
                $label = trim((string)($row['label'] ?? ''));
                $key = strtolower(trim((string)($row['attribute_name'] ?? $label)));
                $key = trim((string)preg_replace('/[^a-z0-9_]+/', '_', $key), '_');
                $inputType = (string)($row['input_type'] ?? 'text');
                $allowed = ['text','number','textarea','select','date','checkbox'];
                if ($label === '' || $key === '') $errors[] = 'Attribute row ' . ($i + 1) . ': label and key are required.';
                if (!in_array($inputType, $allowed, true)) $errors[] = 'Attribute row ' . ($i + 1) . ': invalid input type.';
                $optionsJson = null;
                if (in_array($inputType, ['select','checkbox'], true)) {
                    $opts = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string)($row['options'] ?? '')))));
                    if (!$opts) $errors[] = 'Attribute row ' . ($i + 1) . ': enter options on separate lines.';
                    $optionsJson = json_encode($opts, JSON_UNESCAPED_UNICODE);
                }
                $prepared[] = [
                    'category_id' => (int)($this->catalog->findRow('subcategories', (int)($pt['subcategory_id'] ?? 0))['category_id'] ?? 0),
                    'subcategory_id' => $subcategoryId ?: (int)($pt['subcategory_id'] ?? 0),
                    'product_type_id' => $productTypeId,
                    'attribute_name' => $key, 'label' => $label, 'input_type' => $inputType,
                    'is_required' => !empty($row['is_required']) ? 1 : 0,
                    'options_json' => $optionsJson,
                    'status' => (($row['status'] ?? 'active') === 'inactive') ? 'inactive' : 'active',
                    'sort_order' => $sort,
                ];
            } else {
                $name = trim((string)($row['name'] ?? ''));
                if ($name === '') $errors[] = 'Row ' . ($i + 1) . ': name is required.';
                $slug = $this->slugify($name);
                $data = ['name' => $name, 'slug' => $slug, 'sort_order' => $sort];
                if ($type === 'categories') {
                    $data['status'] = (($row['status'] ?? 'active') === 'inactive') ? 'inactive' : 'active';
                } elseif ($type === 'subcategories') {
                    $data += ['category_id' => $categoryId, 'description' => trim((string)($row['description'] ?? '')) ?: null];
                    $data['status'] = (int)($row['status'] ?? 1) === 1 ? 1 : 0;
                } else {
                    $data += ['subcategory_id' => $subcategoryId, 'description' => trim((string)($row['description'] ?? '')) ?: null];
                    $data['status'] = (int)($row['status'] ?? 1) === 1 ? 1 : 0;
                }
                if ($slug === '' || $this->catalog->slugExists($type, $slug, $categoryId ?: $subcategoryId)) $errors[] = 'Row ' . ($i + 1) . ': invalid or duplicate name.';
                $prepared[] = $data;
            }
        }
        if ($errors) return redirect()->back()->withInput()->with('errors', $errors);

        $db->transBegin();
        try {
            foreach ($prepared as $data) {
                if (!$this->catalog->saveRow($type, $data)) throw new \RuntimeException('A record could not be saved.');
            }
            if ($db->transStatus() === false) throw new \RuntimeException('Database transaction failed.');
            $db->transCommit();
        } catch (\Throwable $e) {
            $db->transRollback();
            return redirect()->back()->withInput()->with('error', 'Bulk save failed. ' . $e->getMessage());
        }
        return redirect()->to(site_url('shipping-admin/catalog/' . $type))->with('success', count($prepared) . ' records saved successfully.');
    }

    public function productTypesJson()
    {
        $subcategoryId = (int)$this->request->getGet('subcategory_id');
        return $this->response->setJSON(['items' => $this->catalog->producttype($subcategoryId ?: null, true)]);
    }

    public function attributesJson()
    {
        $productTypeId = (int)$this->request->getGet('product_type_id');
        return $this->response->setJSON(['items' => $productTypeId ? $this->catalog->attributesByProductType($productTypeId, true) : []]);
    }

    public function delete(string $type, int $id)
    {
        if (!$this->validType($type)) return redirect()->to(site_url('shipping-admin/catalog/categories'));
        if (!$this->catalog->findRow($type, $id)) return redirect()->to(site_url('shipping-admin/catalog/' . $type))->with('error', 'Record not found.');

        $reason = $this->catalog->deleteBlockReason($type, $id);
        if ($reason) return redirect()->to(site_url('shipping-admin/catalog/' . $type))->with('error', $reason);

        if (!$this->catalog->deleteRow($type, $id)) {
            return redirect()->to(site_url('shipping-admin/catalog/' . $type))->with('error', 'Delete failed.');
        }
        return redirect()->to(site_url('shipping-admin/catalog/' . $type))->with('success', 'Record deleted successfully.');
    }

    public function subcategoriesJson()
    {
        $categoryId = (int) $this->request->getGet('category_id');
        return $this->response->setJSON([
            'items' => $this->catalog->subcategories($categoryId ?: null, true),
        ]);
    }

    private function slugify(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/', '-', $value);
        return trim($value, '-');
    }
}
