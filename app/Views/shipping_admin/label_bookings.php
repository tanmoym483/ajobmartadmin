
<?= $this->extend('shipping_admin/layouts/main') ?>

<?= $this->section('content') ?>

<?php
$statusLabels = [
    'pending_review' => 'Pending Review',
    'approved'       => 'Approved',
    'rejected'       => 'Rejected',
    'booked'          => 'Booked',
    'shipped'         => 'Shipped',
    'delivered'       => 'Delivered',
    'cancelled'       => 'Cancelled',
];

$statusClasses = [
    'pending_review' => 'bg-warning text-dark',
    'approved'       => 'bg-info text-dark',
    'rejected'       => 'bg-danger',
    'booked'          => 'bg-primary',
    'shipped'         => 'bg-primary',
    'delivered'       => 'bg-success',
    'cancelled'       => 'bg-secondary',
];
?>

<div class="container-fluid">

    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <h4 class="mb-1">Seller Shipping Labels</h4>
            <p class="text-muted mb-0">
                Review seller-generated labels and manage India Post bookings.
            </p>
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

    <div class="card border-0 shadow-sm">

        <div class="card-header bg-white py-3">

            <form method="get"
                  action="<?= site_url('shipping-admin/label-bookings') ?>"
                  class="row g-2 align-items-center">

                <div class="col-md-4">
                    <select name="status" class="form-select">
                        <option value="">All Statuses</option>

                        <?php foreach ($statusLabels as $key => $label): ?>
                            <option value="<?= esc($key) ?>"
                                <?= ($status ?? '') === $key ? 'selected' : '' ?>>
                                <?= esc($label) ?>
                            </option>
                        <?php endforeach; ?>

                    </select>
                </div>

                <div class="col-auto">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-filter me-1"></i>
                        Filter
                    </button>

                    <a href="<?= site_url('shipping-admin/label-bookings') ?>"
                       class="btn btn-outline-secondary">
                        Reset
                    </a>
                </div>

            </form>

        </div>

        <div class="card-body p-0">

            <div class="table-responsive">

                <table class="table table-hover align-middle mb-0">

                    <thead class="table-light">
                        <tr>
                            <th>Label Number</th>
                            <th>Order Number</th>
                            <th>Seller ID</th>
                            <th>Generated</th>
                            <th>Consignment Number</th>
                            <th>Status</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php if (!empty($bookings)): ?>

                        <?php foreach ($bookings as $booking): ?>

                            <?php
                            $currentStatus = $booking['admin_status'] ?? 'pending_review';
                            $badgeClass = $statusClasses[$currentStatus] ?? 'bg-secondary';
                            ?>

                            <tr>

                                <td>
                                    <strong>
                                        <?= esc($booking['label_number'] ?? '-') ?>
                                    </strong>
                                </td>

                                <td>
                                    <?= esc($booking['order_number'] ?? $booking['order_id']) ?>
                                </td>

                                <td>
                                    <?= esc($booking['seller_id']) ?>
                                </td>

                                <td>
                                    <?= !empty($booking['generated_at'])
                                        ? esc(date('d M Y, h:i A', strtotime($booking['generated_at'])))
                                        : '-' ?>
                                </td>

                                <td>
                                    <?= esc($booking['consignment_number'] ?? '-') ?>
                                </td>

                                <td>
                                    <span class="badge <?= esc($badgeClass) ?>">
                                        <?= esc($statusLabels[$currentStatus] ?? ucfirst($currentStatus)) ?>
                                    </span>
                                </td>

                                <td class="text-end">
                                    <a href="<?= site_url('shipping-admin/label-bookings/' . (int) $booking['id']) ?>"
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye me-1"></i>
                                        Review
                                    </a>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <i class="bi bi-inbox fs-2 text-muted"></i>
                                <p class="text-muted mb-0 mt-2">
                                    No shipping labels found.
                                </p>
                            </td>
                        </tr>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</div>

<?= $this->endSection() ?>
