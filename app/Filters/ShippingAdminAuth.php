<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class ShippingAdminAuth implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if ((int) session()->get('shipping_admin_id') < 1) {
            return redirect()->to(site_url('shipping-admin/login'))
                ->with('error', 'Please log in to continue.');
        }

        return null;
    }

    public function after(
        RequestInterface $request,
        ResponseInterface $response,
        $arguments = null
    ) {
        // Nothing required.
    }
}