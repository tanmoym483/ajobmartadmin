
<?= $this->extend('shipping_admin/layouts/main') ?>

<?= $this->section('content') ?>

<?php
$orderStatuses = [
    'pending',
    'confirmed',
    'processing',
    'shipped',
    'delivered',
    'cancelled',
];

$paymentStatuses = [
    'pending',
    'paid',
    'failed',
    'refunded',
];

function orderStatusBadge($status)
{
    $status = strtolower((string) $status);

    $classes = [
        'pending' => 'bg-warning text-dark',
        'confirmed' => 'bg-info text-dark',
        'processing' => 'bg-primary',
        'shipped' => 'bg-primary',
        'delivered' => 'bg-success',
        'cancelled' => 'bg-danger',
        'paid' => 'bg-success',
        'failed' => 'bg-danger',
        'refunded' => 'bg-secondary',
    ];

    $class = $classes[$status] ?? 'bg-secondary';

    return '<span class="badge ' . $class . '">' . esc(ucfirst($status ?: 'Unknown')) . '</span>';
}
?>

<div class="container-fluid">

    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">All Orders</h4>
            <p class="text-muted mb-0">Manage and monitor customer orders across all sellers.</p>
        </div>
    </div>

    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success">
            <?= esc(session()->getFlashdata('success')) ?>
        </div>
    <?php endif; ?>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger">
            <?= esc(session()->getFlashdata('error')) ?>
        </div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">

            <form method="get" action="<?= site_url('orders') ?>">
                <div class="row g-2">

                    <div class="col-lg-5 col-md-6">
                        <input
                            type="text"
                            name="search"
                            class="form-control"
                            placeholder="Search order number, customer, mobile or email"
                            value="<?= esc($search) ?>">
                    </div>

                    <div class="col-lg-2 col-md-3">
                        <select name="order_status" class="form-select">
                            <option value="">All Order Status</option>
                            <?php foreach ($orderStatuses as $status): ?>
                                <option value="<?= esc($status) ?>"
                                    <?= $orderStatus === $status ? 'selected' : '' ?>>
                                    <?= esc(ucfirst($status)) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-lg-2 col-md-3">
                        <select name="payment_status" class="form-select">
                            <option value="">All Payment Status</option>
                            <?php foreach ($paymentStatuses as $status): ?>
                                <option value="<?= esc($status) ?>"
                                    <?= $paymentStatus === $status ? 'selected' : '' ?>>
                                    <?= esc(ucfirst($status)) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-lg-3 col-md-12 d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-search"></i> Search
                        </button>

                        <a href="<?= site_url('orders') ?>"
                           class="btn btn-outline-secondary">
                            Reset
                        </a>
                    </div>

                </div>
            </form>

        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3">
            <strong>Order List</strong>
        </div>

        <div class="card-body p-0">

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">

                    <thead class="table-light">
                        <tr>
                            <th>Order</th>
                            <th>Customer</th>
                            <th>Order Date</th>
                            <th>Items</th>
                            <th>Sellers</th>
                            <th>Amount</th>
                            <th>Payment</th>
                            <th>Order Status</th>
                            <th>Shipping</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php if (!empty($orders)): ?>

                        <?php foreach ($orders as $row): ?>

                            <tr>
                                <td>
                                    <strong><?= esc($row['order_number']) ?></strong>
                                    <div class="small text-muted">
                                        ID: <?= (int) $row['id'] ?>
                                    </div>
                                </td>

                                <td>
                                    <strong><?= esc($row['customer_name'] ?? 'Customer') ?></strong>
                                    <div class="small text-muted">
                                        <?= esc($row['customer_mobile'] ?? '') ?>
                                    </div>
                                </td>

                                <td>
                                    <?= !empty($row['created_at'])
                                        ? esc(date('d M Y, h:i A', strtotime($row['created_at'])))
                                        : '-' ?>
                                </td>

                                <td>
                                    <?= (int) $row['total_quantity'] ?> qty
                                    <div class="small text-muted">
                                        <?= (int) $row['item_lines'] ?> product lines
                                    </div>
                                </td>

                                <td>
                                    <?= (int) $row['seller_count'] ?>
                                </td>

                                <td>
                                    <strong>₹<?= number_format((float) $row['total_amount'], 2) ?></strong>
                                </td>

                                <td>
                                    <div><?= esc(strtoupper($row['payment_method'] ?? '-')) ?></div>
                                    <div class="mt-1">
                                        <?= orderStatusBadge($row['payment_status'] ?? '') ?>
                                    </div>
                                </td>

                                <td>
                                    <?= orderStatusBadge($row['order_status'] ?? '') ?>
                                </td>

                                <td>
                                    <?php if (!empty($row['booking_statuses'])): ?>
                                        <span class="small">
                                            <?= esc($row['booking_statuses']) ?>
                                        </span>
                                    <?php elseif (!empty($row['seller_statuses'])): ?>
                                        <span class="small">
                                            <?= esc($row['seller_statuses']) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted small">Not started</span>
                                    <?php endif; ?>
                                </td>

                                <td class="text-end">
                                    <a href="<?= site_url('orders/' . $row['id']) ?>"
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye"></i> Details
                                    </a>
                                </td>
                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="10" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                No orders found.
                            </td>
                        </tr>

                    <?php endif; ?>

                    </tbody>
                </table>
            </div>

        </div>

        <?php if (!empty($pagerLinks)): ?>
            <div class="card-footer bg-white">
                <?= $pagerLinks ?>
            </div>
        <?php endif; ?>

    </div>

</div>

<?= $this->endSection() ?>
