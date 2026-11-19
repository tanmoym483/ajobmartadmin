<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;

class CancellationRequests extends BaseController
{
    protected $db;
    protected $layout = 'shipping_admin/layouts/main';


    public function __construct()
    {
        $this->db = db_connect();
    }

    public function index()
    {
        $status = $this->request->getGet('status') ?? 'pending';

        $builder = $this->db
            ->table('ajobmart_order_cancellation_requests cr')
            ->select('
                cr.*,
                o.order_number,
                o.total_amount,
                o.payment_status,
                o.order_status,
                u.name AS customer_name,
                u.email AS customer_email,
                u.mobile AS customer_mobile
            ')
            ->join('ajobmart_orders o', 'o.id = cr.order_id')
            ->join('ajobmart_users u', 'u.id = cr.customer_id');

        if (in_array($status, ['pending', 'approved', 'rejected'])) {
            $builder->where('cr.request_status', $status);
        }

        $requests = $builder
            ->orderBy('cr.id', 'DESC')
            ->get()
            ->getResultArray();

        $counts = [];

        foreach (['pending', 'approved', 'rejected'] as $s) {
            $counts[$s] = $this->db
                ->table('ajobmart_order_cancellation_requests')
                ->where('request_status', $s)
                ->countAllResults();
        }

        return view('admin/cancellation_requests/index', [
            'requests' => $requests,
            'counts'   => $counts,
            'status'   => $status,
        ]);
    }

    public function details($id)
    {
        $request = $this->db
            ->table('ajobmart_order_cancellation_requests cr')
            ->select('
                cr.*,
                o.order_number,
                o.total_amount,
                o.subtotal,
                o.shipping_charge,
                o.payment_method,
                o.payment_status,
                o.order_status,
                u.name AS customer_name,
                u.email AS customer_email,
                u.mobile AS customer_mobile
            ')
            ->join('ajobmart_orders o', 'o.id = cr.order_id')
            ->join('ajobmart_users u', 'u.id = cr.customer_id')
            ->where('cr.id', (int) $id)
            ->get()
            ->getRowArray();

        if (!$request) {
            return redirect()->to(site_url('admin/cancellation-requests'))
                ->with('error', 'Cancellation request not found.');
        }

        $items = $this->db
            ->table('ajobmart_order_items')
            ->where('order_id', $request['order_id'])
            ->get()
            ->getResultArray();

        return view('admin/cancellation_requests/details', [
            'request' => $request,
            'items'   => $items,
        ]);
    }

    public function review($id)
    {
        if (!$this->request->is('post')) {
            return redirect()->back();
        }

        $decision = $this->request->getPost('decision');
        $adminNote = trim((string) $this->request->getPost('admin_note'));

        if (!in_array($decision, ['approved', 'rejected'])) {
            return redirect()->back()
                ->with('error', 'Invalid cancellation decision.');
        }

        if ($adminNote === '') {
            return redirect()->back()
                ->with('error', 'Please enter an admin reply.');
        }

        $request = $this->db
            ->table('ajobmart_order_cancellation_requests')
            ->where('id', (int) $id)
            ->get()
            ->getRowArray();

        if (!$request) {
            return redirect()->to(site_url('admin/cancellation-requests'))
                ->with('error', 'Cancellation request not found.');
        }

        if ($request['request_status'] !== 'pending') {
            return redirect()->back()
                ->with('error', 'This request has already been reviewed.');
        }

        $this->db
            ->table('ajobmart_order_cancellation_requests')
            ->where('id', (int) $id)
            ->update([
                'request_status' => $decision,
                'admin_note'     => $adminNote,
                'reviewed_by'    => (int) session('admin_id') ?: null,
                'reviewed_at'    => date('Y-m-d H:i:s'),
            ]);

        return redirect()->to(site_url('admin/cancellation-requests/' . $id))
            ->with('success', 'Cancellation request reviewed successfully.');
    }
}
