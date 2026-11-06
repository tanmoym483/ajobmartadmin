
<?= $this->extend('shipping_admin/layouts/main') ?>

<?= $this->section('content') ?>

<?php
$money = static function ($amount) {
    return '₹' . number_format((float) $amount, 2);
};

$statusClass = static function ($status) {
    $status = strtolower((string) $status);

    $classes = [
        'pending' => 'bg-warning text-dark',
        'pending_review' => 'bg-warning text-dark',
        'approved' => 'bg-info text-dark',
        'processing' => 'bg-primary',
        'booked' => 'bg-primary',
        'shipped' => 'bg-primary',
        'delivered' => 'bg-success',
        'rejected' => 'bg-danger',
        'cancelled' => 'bg-danger',
        'paid' => 'bg-success',
        'failed' => 'bg-danger',
        'refunded' => 'bg-secondary',
        'generated' => 'bg-info text-dark',
        'printed' => 'bg-secondary',
    ];

    return $classes[$status] ?? 'bg-secondary';
};
?>

<div class="container-fluid">

    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <h4 class="mb-1">Order Details</h4>
            <p class="text-muted mb-0">
                Order: <?= esc($order['order_number']) ?>
            </p>
        </div>

        <a href="<?= site_url('orders') ?>"
           class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back to Orders
        </a>
    </div>

    <div class="row g-3">

        <!-- Order and customer information -->
        <div class="col-lg-8">

            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white py-3">
                    <strong>Order Information</strong>
                </div>

                <div class="card-body">
                    <div class="row g-3">

                        <div class="col-md-4">
                            <div class="small text-muted">Order Number</div>
                            <strong><?= esc($order['order_number']) ?></strong>
                        </div>

                        <div class="col-md-4">
                            <div class="small text-muted">Order ID</div>
                            <strong>#<?= (int) $order['id'] ?></strong>
                        </div>

                        <div class="col-md-4">
                            <div class="small text-muted">Order Date</div>
                            <strong>
                                <?= !empty($order['created_at'])
                                    ? esc(date('d M Y, h:i A', strtotime($order['created_at'])))
                                    : '-' ?>
                            </strong>
                        </div>

                        <div class="col-md-4">
                            <div class="small text-muted">Order Status</div>
                            <span class="badge <?= $statusClass($order['order_status']) ?>">
                                <?= esc(ucfirst($order['order_status'] ?? 'Unknown')) ?>
                            </span>
                        </div>

                        <div class="col-md-4">
                            <div class="small text-muted">Payment Method</div>
                            <strong><?= esc(strtoupper($order['payment_method'] ?? '-')) ?></strong>
                        </div>

                        <div class="col-md-4">
                            <div class="small text-muted">Payment Status</div>
                            <span class="badge <?= $statusClass($order['payment_status']) ?>">
                                <?= esc(ucfirst($order['payment_status'] ?? 'Unknown')) ?>
                            </span>
                        </div>

                        <div class="col-md-6">
                            <div class="small text-muted">Payment Gateway</div>
                            <strong><?= esc($order['payment_gateway'] ?: '-') ?></strong>
                        </div>

                        <div class="col-md-6">
                            <div class="small text-muted">Delivery Address ID</div>
                            <strong><?= (int) ($order['address_id'] ?? 0) ?></strong>
                        </div>

                        <?php if (!empty($order['razorpay_order_id'])): ?>
                            <div class="col-md-6">
                                <div class="small text-muted">Razorpay Order ID</div>
                                <small><?= esc($order['razorpay_order_id']) ?></small>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($order['razorpay_payment_id'])): ?>
                            <div class="col-md-6">
                                <div class="small text-muted">Razorpay Payment ID</div>
                                <small><?= esc($order['razorpay_payment_id']) ?></small>
                            </div>
                        <?php endif; ?>

                    </div>
                </div>
            </div>

            <!-- Customer information -->
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white py-3">
                    <strong>Customer Information</strong>
                </div>

                <div class="card-body">
                    <div class="row g-3">

                        <div class="col-md-4">
                            <div class="small text-muted">Customer Name</div>
                            <strong><?= esc($order['customer_name'] ?? '-') ?></strong>
                        </div>

                        <div class="col-md-4">
                            <div class="small text-muted">Mobile</div>
                            <strong><?= esc($order['customer_mobile'] ?? '-') ?></strong>
                        </div>

                        <div class="col-md-4">
                            <div class="small text-muted">Email</div>
                            <strong><?= esc($order['customer_email'] ?? '-') ?></strong>
                        </div>

                        <div class="col-md-6">
                            <div class="small text-muted">Customer User ID</div>
                            <strong><?= esc($order['customer_user_id'] ?? '-') ?></strong>
                        </div>

                    </div>
                </div>
            </div>

            <!-- Ordered products -->
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white py-3">
                    <strong>Ordered Products</strong>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">

                            <thead class="table-light">
                                <tr>
                                    <th>Product</th>
                                    <th>Seller ID</th>
                                    <th>SKU</th>
                                    <th>Unit Price</th>
                                    <th>Quantity</th>
                                    <th>Shipping Allowance</th>
                                    <th>Line Total</th>
                                </tr>
                            </thead>

                            <tbody>

                            <?php if (!empty($items)): ?>

                                <?php foreach ($items as $item): ?>
                                    <tr>
                                        <td>
                                            <strong>
                                                <?= esc(
                                                    $item['product_name']
                                                    ?? $item['current_product_name']
                                                    ?? 'Product'
                                                ) ?>
                                            </strong>
                                            <div class="small text-muted">
                                                Product ID: <?= (int) $item['product_id'] ?>
                                            </div>
                                        </td>

                                        <td>
                                            <?= isset($item['seller_id'])
                                                ? (int) $item['seller_id']
                                                : '-' ?>
                                        </td>

                                        <td><?= esc($item['sku'] ?? '-') ?></td>

                                        <td><?= $money($item['unit_price']) ?></td>

                                        <td><?= (int) $item['quantity'] ?></td>

                                        <td>
                                            <?= $money(
                                                (float) $item['unit_shipping_allowance']
                                                * (int) $item['quantity']
                                            ) ?>
                                        </td>

                                        <td>
                                            <strong><?= $money($item['line_total']) ?></strong>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>

                            <?php else: ?>

                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">
                                        No products found for this order.
                                    </td>
                                </tr>

                            <?php endif; ?>

                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Seller fulfillment -->
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white py-3">
                    <strong>Seller Fulfillment Status</strong>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">

                            <thead class="table-light">
                                <tr>
                                    <th>Seller ID</th>
                                    <th>Seller Status</th>
                                    <th>Courier</th>
                                    <th>Tracking Number</th>
                                    <th>Shipped At</th>
                                    <th>Delivered At</th>
                                </tr>
                            </thead>

                            <tbody>

                            <?php if (!empty($fulfillments)): ?>

                                <?php foreach ($fulfillments as $fulfillment): ?>
                                    <tr>
                                        <td><?= (int) $fulfillment['seller_id'] ?></td>

                                        <td>
                                            <span class="badge <?= $statusClass($fulfillment['seller_status']) ?>">
                                                <?= esc(ucfirst($fulfillment['seller_status'])) ?>
                                            </span>
                                        </td>

                                        <td><?= esc($fulfillment['courier_name'] ?: '-') ?></td>

                                        <td>
                                            <?php if (!empty($fulfillment['tracking_url'])): ?>
                                                <a href="<?= esc($fulfillment['tracking_url']) ?>"
                                                   target="_blank"
                                                   rel="noopener noreferrer">
                                                    <?= esc($fulfillment['tracking_number'] ?: 'Track') ?>
                                                </a>
                                            <?php else: ?>
                                                <?= esc($fulfillment['tracking_number'] ?: '-') ?>
                                            <?php endif; ?>
                                        </td>

                                        <td><?= esc($fulfillment['shipped_at'] ?: '-') ?></td>
                                        <td><?= esc($fulfillment['delivered_at'] ?: '-') ?></td>
                                    </tr>
                                <?php endforeach; ?>

                            <?php else: ?>

                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">
                                        No seller fulfillment records yet.
                                    </td>
                                </tr>

                            <?php endif; ?>

                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Shipping labels and admin booking -->
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white py-3">
                    <strong>Shipping Labels & Postal Bookings</strong>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">

                            <thead class="table-light">
                                <tr>
                                    <th>Seller</th>
                                    <th>Label Number</th>
                                    <th>Generated</th>
                                    <th>Label Status</th>
                                    <th>Admin Status</th>
                                    <th>Consignment Number</th>
                                    <th>Action</th>
                                </tr>
                            </thead>

                            <tbody>

                            <?php if (!empty($labels)): ?>

                                <?php foreach ($labels as $label): ?>
                                    <tr>
                                        <td><?= (int) $label['seller_id'] ?></td>

                                        <td><?= esc($label['label_number']) ?></td>

                                        <td>
                                            <?= !empty($label['generated_at'])
                                                ? esc(date('d M Y', strtotime($label['generated_at'])))
                                                : '-' ?>
                                        </td>

                                        <td>
                                            <span class="badge <?= $statusClass($label['label_status']) ?>">
                                                <?= esc(ucfirst($label['label_status'])) ?>
                                            </span>
                                        </td>

                                        <td>
                                            <?php if (!empty($label['admin_status'])): ?>
                                                <span class="badge <?= $statusClass($label['admin_status']) ?>">
                                                    <?= esc(ucwords(str_replace('_', ' ', $label['admin_status']))) ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted">Not registered</span>
                                            <?php endif; ?>
                                        </td>

                                        <td>
                                            <?= esc($label['consignment_number'] ?: '-') ?>
                                        </td>

                                        <td>
                                            <?php if (!empty($label['booking_id'])): ?>
                                                <a href="<?= site_url('shipping-admin/label-bookings/' . $label['booking_id']) ?>"
                                                   class="btn btn-sm btn-outline-primary">
                                                    View Booking
                                                </a>
                                            <?php else: ?>
                                                <a href="<?= site_url('shipping-admin/label-bookings') ?>"
                                                   class="btn btn-sm btn-outline-secondary">
                                                    Label Queue
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>

                            <?php else: ?>

                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">
                                        No shipping labels generated yet.
                                    </td>
                                </tr>

                            <?php endif; ?>

                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>

        <!-- Payment summary -->
        <div class="col-lg-4">

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <strong>Payment Summary</strong>
                </div>

                <div class="card-body">

                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Subtotal</span>
                        <strong><?= $money($order['subtotal']) ?></strong>
                    </div>

                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Shipping Charge</span>
                        <strong><?= $money($order['shipping_charge']) ?></strong>
                    </div>

                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Included Shipping</span>
                        <strong><?= $money($order['included_shipping']) ?></strong>
                    </div>

                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Actual Shipping</span>
                        <strong><?= $money($order['actual_shipping']) ?></strong>
                    </div>

                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Cashback Deducted</span>
                        <strong><?= $money($order['cashback_deduct']) ?></strong>
                    </div>

                    <div class="d-flex justify-content-between mb-3">
                        <span class="text-muted">Platform Fee</span>
                        <strong><?= $money($order['platform_fee']) ?></strong>
                    </div>

                    <hr>

                    <div class="d-flex justify-content-between">
                        <strong>Total Amount</strong>
                        <strong class="text-primary fs-5">
                            <?= $money($order['total_amount']) ?>
                        </strong>
                    </div>

                    <hr>

                    <div class="small text-muted">
                        Payment method:
                        <strong><?= esc(strtoupper($order['payment_method'] ?? '-')) ?></strong>
                    </div>

                    <div class="small text-muted mt-1">
                        Payment status:
                        <strong><?= esc(ucfirst($order['payment_status'] ?? '-')) ?></strong>
                    </div>

                </div>
            </div>

        </div>

    </div>

</div>

<?= $this->endSection() ?>
