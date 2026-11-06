<?php

namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;

class ShippingAdminFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = session();

        /*
         * SECURITY:
         * Replace these checks with the exact session key used by
         * your existing admin login if it differs.
         *
         * Access is denied unless an admin session is present.
         */
        $isAdminLoggedIn = $session->get('admin_logged_in') === true;

        $role = strtoupper((string) $session->get('role'));
        $isAdminRole = in_array($role, ['ADMIN', 'SUPER_ADMIN'], true);

        if (!$isAdminLoggedIn && !$isAdminRole) {
            return redirect()->to(site_url('admin/login'))
                ->with('error', 'Please log in as an administrator.');
        }

        return null;
    }

    public function after(
        RequestInterface $request,
        ResponseInterface $response,
        $arguments = null
    ) {
        // No action required.
    }
}
