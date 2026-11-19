<?php

namespace App\Models\Admin;

use CodeIgniter\Model;

class CatalogManagementModel extends Model
{
    protected $DBGroup = 'default';

    private array $config = [
        'categories' => [
            'table' => 'ajobmart_categories',
            'title' => 'Categories',
            'parent' => null,
            'status_type' => 'string',
            'name_max' => 100,
        ],
        'subcategories' => [
            'table' => 'ajobmart_subcategories',
            'title' => 'Subcategories',
            'parent' => 'category_id',
            'status_type' => 'int',
            'name_max' => 150,
        ],
        'product-types' => [
            'table' => 'ajobmart_product_types',
            'title' => 'Product Types',
            'parent' => 'subcategory_id',
            'status_type' => 'int',
            'name_max' => 150,
        ],
        'attributes' => [
            'table' => 'ajobmart_category_attributes',
            'title' => 'Category Attributes',
            'parent' => 'category_id',
            'status_type' => 'string',
            'name_max' => 100,
        ],
    ];

    public function configFor(string $type): array
    {
        if (!isset($this->config[$type])) {
            throw new \InvalidArgumentException('Unknown catalog resource.');
        }
        return $this->config[$type];
    }

    public function categories(): array
    {
        return db_connect()->table('ajobmart_categories')
            ->orderBy('sort_order', 'ASC')->orderBy('name', 'ASC')
            ->get()->getResultArray();
    }

    public function subcategories(?int $categoryId = null, bool $activeOnly = false): array
    {
        $builder = db_connect()->table('ajobmart_subcategories s')
            ->select('s.*, c.name AS category_name')
            ->join('ajobmart_categories c', 'c.id = s.category_id', 'left');
        if ($categoryId) $builder->where('s.category_id', $categoryId);
        if ($activeOnly) $builder->where('s.status', 1);
        return $builder->orderBy('s.sort_order', 'ASC')->orderBy('s.name', 'ASC')
            ->get()->getResultArray();
    }

    public function producttype(?int $subcategoryId = null, bool $activeOnly = false): array
    {
        $builder = db_connect()->table('ajobmart_product_types p')
            ->select('p.*, s.name AS subcategory_name')
            ->join('ajobmart_subcategories s', 's.id = p.subcategory_id', 'left');
        if ($subcategoryId) $builder->where('p.subcategory_id', $subcategoryId);
        if ($activeOnly) $builder->where('p.status', 1);
        return $builder->orderBy('p.sort_order', 'ASC')->orderBy('p.name', 'ASC')
            ->get()->getResultArray();
    }

    public function rows(string $type, string $search = ''): array
    {
        $db = db_connect();
        $search = trim($search);

        if ($type === 'categories') {
            $builder = $db->table('ajobmart_categories');
            if ($search !== '') $builder->groupStart()->like('name', $search)->orLike('slug', $search)->groupEnd();
            return $builder->orderBy('sort_order', 'ASC')->orderBy('name', 'ASC')->get()->getResultArray();
        }

        if ($type === 'subcategories') {
            $builder = $db->table('ajobmart_subcategories s')
                ->select('s.*, c.name AS category_name')
                ->join('ajobmart_categories c', 'c.id = s.category_id', 'left');
            if ($search !== '') $builder->groupStart()->like('s.name', $search)->orLike('s.slug', $search)->orLike('c.name', $search)->groupEnd();
            return $builder->orderBy('s.sort_order', 'ASC')->orderBy('s.name', 'ASC')->get()->getResultArray();
        }

        if ($type === 'product-types') {
            $builder = $db->table('ajobmart_product_types pt')
                ->select('pt.*, s.name AS subcategory_name, c.name AS category_name')
                ->join('ajobmart_subcategories s', 's.id = pt.subcategory_id')
                ->join('ajobmart_categories c', 'c.id = s.category_id');
            if ($search !== '') $builder->groupStart()->like('pt.name', $search)->orLike('pt.slug', $search)->orLike('s.name', $search)->orLike('c.name', $search)->groupEnd();
            return $builder->orderBy('c.sort_order', 'ASC')->orderBy('s.sort_order', 'ASC')
                ->orderBy('pt.sort_order', 'ASC')->get()->getResultArray();
        }

        $builder = $db->table('ajobmart_category_attributes a')
            ->select('a.*, c.name AS category_name, s.name AS subcategory_name, p.name AS producttype_name')
            ->join('ajobmart_categories c', 'c.id = a.category_id')
            ->join('ajobmart_subcategories s', 's.id = a.subcategory_id', 'left')
            ->join('ajobmart_product_types p', 'p.id = a.product_type_id', 'left');

        if ($search !== '') $builder->groupStart()->like('a.label', $search)->orLike('a.attribute_name', $search)->orLike('c.name', $search)->orLike('s.name', $search)->orLike('p.name', $search)->groupEnd();
        return $builder->orderBy('c.sort_order', 'ASC')->orderBy('a.sort_order', 'ASC')->get()->getResultArray();
    }

