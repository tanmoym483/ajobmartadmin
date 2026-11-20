
<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

// =========================================
// FIRST ADMIN SETUP
// =========================================

$routes->get('/', 'ShippingAdmin::setup');

$routes->post('shipping-admin/setup', 'ShippingAdmin::createFirstAdmin');


// =========================================
// ADMIN LOGIN
// =========================================

$routes->get('shipping-admin/login', 'ShippingAdmin::login');

$routes->post('shippingadmin/login', 'ShippingAdmin::authenticate');


// =========================================
// SHIPPING ADMIN AUTHENTICATED ROUTES
// =========================================

$routes->group('shipping-admin', [
    'filter' => 'shippingadminauth',
], static function ($routes) {

    // Dashboard
    $routes->get('/', 'ShippingAdmin::index');


    // =====================================
    // SHIPPING MANAGEMENT
    // =====================================

    $routes->get('shipping', 'ShippingAdmin::shipping');

    $routes->post('logout', 'ShippingAdmin::logout');

    $routes->post('import', 'ShippingAdmin::importCsv');


    // =====================================
    // SHIPPING MAPPING
    // =====================================

    $routes->post('mapping/save', 'ShippingAdmin::saveMapping');

    $routes->post('mapping/toggle/(:num)', 'ShippingAdmin::toggleMapping/$1');


    // =====================================
    // SHIPPING LABEL DESIGN SETTINGS
    // =====================================

    $routes->get('label-settings', 'ShippingAdmin::labelSettings');

    $routes->post('label-settings/save', 'ShippingAdmin::saveLabelSettings');


    // =====================================
    // SELLER LABEL BOOKINGS
    // =====================================

    $routes->get('label-bookings', 'ShippingAdmin::labelBookings');

    $routes->get('label-bookings/(:num)', 'ShippingAdmin::labelBookingDetails/$1');

    $routes->post('label-bookings/(:num)/approve', 'ShippingAdmin::approveLabel/$1');

    $routes->post('label-bookings/(:num)/reject', 'ShippingAdmin::rejectLabel/$1');


    // =====================================
    // MANUAL INDIA POST BOOKING
    // =====================================

    $routes->post('label-bookings/(:num)/book', 'ShippingAdmin::savePostalBooking/$1');


    // =====================================
    // DISPATCH AND DELIVERY UPDATES
    // =====================================

    $routes->post('label-bookings/(:num)/status', 'ShippingAdmin::updatePostalStatus/$1');

    $routes->get('label-bookings/(:num)/pdf', 'ShippingAdmin::viewSellerLabel/$1');

    $routes->get('label-bookings/(:num)/receipt', 'ShippingAdmin::viewPostalReceipt/$1');


    // =====================================
    // PRODUCT CATALOG MANAGEMENT
    // =====================================

    // Catalog default page
    $routes->get('catalog', 'Admin\CatalogManagementController::index/categories');

    $routes->get('catalog/subcategories-json', 'Admin\CatalogManagementController::subcategoriesJson');
$routes->get('catalog/product-types-json', 'Admin\CatalogManagementController::productTypesJson');
$routes->get('catalog/attributes-json', 'Admin\CatalogManagementController::attributesJson');
$routes->get('catalog/(:segment)/bulk', 'Admin\CatalogManagementController::bulk/$1');
$routes->post('catalog/(:segment)/bulk/save', 'Admin\CatalogManagementController::bulkSave/$1');

    // Category, Subcategory, Product Type, Attributes
    $routes->get('catalog/(:segment)', 'Admin\CatalogManagementController::index/$1');

    // Add
    $routes->get('catalog/(:segment)/add', 'Admin\CatalogManagementController::add/$1');

    // Edit
    $routes->get('catalog/(:segment)/edit/(:num)', 'Admin\CatalogManagementController::edit/$1/$2');

    // Save new record
    $routes->post('catalog/(:segment)/save', 'Admin\CatalogManagementController::save/$1');

    // Update existing record
    $routes->post('catalog/(:segment)/save/(:num)', 'Admin\CatalogManagementController::save/$1/$2');

    // Delete
    $routes->post('catalog/(:segment)/delete/(:num)', 'Admin\CatalogManagementController::delete/$1/$2');

});


// =========================================
// ADMIN ORDER MANAGEMENT
// =========================================

$routes->get('orders', 'ShippingAdminOrders::index');

$routes->get('orders/list-data', 'ShippingAdminOrders::listData');

$routes->get('orders/(:num)', 'ShippingAdminOrders::details/$1');


$routes->get(
    'admin/cancellation-requests',
    'Admin\CancellationRequests::index'
);

$routes->get(
    'admin/cancellation-requests/(:num)',
    'Admin\CancellationRequests::details/$1'
);

$routes->post(
    'admin/cancellation-requests/review/(:num)',
    'Admin\CancellationRequests::review/$1'
);

$routes->group('admin', function($routes) {

    $routes->get('home-banners', 'Admin\HomeBanners::index');
    $routes->get('home-banners/create', 'Admin\HomeBanners::create');
    $routes->post('home-banners/store', 'Admin\HomeBanners::store');

    $routes->get('home-banners/edit/(:num)', 'Admin\HomeBanners::edit/$1');
    $routes->post('home-banners/update/(:num)', 'Admin\HomeBanners::update/$1');

    $routes->post('home-banners/delete/(:num)', 'Admin\HomeBanners::delete/$1');

});

$routes->get('shared-images/(:any)', 'SharedImages::show/$1');