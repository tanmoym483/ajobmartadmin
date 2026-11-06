<?php

namespace App\Models;

use CodeIgniter\Model;

class ShippingAdminDataModel extends Model
{
    protected $table = 'ajobmart_pincode_directory';
    protected $primaryKey = 'id';
    protected $returnType = 'array';

    public function importPin(array $pin): bool
    {
        // pincode must have a UNIQUE index in the database.
        // Existing metro_code and local_area_code values are preserved.
        $sql = "
            INSERT INTO ajobmart_pincode_directory
                (pincode, state_name, district_name, city_name,
                 is_active, created_at, updated_at)
            VALUES (?, ?, ?, ?, 1, NOW(), NOW())
            ON DUPLICATE KEY UPDATE
                state_name = VALUES(state_name),
                district_name = VALUES(district_name),
                city_name = VALUES(city_name),
                is_active = 1,
                updated_at = NOW()
        ";

        return $this->db->query($sql, [
            $pin['pincode'],
            $pin['state_name'],
            $pin['district_name'],
            $pin['city_name'],
        ]);
    }

    public function searchPins(string $term = ''): array
    {
        $builder = $this->db->table('ajobmart_pincode_directory');

        if ($term !== '') {
            $builder->groupStart()
                ->like('pincode', $term)
                ->orLike('city_name', $term)
                ->orLike('district_name', $term)
                ->orLike('state_name', $term)
                ->groupEnd();
        }

        return $builder->orderBy('pincode', 'ASC')
            ->limit(100)
            ->get()->getResultArray();
    }

    public function searchMappings(string $term = ''): array
    {
        $builder = $this->db->table('ajobmart_shipping_zone_overrides');

        if ($term !== '') {
            $builder->groupStart()
                ->like('origin_pincode', $term)
                ->orLike('destination_pincode', $term)
                ->orLike('zone_code', $term)
                ->orLike('notes', $term)
                ->groupEnd();
        }

        return $builder->orderBy('id', 'DESC')
            ->limit(300)
            ->get()->getResultArray();
    }

    public function saveMapping(array $data): bool
    {
        $table = $this->db->table('ajobmart_shipping_zone_overrides');

        $existing = $table
            ->where('origin_pincode', $data['origin_pincode'])
            ->where('destination_pincode', $data['destination_pincode'])
            ->get()->getRowArray();

        if ($existing) {
            return false;
        }

        return $table->insert($data) !== false;
    }

    public function toggleMapping(int $id): bool
    {
        $table = $this->db->table('ajobmart_shipping_zone_overrides');

        $mapping = $table->where('id', $id)->get()->getRowArray();

        if (!$mapping) {
            return false;
        }

        return $this->db->table('ajobmart_shipping_zone_overrides')
            ->where('id', $id)
            ->update([
                'is_active' => (int) !(bool) $mapping['is_active'],
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
    }
}
