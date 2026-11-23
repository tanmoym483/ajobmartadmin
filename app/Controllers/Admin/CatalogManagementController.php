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
    if (!$this->validType($type)) {
        return redirect()->to(site_url('shipping-admin/catalog/categories'));
    }

    $db = db_connect();
    $errors = [];

    $rows = $this->request->getPost('rows');

    if (!is_array($rows)) {
        $rows = [];
    }

    // Keep only rows containing the required identifying field.
    $rows = array_values(array_filter($rows, static function ($row) use ($type) {

        if (!is_array($row)) {
            return false;
        }

        if ($type === 'attributes') {
            return trim((string)($row['attribute_name'] ?? $row['label'] ?? '')) !== '';
        }

        return trim((string)($row['name'] ?? '')) !== '';
    }));

    if (!$rows) {
        $errors[] = 'Please enter at least one record.';
    }

    // Global dropdown selections (Subcategories and Product Types).
    $categoryId = (int)$this->request->getPost('category_id');
    $subcategoryId = (int)$this->request->getPost('subcategory_id');

    $prepared = [];


    // =====================================================
    // VALIDATE GLOBAL CATEGORY FOR SUBCATEGORIES
    // =====================================================

    if ($type === 'subcategories') {

        $cat = $this->catalog->findRow('categories', $categoryId);

        if (!$cat || ($cat['status'] ?? '') !== 'active') {
            $errors[] = 'Select an active category.';
        }

    }


    // =====================================================
    // VALIDATE GLOBAL SUBCATEGORY FOR PRODUCT TYPES
    // =====================================================

    elseif ($type === 'product-types') {

        $sub = $this->catalog->findRow('subcategories', $subcategoryId);

        $cat = $sub
            ? $this->catalog->findRow('categories', (int)$sub['category_id'])
            : null;

        if (
            !$sub ||
            (int)($sub['status'] ?? 0) !== 1 ||
            !$cat ||
            ($cat['status'] ?? '') !== 'active'
        ) {
            $errors[] = 'Select a subcategory under an active category.';
        }

    }


    // =====================================================
    // PREPARE EACH ROW
    // =====================================================

    foreach ($rows as $i => $row) {

        $rowNumber = $i + 1;

        $sort = (int)($row['sort_order'] ?? 0);


        // =================================================
        // ATTRIBUTES
        // Each attribute has its own category,
        // subcategory and product type.
        // =================================================

        if ($type === 'attributes') {

            $rowCategoryId = (int)($row['category_id'] ?? 0);

            $rowSubcategoryId = (int)($row['subcategory_id'] ?? 0);

            $rowProductTypeId = (int)($row['product_type_id'] ?? 0);


            // Get related records independently for this row.
            $pt = $rowProductTypeId
                ? $this->catalog->findRow('product-types', $rowProductTypeId)
                : null;

            $sub = $rowSubcategoryId
                ? $this->catalog->findRow('subcategories', $rowSubcategoryId)
                : null;

            $cat = $rowCategoryId
                ? $this->catalog->findRow('categories', $rowCategoryId)
                : null;


            // Validate category.
            if (!$cat || ($cat['status'] ?? '') !== 'active') {

                $errors[] = "Attribute row {$rowNumber}: select an active category.";

            }


            // Validate subcategory belongs to selected category.
            if (
                !$sub ||
                (int)($sub['status'] ?? 0) !== 1 ||
                (int)($sub['category_id'] ?? 0) !== $rowCategoryId
            ) {

                $errors[] = "Attribute row {$rowNumber}: select a valid subcategory under the selected category.";

            }


            // Validate product type belongs to selected subcategory.
            if (
                !$pt ||
                (int)($pt['status'] ?? 0) !== 1 ||
                (int)($pt['subcategory_id'] ?? 0) !== $rowSubcategoryId
            ) {

                $errors[] = "Attribute row {$rowNumber}: select a valid product type under the selected subcategory.";

            }


            // Attribute details.
            $label = trim((string)($row['label'] ?? ''));

            $key = strtolower(trim((string)($row['attribute_name'] ?? $label)));

            $key = trim(
                (string)preg_replace('/[^a-z0-9_]+/', '_', $key),
                '_'
            );

            $inputType = (string)($row['input_type'] ?? 'text');

            $allowed = [
                'text',
                'number',
                'textarea',
                'select',
                'date',
                'checkbox'
            ];


            if ($label === '' || $key === '') {

                $errors[] = "Attribute row {$rowNumber}: label and key are required.";

            }


            if (!in_array($inputType, $allowed, true)) {

                $errors[] = "Attribute row {$rowNumber}: invalid input type.";

            }


            // Options handling.
            $optionsJson = null;

            if (in_array($inputType, ['select', 'checkbox'], true)) {

                $rawOptions = (string)($row['options'] ?? '');

                $opts = array_values(array_filter(
                    array_map('trim', preg_split('/\r\n|\r|\n/', $rawOptions))
                ));

                if (!$opts) {

                    $errors[] = "Attribute row {$rowNumber}: enter options on separate lines.";

                }

                $optionsJson = json_encode(
                    $opts,
                    JSON_UNESCAPED_UNICODE
                );

                if ($optionsJson === false) {

                    $errors[] = "Attribute row {$rowNumber}: unable to encode options.";

                    $optionsJson = null;

                }

            }


            // Prepare attribute record.
            $prepared[] = [

                'category_id' => $rowCategoryId,

                'subcategory_id' => $rowSubcategoryId,

                'product_type_id' => $rowProductTypeId,

                'attribute_name' => $key,

                'label' => $label,

                'input_type' => $inputType,

                'is_required' => !empty($row['is_required']) ? 1 : 0,

                'options_json' => $optionsJson,

                'status' => (($row['status'] ?? 'active') === 'inactive')
                    ? 'inactive'
                    : 'active',

                'sort_order' => $sort,

            ];

        }


        // =================================================
        // CATEGORIES / SUBCATEGORIES / PRODUCT TYPES
        // =================================================

        else {

            $name = trim((string)($row['name'] ?? ''));

            if ($name === '') {

                $errors[] = "Row {$rowNumber}: name is required.";

                continue;

            }

            $slug = $this->slugify($name);

            $data = [

                'name' => $name,

                'slug' => $slug,

                'sort_order' => $sort,

            ];


            // Categories.
            if ($type === 'categories') {

                $data['status'] =
                    (($row['status'] ?? 'active') === 'inactive')
                    ? 'inactive'
                    : 'active';

            }


            // Subcategories.
            elseif ($type === 'subcategories') {

                $data += [

                    'category_id' => $categoryId,

                    'description' => trim(
                        (string)($row['description'] ?? '')
                    ) ?: null,

                ];

                $data['status'] =
                    (int)($row['status'] ?? 1) === 1 ? 1 : 0;

            }


            // Product Types.
            elseif ($type === 'product-types') {

                $data += [

                    'subcategory_id' => $subcategoryId,

                    'description' => trim(
                        (string)($row['description'] ?? '')
                    ) ?: null,

                ];

                $data['status'] =
                    (int)($row['status'] ?? 1) === 1 ? 1 : 0;

            }


            // Slug validation.
            if (
                $slug === '' ||
                $this->catalog->slugExists(
                    $type,
                    $slug,
                    $categoryId ?: $subcategoryId
                )
            ) {

                $errors[] = "Row {$rowNumber}: invalid or duplicate name.";

            }

            $prepared[] = $data;

        }

    }


    // =====================================================
    // STOP IF VALIDATION ERRORS EXIST
    // =====================================================

    if ($errors) {

        return redirect()
            ->back()
            ->withInput()
            ->with('errors', $errors);

    }


    // =====================================================
    // SAVE ALL RECORDS USING TRANSACTION
    // =====================================================

    $db->transBegin();

    try {

        foreach ($prepared as $index => $data) {

            $result = $this->catalog->saveRow($type, $data);

            if ($result === false) {

                $modelErrors = [];

                if (method_exists($this->catalog, 'errors')) {
                    $modelErrors = $this->catalog->errors();
                }

                $dbError = $db->error();

                $details = [
                    'record_number' => $index + 1,
                    'catalog_type'  => $type,
                    'model_errors'  => $modelErrors,
                    'database_error' => $dbError,
                    'submitted_fields' => array_keys($data),
                ];

                log_message(
                    'error',
                    'Catalog save failed: ' . json_encode($details)
                );

                throw new \RuntimeException(
                    'Record ' . ($index + 1) . ' could not be saved. ' .
                    json_encode($details)
                );
            }
        }


        if ($db->transStatus() === false) {

            throw new \RuntimeException(
                'Database transaction failed.'
            );

        }


        $db->transCommit();

    } catch (\Throwable $e) {

        $db->transRollback();

        log_message('error', 'Catalog bulk save error: ' . $e->getMessage());

        return redirect()
            ->back()
            ->withInput()
            ->with(
                'error',
                'Bulk save failed. ' . $e->getMessage()
            );

    }


    // =====================================================
    // SUCCESS
    // =====================================================

    return redirect()
        ->to(site_url('shipping-admin/catalog/' . $type))
        ->with(
            'success',
            count($prepared) . ' records saved successfully.'
        );
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

    public function existingAttributesJson()
    {
        $categoryId    = (int) $this->request->getGet('category_id');
        $subcategoryId = (int) $this->request->getGet('subcategory_id');
        $productTypeId = (int) $this->request->getGet('product_type_id');

        if (!$categoryId || !$subcategoryId || !$productTypeId) {
            return $this->response->setJSON([
                'status'  => false,
                'message' => 'Please select category, subcategory and product type.',
                'items'   => [],
            ]);
        }

        // Verify that the selected hierarchy is valid.
        $productType = $this->catalog->findRow('product-types', $productTypeId);

        if (
            !$productType ||
            (int) ($productType['subcategory_id'] ?? 0) !== $subcategoryId ||
            (int) ($productType['status'] ?? 0) !== 1
        ) {
            return $this->response->setJSON([
                'status'  => false,
                'message' => 'Invalid product type.',
                'items'   => [],
            ]);
        }

        $subcategory = $this->catalog->findRow('subcategories', $subcategoryId);

        if (
            !$subcategory ||
            (int) ($subcategory['category_id'] ?? 0) !== $categoryId ||
            (int) ($subcategory['status'] ?? 0) !== 1
        ) {
            return $this->response->setJSON([
                'status'  => false,
                'message' => 'Invalid subcategory.',
                'items'   => [],
            ]);
        }

        $db = db_connect();

        // Get distinct attribute definitions from the existing catalog.
        $builder = $db->table('ajobmart_category_attributes');

        $builder->select(
            'MIN(id) AS id,
            attribute_name,
            label,
            input_type,
            options_json,
            is_required'
        );

        $builder->where('status', 'active');

        $builder->groupBy([
            'attribute_name',
            'label',
            'input_type',
            'options_json',
            'is_required'
        ]);

        $builder->orderBy('attribute_name', 'ASC');

        $items = $builder->get()->getResultArray();

        // Exclude attributes already assigned to this product type.
        $existingRows = $db->table('ajobmart_category_attributes')
            ->select('attribute_name')
            ->where('category_id', $categoryId)
            ->where('subcategory_id', $subcategoryId)
            ->where('product_type_id', $productTypeId)
            ->get()
            ->getResultArray();

        $existingKeys = array_column($existingRows, 'attribute_name');

        $items = array_values(array_filter($items, static function ($item) use ($existingKeys) {
            return !in_array($item['attribute_name'], $existingKeys, true);
        }));

        return $this->response->setJSON([
            'status'  => true,
            'message' => count($items) ? 'Attributes loaded.' : 'No additional attributes found.',
            'items'   => $items,
        ]);
    }
}
