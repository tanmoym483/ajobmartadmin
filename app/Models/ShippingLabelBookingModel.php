<?php

namespace App\Models;

use CodeIgniter\Model;

class ShippingLabelBookingModel extends Model
{
    protected $table = 'ajobmart_shipping_label_bookings';
    protected $primaryKey = 'id';
    protected $returnType = 'array';

    protected $allowedFields = [
        'label_id',
        'seller_id',
        'order_id',
        'admin_status',
        'admin_note',
        'rejection_reason',
        'approved_by',
        'approved_at',
        'booking_service',
        'consignment_number',
        'booking_date',
        'booking_post_office',
        'parcel_weight_grams',
        'parcel_length_cm',
        'parcel_width_cm',
        'parcel_height_cm',
        'postage_amount',
        'cod_amount',
        'postal_receipt_path',
        'booked_by',
        'booked_at',
        'shipped_at',
        'delivered_at',
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    /**
     * Create a pending admin review record for a seller label.
     * Does not create duplicate records for the same label.
     */
    public function createFromLabel(array $label): int
    {
        $existing = $this->where('label_id', $label['id'])
            ->first();

        if ($existing) {
            return (int) $existing['id'];
        }

        $this->insert([
            'label_id' => $label['id'],
            'seller_id' => $label['seller_id'],
            'order_id' => $label['order_id'],
            'admin_status' => 'pending_review',
        ]);

        return (int) $this->getInsertID();
    }

    /**
     * Fetch label bookings for the admin list.
     */
    public function getAdminBookings(string $status = ''): array
    {
        $builder = $this->db->table($this->table . ' b');

        $builder->select([
            'b.*',
            'l.label_number',
            'l.pdf_path',
            'l.label_status',
            'l.generated_at',
            'o.order_number',
        ]);

        $builder->join(
            'ajobmart_seller_order_labels l',
            'l.id = b.label_id',
            'left'
        );

        $builder->join(
            'ajobmart_orders o',
            'o.id = b.order_id',
            'left'
        );

        if ($status !== '') {
            $builder->where('b.admin_status', $status);
        }

        return $builder
            ->orderBy('b.id', 'DESC')
            ->get()
            ->getResultArray();
    }

    /**
     * Fetch one booking with its seller label and order details.
     */
    public function getAdminBooking(int $id): ?array
    {
        return $this->db->table($this->table . ' b')
            ->select([
                'b.*',
                'l.label_number',
                'l.pdf_path',
                'l.label_status',
                'l.generated_at',
                'o.order_number',
                'o.payment_method',
                'o.payment_status',
                'o.order_status',
            ])
            ->join(
                'ajobmart_seller_order_labels l',
                'l.id = b.label_id',
                'left'
            )
            ->join(
                'ajobmart_orders o',
                'o.id = b.order_id',
                'left'
            )
            ->where('b.id', $id)
            ->get()
            ->getRowArray();
    }
}
