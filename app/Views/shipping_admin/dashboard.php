
<?= $this->extend('shipping_admin/layouts/main') ?>

<?= $this->section('content') ?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
    <div>
        <h3 class="admin-page-title">Dashboard Overview</h3>
        <p class="admin-page-subtitle mb-0">
            Welcome back, <?= esc(session()->get('shipping_admin_username') ?? 'Admin') ?>.
            Here's your store overview.
        </p>
    </div>

    <div class="text-secondary small mt-2 mt-md-0">
        <i class="bi bi-calendar3 me-1"></i>
        <?= date('d M Y') ?>
    </div>
</div>

<!-- Statistics -->
<div class="row g-3 mb-4">

    <div class="col-12 col-sm-6 col-xl-3">
        <div class="admin-card p-3 h-100">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="text-secondary small mb-2">Total Orders</div>
                    <div class="fs-3 fw-bold">—</div>
                    <div class="small text-secondary">All store orders</div>
                </div>
                <div class="admin-stat-icon bg-primary-subtle text-primary">
                    <i class="bi bi-bag-check"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
        <div class="admin-card p-3 h-100">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="text-secondary small mb-2">Pending Orders</div>
                    <div class="fs-3 fw-bold">—</div>
                    <div class="small text-secondary">Awaiting processing</div>
                </div>
                <div class="admin-stat-icon bg-warning-subtle text-warning">
                    <i class="bi bi-hourglass-split"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
        <div class="admin-card p-3 h-100">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="text-secondary small mb-2">Total Sellers</div>
                    <div class="fs-3 fw-bold">—</div>
                    <div class="small text-secondary">Registered sellers</div>
                </div>
                <div class="admin-stat-icon bg-success-subtle text-success">
                    <i class="bi bi-shop"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
        <div class="admin-card p-3 h-100">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="text-secondary small mb-2">Total Customers</div>
                    <div class="fs-3 fw-bold">—</div>
                    <div class="small text-secondary">Registered customers</div>
                </div>
                <div class="admin-stat-icon bg-info-subtle text-info">
                    <i class="bi bi-people"></i>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- Quick actions -->
<div class="admin-card p-3 p-md-4 mb-4">
    <h5 class="fw-bold mb-3">Quick Actions</h5>

    <div class="row g-3">

        <div class="col-12 col-md-6 col-xl-3">
            <a href="<?= site_url('shipping-admin/orders') ?>"
               class="btn btn-primary w-100 py-3">
                <i class="bi bi-bag-check me-2"></i>
                Manage Orders
            </a>
        </div>

        <div class="col-12 col-md-6 col-xl-3">
            <a href="<?= site_url('shipping-admin/shipping') ?>"
               class="btn btn-outline-primary w-100 py-3">
                <i class="bi bi-truck me-2"></i>
                Shipping Management
            </a>
        </div>

        <div class="col-12 col-md-6 col-xl-3">
            <a href="<?= site_url('shipping-admin/products') ?>"
               class="btn btn-outline-secondary w-100 py-3">
                <i class="bi bi-box-seam me-2"></i>
                Manage Products
            </a>
        </div>

        <div class="col-12 col-md-6 col-xl-3">
            <a href="<?= site_url('shipping-admin/sellers') ?>"
               class="btn btn-outline-secondary w-100 py-3">
                <i class="bi bi-shop me-2"></i>
                Manage Sellers
            </a>
        </div>

    </div>
</div>

<!-- Recent orders placeholder -->
<div class="admin-card">

    <div class="p-3 p-md-4 border-bottom d-flex flex-wrap
                justify-content-between align-items-center gap-2">
        <div>
            <h5 class="fw-bold mb-1">Recent Orders</h5>
            <div class="small text-secondary">
                Latest customer orders will appear here.
            </div>
        </div>

        <a href="<?= site_url('shipping-admin/orders') ?>"
           class="btn btn-sm btn-outline-primary">
            View All Orders
            <i class="bi bi-arrow-right ms-1"></i>
        </a>
    </div>

    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Order Number</th>
                    <th>Customer</th>
                    <th>Date</th>
                    <th>Payment</th>
                    <th>Status</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td colspan="6" class="text-center text-secondary py-5">
                        Orders will be connected here after the Orders page
                        and its database query are implemented.
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

</div>

<?= $this->endSection() ?>
