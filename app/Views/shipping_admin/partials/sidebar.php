
<?php
$currentPath = trim(uri_string(), '/');

$menuItems = [
    [
        'label' => 'Dashboard',
        'url' => 'shipping-admin',
        'icon' => 'bi-speedometer2',
        'match' => 'shipping-admin',
    ],
    [
        'label' => 'Orders',
        'url' => 'orders',
        'icon' => 'bi-bag-check',
        'match' => 'orders',
    ],
    [
        'label' => 'Products',
        'url' => 'shipping-admin/products',
        'icon' => 'bi-box-seam',
        'match' => 'shipping-admin/products',
    ],
    [
        'label' => 'Sellers',
        'url' => 'shipping-admin/sellers',
        'icon' => 'bi-shop',
        'match' => 'shipping-admin/sellers',
    ],
    [
        'label' => 'Customers',
        'url' => 'shipping-admin/customers',
        'icon' => 'bi-people',
        'match' => 'shipping-admin/customers',
    ],
];

$shippingItems = [
    [
        'label' => 'Shipping Management',
        'url' => 'shipping-admin/shipping',
        'icon' => 'bi-truck',
        'match' => 'shipping-admin/shipping',
    ],
    [
        'label' => 'Seller Shipping Labels',
        'url' => 'shipping-admin/label-bookings',
        'icon' => 'bi-box-seam',
        'match' => 'shipping-admin/label-bookings',
    ],
    [
        'label' => 'Label Design Settings',
        'url' => 'shipping-admin/label-settings',
        'icon' => 'bi-printer',
        'match' => 'shipping-admin/label-settings',
    ],
];
?>

<aside class="admin-sidebar" id="adminSidebar">

    <a href="<?= site_url('shipping-admin') ?>" class="admin-brand">
        <i class="bi bi-shop-window me-2"></i>
        Ajobmart <span>Admin</span>
    </a>

    <div class="sidebar-label">Main Menu</div>

    <?php foreach ($menuItems as $item): ?>
        <a href="<?= site_url($item['url']) ?>"
           class="sidebar-link <?= $currentPath === $item['match'] ? 'active' : '' ?>">
            <i class="bi <?= esc($item['icon']) ?>"></i>
            <span><?= esc($item['label']) ?></span>
        </a>
    <?php endforeach; ?>

    <div class="sidebar-label">Operations</div>

    <?php foreach ($shippingItems as $item): ?>
        <a href="<?= site_url($item['url']) ?>"
           class="sidebar-link <?= (
               $currentPath === $item['match']
               || str_starts_with($currentPath, $item['match'] . '/')
           ) ? 'active' : '' ?>">
            <i class="bi <?= esc($item['icon']) ?>"></i>
            <span><?= esc($item['label']) ?></span>
        </a>
    <?php endforeach; ?>

    <div class="sidebar-label">System</div>

    <a href="<?= site_url('shipping-admin/settings') ?>"
       class="sidebar-link <?= $currentPath === 'shipping-admin/settings' ? 'active' : '' ?>">
        <i class="bi bi-gear"></i>
        <span>Settings</span>
    </a>

</aside>
