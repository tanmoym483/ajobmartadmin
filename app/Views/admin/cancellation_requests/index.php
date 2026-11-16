
<?= $this->extend('shipping_admin/layouts/main') ?>

<?= $this->section('content') ?>

<div class="container-fluid py-4">

    <h3 class="mb-4">Cancellation Requests</h3>

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

    <div class="d-flex flex-wrap gap-2 mb-4">

        <a href="<?= site_url('admin/cancellation-requests?status=pending') ?>"
           class="btn btn-outline-warning">
            Pending (<?= $counts['pending'] ?>)
        </a>

        <a href="<?= site_url('admin/cancellation-requests?status=approved') ?>"
           class="btn btn-outline-success">
            Approved (<?= $counts['approved'] ?>)
        </a>

        <a href="<?= site_url('admin/cancellation-requests?status=rejected') ?>"
           class="btn btn-outline-danger">
            Rejected (<?= $counts['rejected'] ?>)
        </a>

        <a href="<?= site_url('admin/cancellation-requests?status=all') ?>"
           class="btn btn-outline-secondary">
            All Requests
        </a>

    </div>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">

            <table class="table table-hover align-middle mb-0">

                <thead class="table-light">
                    <tr>
                        <th>Request</th>
                        <th>Order</th>
                        <th>Customer</th>
                        <th>Amount</th>
                        <th>Reason</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>

                <tbody>

                <?php if (empty($requests)): ?>

                    <tr>
                        <td colspan="8" class="text-center py-5">
                            No cancellation requests found.
                        </td>
                    </tr>

                <?php else: ?>

                    <?php foreach ($requests as $row): ?>

                        <tr>
                            <td>#<?= (int) $row['id'] ?></td>

                            <td>
                                <strong>
                                    <?= esc($row['order_number']) ?>
                                </strong>
                                <div class="small text-muted">
                                    <?= esc($row['order_status']) ?>
                                </div>
                            </td>

                            <td>
                                <?= esc($row['customer_name']) ?>
                                <div class="small text-muted">
                                    <?= esc($row['customer_mobile']) ?>
                                </div>
                            </td>

                            <td>
                                ₹<?= number_format((float) $row['total_amount'], 2) ?>
                            </td>

                            <td style="max-width:220px">
                                <?= esc(mb_strimwidth($row['reason'], 0, 70, '...')) ?>
                            </td>

                            <td>
                                <?= esc($row['created_at']) ?>
                            </td>

                            <td>
                                <?php
                                $badge = match ($row['request_status']) {
                                    'approved' => 'success',
                                    'rejected' => 'danger',
                                    default => 'warning'
                                };
                                ?>

                                <span class="badge bg-<?= $badge ?>">
                                    <?= ucfirst(esc($row['request_status'])) ?>
                                </span>
                            </td>

                            <td>
                                <a href="<?= site_url('admin/cancellation-requests/' . $row['id']) ?>"
                                   class="btn btn-sm btn-primary">
                                    View
                                </a>
                            </td>
                        </tr>

                    <?php endforeach; ?>

                <?php endif; ?>

                </tbody>

            </table>

        </div>
    </div>

</div>

<?= $this->endSection() ?>
