
<?= $this->extend('shipping_admin/layouts/main') ?>

<?= $this->section('content') ?>

<?php
$status = $booking['admin_status'] ?? 'pending_review';

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
            <h4 class="mb-1">Shipping Label Review</h4>
            <p class="text-muted mb-0">
                <?= esc($booking['label_number'] ?? '') ?>
            </p>
        </div>

        <a href="<?= site_url('shipping-admin/label-bookings') ?>"
           class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>
            Back to Labels
        </a>

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

    <div class="row g-4">

        <div class="col-lg-5">

            <div class="card border-0 shadow-sm mb-4">

                <div class="card-header bg-white py-3">
                    <h6 class="mb-0">Label Information</h6>
                </div>

                <div class="card-body">

                    <div class="mb-3">
                        <small class="text-muted">Label Number</small>
                        <div class="fw-semibold">
                            <?= esc($booking['label_number'] ?? '-') ?>
                        </div>
                    </div>

                    <div class="mb-3">
                        <small class="text-muted">Order Number</small>
                        <div class="fw-semibold">
                            <?= esc($booking['order_number'] ?? $booking['order_id']) ?>
                        </div>
                    </div>

                    <div class="mb-3">
                        <small class="text-muted">Seller ID</small>
                        <div class="fw-semibold">
                            <?= esc($booking['seller_id']) ?>
                        </div>
                    </div>

                    <div class="mb-3">
                        <small class="text-muted">Label Generated</small>
                        <div>
                            <?= !empty($booking['generated_at'])
                                ? esc(date('d M Y, h:i A', strtotime($booking['generated_at'])))
                                : '-' ?>
                        </div>
                    </div>

                    <div class="mb-3">
                        <small class="text-muted">Payment Method</small>
                        <div>
                            <?= esc(strtoupper($booking['payment_method'] ?? '-')) ?>
                        </div>
                    </div>

                    <div class="mb-3">
                        <small class="text-muted">Payment Status</small>
                        <div>
                            <?= esc(ucfirst($booking['payment_status'] ?? '-')) ?>
                        </div>
                    </div>

                    <div class="mb-3">
                        <small class="text-muted">Admin Status</small>
                        <div>
                            <span class="badge <?= esc($statusClasses[$status] ?? 'bg-secondary') ?>">
                                <?= esc($statusLabels[$status] ?? ucfirst($status)) ?>
                            </span>
                        </div>
                    </div>

                    <a href="<?= site_url('shipping-admin/label-bookings/' . (int) $booking['id'] . '/pdf') ?>"
                       target="_blank"
                       class="btn btn-outline-primary w-100">
                        <i class="bi bi-file-earmark-pdf me-1"></i>
                        View Seller Shipping Label
                    </a>

                </div>

            </div>

            <?php if ($status === 'pending_review'): ?>

                <div class="card border-0 shadow-sm mb-4">

                    <div class="card-header bg-white py-3">
                        <h6 class="mb-0">Admin Review</h6>
                    </div>

                    <div class="card-body">

                        <form method="post"
                              action="<?= site_url('shipping-admin/label-bookings/' . (int) $booking['id'] . '/approve') ?>"
                              class="mb-3">

                            <?= csrf_field() ?>

                            <label class="form-label">Admin Note</label>

                            <textarea name="admin_note"
                                      class="form-control mb-3"
                                      rows="2"
                                      placeholder="Optional approval note"></textarea>

                            <button type="submit" class="btn btn-success w-100">
                                <i class="bi bi-check-circle me-1"></i>
                                Approve Label
                            </button>

                        </form>

                        <hr>

                        <form method="post"
                              action="<?= site_url('shipping-admin/label-bookings/' . (int) $booking['id'] . '/reject') ?>">

                            <?= csrf_field() ?>

                            <label class="form-label">Rejection Reason</label>

                            <textarea name="rejection_reason"
                                      class="form-control mb-3"
                                      rows="3"
                                      placeholder="Explain why this label is rejected"
                                      required></textarea>

                            <button type="submit" class="btn btn-danger w-100">
                                <i class="bi bi-x-circle me-1"></i>
                                Reject Label
                            </button>

                        </form>

                    </div>

                </div>

            <?php elseif ($status === 'rejected'): ?>

                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white py-3">
                        <h6 class="mb-0 text-danger">Rejection Details</h6>
                    </div>

                    <div class="card-body">
                        <?= nl2br(esc($booking['rejection_reason'] ?? 'No reason provided.')) ?>
                    </div>
                </div>

            <?php endif; ?>

        </div>


        <div class="col-lg-7">

            <?php if ($status === 'approved'): ?>

                <div class="card border-0 shadow-sm">

                    <div class="card-header bg-white py-3">
                        <h6 class="mb-0">
                            <i class="bi bi-truck me-1"></i>
                            India Post Manual Booking
                        </h6>
                    </div>

                    <div class="card-body">

                        <div class="alert alert-info">
                            Book the parcel manually at India Post first.
                            Enter the official information from the postal receipt below.
                        </div>

                        <form method="post"
                              action="<?= site_url('shipping-admin/label-bookings/' . (int) $booking['id'] . '/book') ?>"
                              enctype="multipart/form-data">

                            <?= csrf_field() ?>

                            <div class="row g-3">

                                <div class="col-md-6">
                                    <label class="form-label">India Post Service *</label>

                                    <select name="booking_service"
                                            class="form-select"
                                            required>

                                        <option value="">Select Service</option>

                                        <option value="india_post_parcel">
                                            Parcel Retail
                                        </option>

                                        <option value="india_post_speed_post">
                                            Speed Post Parcel
                                        </option>

                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Booking Date *</label>

                                    <input type="date"
                                           name="booking_date"
                                           class="form-control"
                                           value="<?= date('Y-m-d') ?>"
                                           required>
                                </div>

                                <div class="col-12">
                                    <label class="form-label">
                                        Official Consignment Number *
                                    </label>

                                    <input type="text"
                                           name="consignment_number"
                                           class="form-control"
                                           maxlength="100"
                                           placeholder="Enter actual India Post tracking number"
                                           required>
                                </div>

                                <div class="col-12">
                                    <label class="form-label">Booking Post Office *</label>

                                    <input type="text"
                                           name="booking_post_office"
                                           class="form-control"
                                           maxlength="150"
                                           placeholder="Post office name"
                                           required>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Parcel Weight (grams) *</label>

                                    <input type="number"
                                           name="parcel_weight_grams"
                                           class="form-control"
                                           min="1"
                                           step="1"
                                           required>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Postage Amount (₹) *</label>

                                    <input type="number"
                                           name="postage_amount"
                                           class="form-control"
                                           min="0"
                                           step="0.01"
                                           required>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label">Length (cm)</label>

                                    <input type="number"
                                           name="parcel_length_cm"
                                           class="form-control"
                                           min="0"
                                           step="0.01">
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label">Width (cm)</label>

                                    <input type="number"
                                           name="parcel_width_cm"
                                           class="form-control"
                                           min="0"
                                           step="0.01">
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label">Height (cm)</label>

                                    <input type="number"
                                           name="parcel_height_cm"
                                           class="form-control"
                                           min="0"
                                           step="0.01">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">COD Amount (₹)</label>

                                    <input type="number"
                                           name="cod_amount"
                                           class="form-control"
                                           min="0"
                                           step="0.01"
                                           value="<?= esc($booking['payment_method'] === 'cod' ? '' : '0') ?>"
                                           placeholder="0.00">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Postal Receipt</label>

                                    <input type="file"
                                           name="postal_receipt"
                                           class="form-control"
                                           accept=".pdf,.jpg,.jpeg,.png">

                                    <small class="text-muted">
                                        PDF, JPG or PNG. Maximum 5 MB.
                                    </small>
                                </div>

                            </div>

                            <hr>

                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-save me-1"></i>
                                Save India Post Booking
                            </button>

                        </form>

                    </div>
                </div>

            <?php elseif (in_array($status, ['booked', 'shipped', 'delivered'], true)): ?>

                <div class="card border-0 shadow-sm mb-4">

                    <div class="card-header bg-white py-3">
                        <h6 class="mb-0">India Post Booking Information</h6>
                    </div>

                    <div class="card-body">

                        <div class="row g-3">

                            <div class="col-md-6">
                                <small class="text-muted">Service</small>
                                <div class="fw-semibold">
                                    <?= esc($booking['booking_service'] === 'india_post_parcel'
                                        ? 'Parcel Retail'
                                        : 'Speed Post Parcel') ?>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <small class="text-muted">Consignment Number</small>
                                <div class="fw-semibold">
                                    <?= esc($booking['consignment_number'] ?? '-') ?>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <small class="text-muted">Booking Date</small>
                                <div>
                                    <?= esc($booking['booking_date'] ?? '-') ?>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <small class="text-muted">Post Office</small>
                                <div>
                                    <?= esc($booking['booking_post_office'] ?? '-') ?>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <small class="text-muted">Weight</small>
                                <div>
                                    <?= esc($booking['parcel_weight_grams'] ?? '-') ?> grams
                                </div>
                            </div>

                            <div class="col-md-6">
                                <small class="text-muted">Postage</small>
                                <div>
                                    ₹<?= number_format((float) ($booking['postage_amount'] ?? 0), 2) ?>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <small class="text-muted">COD Amount</small>
                                <div>
                                    ₹<?= number_format((float) ($booking['cod_amount'] ?? 0), 2) ?>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <small class="text-muted">Booked At</small>
                                <div>
                                    <?= !empty($booking['booked_at'])
                                        ? esc(date('d M Y, h:i A', strtotime($booking['booked_at'])))
                                        : '-' ?>
                                </div>
                            </div>

                        </div>

                        <?php if (!empty($booking['postal_receipt_path'])): ?>
                            <hr>

                            <a href="<?= site_url('shipping-admin/label-bookings/' . (int) $booking['id'] . '/receipt') ?>"
                               target="_blank"
                               class="btn btn-outline-primary">
                                <i class="bi bi-file-earmark-pdf me-1"></i>
                                View Postal Receipt
                            </a>
                        <?php endif; ?>

                    </div>
                </div>

                <?php if (in_array($status, ['booked', 'shipped'], true)): ?>

                    <div class="card border-0 shadow-sm">

                        <div class="card-header bg-white py-3">
                            <h6 class="mb-0">Update Shipment Status</h6>
                        </div>

                        <div class="card-body">

                            <form method="post"
                                  action="<?= site_url('shipping-admin/label-bookings/' . (int) $booking['id'] . '/status') ?>">

                                <?= csrf_field() ?>

                                <div class="mb-3">
                                    <label class="form-label">New Status</label>

                                    <select name="admin_status" class="form-select" required>

                                        <?php if ($status === 'booked'): ?>
                                            <option value="shipped">Shipped / Dispatched</option>
                                        <?php endif; ?>

                                        <?php if ($status === 'shipped'): ?>
                                            <option value="delivered">Delivered</option>
                                        <?php endif; ?>

                                    </select>
                                </div>

                                <button type="submit" class="btn btn-success w-100">
                                    Update Shipment Status
                                </button>

                            </form>

                        </div>

                    </div>

                <?php endif; ?>

            <?php endif; ?>

        </div>

    </div>

</div>

<?= $this->endSection() ?>
