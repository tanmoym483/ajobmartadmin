<?php

namespace App\Models;

use CodeIgniter\Model;

class ShippingAdminModel extends Model
{
    protected $table = 'ajobmart_shipping_admins';
    protected $primaryKey = 'id';
    protected $returnType = 'array';

    protected $allowedFields = [
        'username',
        'password_hash',
        'role',
        'is_active',
        'last_login',
        'created_at',
        'updated_at',
    ];

    public function findByUsername(string $username): ?array
    {
        return $this->where('username', $username)->first();
    }

    public function adminCount(): int
    {
        return $this->countAllResults();
    }

    public function createAdmin(string $username, string $password): bool
    {
        return $this->insert([
            'username'      => $username,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'role'          => 'ADMIN',
            'is_active'     => 1,
            'created_at'    => date('Y-m-d H:i:s'),
        ]) !== false;
    }

    public function recordLogin(int $id): void
    {
        $this->update($id, [
            'last_login' => date('Y-m-d H:i:s'),
        ]);
    }
}
