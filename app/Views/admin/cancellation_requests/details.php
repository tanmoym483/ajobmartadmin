
<?= $this->extend('shipping_admin/layouts/main') ?>

<?= $this->section('content') ?>

<div class="container-fluid py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3>Cancellation Request #<?= (int) $request['id'] ?></h3>

        <a href="<?= site_url('admin/cancellation-requests') ?>"
           class="btn btn-outline-secondary">
            Back to Requests
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

        <div class="col-lg-7">

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white">
                    <strong>Order Information</strong>
                </div>

                <div class="card-body">

                    <div class="row mb-2">
                        <div class="col-5 text-muted">Order Number</div>
                        <div class="col-7">
                            <strong><?= esc($request['order_number']) ?></strong>
                        </div>
                    </div>

                    <div class="row mb-2">
                        <div class="col-5 text-muted">Order Status</div>
                        <div class="col-7"><?= esc($request['order_status']) ?></div>
                    </div>

                    <div class="row mb-2">
                        <div class="col-5 text-muted">Payment Method</div>
                        <div class="col-7"><?= esc($request['payment_method']) ?></div>
                    </div>

                    <div class="row mb-2">
                        <div class="col-5 text-muted">Payment Status</div>
                        <div class="col-7"><?= esc($request['payment_status']) ?></div>
                    </div>

                    <div class="row">
                        <div class="col-5 text-muted">Order Amount</div>
                        <div class="col-7">
                            <strong>
                                ₹<?= number_format((float) $request['total_amount'], 2) ?>
                            </strong>
                        </div>
                    </div>

                </div>
            </div>

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white">
                    <strong>Customer Information</strong>
                </div>

                <div class="card-body">
                    <p><strong>Name:</strong> <?= esc($request['customer_name']) ?></p>
                    <p><strong>Email:</strong> <?= esc($request['customer_email']) ?></p>
                    <p><strong>Mobile:</strong> <?= esc($request['customer_mobile']) ?></p>
                </div>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-header bg-white">
                    <strong>Ordered Products</strong>
                </div>

                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Qty</th>
                                <th>Price</th>
                            </tr>
                        </thead>

                        <tbody>
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <td><?= esc($item['product_name']) ?></td>
                                <td><?= (int) $item['quantity'] ?></td>
                                <td>
                                    ₹<?= number_format((float) $item['line_total'], 2) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

        <div class="col-lg-5">

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white">
                    <strong>Customer Cancellation Reason</strong>
                </div>

                <div class="card-body">
                    <?= nl2br(esc($request['reason'])) ?>

                    <div class="small text-muted mt-3">
                        Submitted: <?= esc($request['created_at']) ?>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0">

                <div class="card-header bg-white">
                    <strong>Admin Decision & Reply</strong>
                </div>

                <div class="card-body">

                    <div class="mb-3">
                        Current Status:
                        <span class="badge bg-warning">
                            <?= ucfirst(esc($request['request_status'])) ?>
                        </span>
                    </div>

                    <?php if ($request['request_status'] === 'pending'): ?>

                        <form method="post"
                              action="<?= site_url('admin/cancellation-requests/review/' . $request['id']) ?>">

                            <?= csrf_field() ?>

                            <div class="mb-3">
                                <label class="form-label">
                                    Admin Reply <span class="text-danger">*</span>
                                </label>

                                <textarea name="admin_note"
                                          class="form-control"
                                          rows="5"
                                          maxlength="2000"
                                          required
                                          placeholder="Write your reply to the customer..."></textarea>
                            </div>

                            <button type="submit"
                                    name="decision"
                                    value="approved"
                                    class="btn btn-success w-100 mb-2"
                                    onclick="return confirm('Approve this cancellation request?')">
                                Approve Cancellation
                            </button>

                            <button type="submit"
                                    name="decision"
                                    value="rejected"
                                    class="btn btn-danger w-100"
                                    onclick="return confirm('Reject this cancellation request?')">
                                Reject Cancellation
                            </button>

                        </form>

                    <?php else: ?>

                        <div class="alert alert-info">
                            This request has already been reviewed.
                        </div>

                        <div class="mb-3">
                            <strong>Admin Reply:</strong>
                            <div class="mt-2">
                                <?= nl2br(esc($request['admin_note'])) ?>
                            </div>
                        </div>

                        <div class="small text-muted">
                            Reviewed: <?= esc($request['reviewed_at']) ?>
                        </div>

                    <?php endif; ?>

                </div>
            </div>

        </div>

    </div>

</div>

<?= $this->endSection() ?>