    public function findRow(string $type, int $id): ?array
    {
        $row = db_connect()->table($this->configFor($type)['table'])
            ->where('id', $id)->get()->getRowArray();
        return $row ?: null;
    }

    public function saveRow(string $type, array $data, ?int $id = null): bool
    {
        $db = db_connect();
        $table = $this->configFor($type)['table'];
        $data['updated_at'] = date('Y-m-d H:i:s');

        if ($id) {
            return $db->table($table)->where('id', $id)->update($data);
        }

        $data['created_at'] = date('Y-m-d H:i:s');
        return (bool) $db->table($table)->insert($data);
    }

    public function slugExists(string $type, string $slug, int $parentId = 0, ?int $excludeId = null): bool
    {
        $db = db_connect();
        $cfg = $this->configFor($type);
        $builder = $db->table($cfg['table'])->where('slug', $slug);

        if ($cfg['parent'] !== null && $type !== 'attributes') {
            $builder->where($cfg['parent'], $parentId);
        }
        if ($excludeId) $builder->where('id !=', $excludeId);
        return $builder->countAllResults() > 0;
    }

    public function deleteBlockReason(string $type, int $id): ?string
    {
        $db = db_connect();

        if ($type === 'categories') {
            $subCount = $db->table('ajobmart_subcategories')->where('category_id', $id)->countAllResults();
            $productCount = $db->table('ajobmart_products')->where('category_id', $id)->countAllResults();
            $attributeCount = $db->table('ajobmart_category_attributes')->where('category_id', $id)->countAllResults();
            if ($subCount || $productCount || $attributeCount) {
                return 'This category is in use. Remove or reassign its subcategories, products and attributes before deleting it.';
            }
        } elseif ($type === 'subcategories') {
            $productCount = $db->table('ajobmart_products')->where('subcategory_id', $id)->countAllResults();
            $typeCount = $db->table('ajobmart_product_types')->where('subcategory_id', $id)->countAllResults();
            $attributeCount = $db->table('ajobmart_category_attributes')->where('subcategory_id', $id)->countAllResults();
            if ($productCount || $typeCount || $attributeCount) {
                return 'This subcategory is in use by products, product types or attributes. Reassign those records first.';
            }
        } elseif ($type === 'product-types') {
            $productCount = $db->table('ajobmart_products')->where('product_type_id', $id)->countAllResults();
            if ($productCount) return 'This product type is assigned to products and cannot be deleted.';
        } elseif ($type === 'attributes') {
            if ($db->tableExists('ajobmart_product_attribute_values')) {
                $valueCount = $db->table('ajobmart_product_attribute_values')
                    ->where('attribute_id', $id)->countAllResults();
                if ($valueCount) return 'This attribute already has product values. Deleting it would break existing product data.';
            }
        }

        return null;
    }

    public function deleteRow(string $type, int $id): bool
    {
        return db_connect()->table($this->configFor($type)['table'])
            ->where('id', $id)->delete();
    }
}
