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
            'categories' => $this->catalog->categories(),
            'subcategories' => $this->catalog->subcategories(),
            'producttype' => $this->catalog->producttype(),
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
            if (!$this->catalog->findRow('categories', $categoryId)) $errors[] = 'Please select a valid category.';
            $data += ['category_id' => $categoryId, 'description' => $description ?: null];
            $status = (int) $this->request->getPost('status');
            $data['status'] = $status === 1 ? 1 : 0;
        } elseif ($type === 'product-types') {
            $subcategoryId = (int) $this->request->getPost('subcategory_id');
            if (!$this->catalog->findRow('subcategories', $subcategoryId)) $errors[] = 'Please select a valid subcategory.';
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

            if (!$category) {
                $errors[] = 'Please select a valid category.';
            }

            // Validate subcategory
            if ($subcategoryId) {

                $sub = $this->catalog->findRow('subcategories', $subcategoryId);

                if (!$sub || (int) $sub['category_id'] !== $categoryId) {
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

                if (!$productType) {

                    $errors[] = 'Please select a valid product type.';

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
