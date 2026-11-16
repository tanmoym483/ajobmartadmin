
<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

// First admin setup
$routes->get('/', 'ShippingAdmin::setup');
    // $routes->get('/', 'ShippingAdmin::index');

$routes->post('shipping-admin/setup', 'ShippingAdmin::createFirstAdmin');

// Admin login
$routes->get('shipping-admin/login', 'ShippingAdmin::login');
$routes->post('shippingadmin/login', 'ShippingAdmin::authenticate');

$routes->group('shipping-admin', [
    'filter' => 'shippingadminauth',
], static function ($routes) {

    $routes->get('/', 'ShippingAdmin::index');

    $routes->get('shipping', 'ShippingAdmin::shipping');

    $routes->post('logout', 'ShippingAdmin::logout');

    $routes->post('import', 'ShippingAdmin::importCsv');

    $routes->post('mapping/save', 'ShippingAdmin::saveMapping');

    $routes->post('mapping/toggle/(:num)', 'ShippingAdmin::toggleMapping/$1');

    // Shipping label design settings
    $routes->get('label-settings', 'ShippingAdmin::labelSettings');
    $routes->post('label-settings/save', 'ShippingAdmin::saveLabelSettings');

    // Seller-generated label review
    $routes->get('label-bookings', 'ShippingAdmin::labelBookings');
    $routes->get('label-bookings/(:num)', 'ShippingAdmin::labelBookingDetails/$1');

    $routes->post('label-bookings/(:num)/approve', 'ShippingAdmin::approveLabel/$1');
    $routes->post('label-bookings/(:num)/reject', 'ShippingAdmin::rejectLabel/$1');

    // Manual India Post booking
    $routes->post('label-bookings/(:num)/book', 'ShippingAdmin::savePostalBooking/$1');

    // Dispatch and delivery updates
    $routes->post('label-bookings/(:num)/status', 'ShippingAdmin::updatePostalStatus/$1');

    $routes->get('label-bookings/(:num)/pdf', 'ShippingAdmin::viewSellerLabel/$1');
    $routes->get('label-bookings/(:num)/receipt', 'ShippingAdmin::viewPostalReceipt/$1');



    $routes->get('catalog', 'Admin\CatalogManagementController::index/categories');
    $routes->get('catalog/(:segment)', 'Admin\CatalogManagementController::index/$1');
    $routes->get('catalog/(:segment)/add', 'Admin\CatalogManagementController::add/$1');
    $routes->get('catalog/(:segment)/edit/(:num)', 'Admin\CatalogManagementController::edit/$1/$2');
    $routes->post('catalog/(:segment)/save', 'Admin\CatalogManagementController::save/$1');
    $routes->post('catalog/(:segment)/save/(:num)', 'Admin\CatalogManagementController::save/$1/$2');
    $routes->post('catalog/(:segment)/delete/(:num)', 'Admin\CatalogManagementController::delete/$1/$2');


    $routes->get('catalog', 'Admin\CatalogManagementController::index/categories');
    $routes->get('catalog/categories', 'Admin\CatalogManagementController::index/categories');
    $routes->get('catalog/subcategories', 'Admin\CatalogManagementController::index/subcategories');
    $routes->get('catalog/product-types', 'Admin\CatalogManagementController::index/product-types');
    $routes->get('catalog/attributes', 'Admin\CatalogManagementController::index/attributes');

    $routes->get('catalog/(:segment)/add', 'Admin\CatalogManagementController::add/$1');
    $routes->get('catalog/(:segment)/edit/(:num)', 'Admin\CatalogManagementController::edit/$1/$2');
    $routes->post('catalog/(:segment)/save', 'Admin\CatalogManagementController::save/$1');
    $routes->post('catalog/(:segment)/save/(:num)', 'Admin\CatalogManagementController::save/$1/$2');
    $routes->post('catalog/(:segment)/delete/(:num)', 'Admin\CatalogManagementController::delete/$1/$2');
});

// Admin order management
$routes->get('orders', 'ShippingAdminOrders::index');
$routes->get('orders/list-data', 'ShippingAdminOrders::listData');
$routes->get('orders/(:num)', 'ShippingAdminOrders::details/$1');


