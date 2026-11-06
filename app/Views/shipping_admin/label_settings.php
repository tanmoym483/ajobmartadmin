
<?= $this->extend('shipping_admin/layouts/main') ?>

<?= $this->section('content') ?>

<?php
$settings = $settings ?? [];

$companyName = $settings['company_name'] ?? 'Ajobmart';
$labelSize = $settings['label_size'] ?? 'A5';
$footerText = $settings['footer_text'] ?? '';

$logoPath = $settings['logo_path'] ?? null;
?>

<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">Shipping Label Settings</h4>
            <p class="text-muted mb-0">
                Configure the design of seller shipping labels.
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

    <form method="post"
          action="<?= site_url('shipping-admin/label-settings/save') ?>"
          enctype="multipart/form-data">

        <?= csrf_field() ?>

        <div class="row g-4">

            <div class="col-lg-8">

                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white py-3">
                        <h6 class="mb-0">Company Branding</h6>
                    </div>

                    <div class="card-body">

                        <div class="mb-3">
                            <label class="form-label">Company Name</label>
                            <input type="text"
                                   name="company_name"
                                   class="form-control"
                                   maxlength="150"
                                   value="<?= esc($companyName) ?>"
                                   required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Company Logo</label>
                            <input type="file"
                                   name="logo"
                                   class="form-control"
                                   accept=".jpg,.jpeg,.png,.webp">

                            <small class="text-muted">
                                JPG, PNG or WebP. Maximum 2 MB.
                            </small>
                        </div>

                        <?php if ($logoPath): ?>
                            <div class="mt-3">
                                <label class="form-label">Current Logo</label>
                                <div>
                                    <img src="<?= base_url($logoPath) ?>"
                                         alt="Company Logo"
                                         style="max-width:180px;max-height:90px;object-fit:contain;">
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="mt-3">
                            <label class="form-label">Label Paper Size</label>

                            <select name="label_size" class="form-select">
                                <option value="A5" <?= $labelSize === 'A5' ? 'selected' : '' ?>>
                                    A5 Portrait
                                </option>

                                <option value="A6" <?= $labelSize === 'A6' ? 'selected' : '' ?>>
                                    A6 Portrait
                                </option>
                            </select>
                        </div>

                    </div>
                </div>

                <div class="card border-0 shadow-sm mb-4">

                    <div class="card-header bg-white py-3">
                        <h6 class="mb-0">Label Information Visibility</h6>
                    </div>

                    <div class="card-body">

                        <?php
                        $visibilityFields = [
                            'show_order_number' => 'Show Order Number',
                            'show_label_number' => 'Show Label Number',
                            'show_seller_details' => 'Show Seller Details',
                            'show_product_details' => 'Show Product Details',
                            'show_payment_details' => 'Show Payment Details',
                            'show_shipment_details' => 'Show Shipment Details',
                        ];
                        ?>

                        <?php foreach ($visibilityFields as $field => $label): ?>

                            <div class="form-check form-switch mb-3">
                                <input type="checkbox"
                                       class="form-check-input"
                                       id="<?= esc($field) ?>"
                                       name="<?= esc($field) ?>"
                                       value="1"
                                       <?= !empty($settings[$field]) ? 'checked' : '' ?>>

                                <label class="form-check-label"
                                       for="<?= esc($field) ?>">
                                    <?= esc($label) ?>
                                </label>
                            </div>

                        <?php endforeach; ?>

                    </div>
                </div>

                <div class="card border-0 shadow-sm">

                    <div class="card-header bg-white py-3">
                        <h6 class="mb-0">Label Footer</h6>
                    </div>

                    <div class="card-body">

                        <textarea name="footer_text"
                                  class="form-control"
                                  rows="3"
                                  maxlength="500"
                                  placeholder="Enter label footer text"><?= esc($footerText) ?></textarea>

                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-1"></i>
                        Save Label Settings
                    </button>
                </div>

            </div>

            <div class="col-lg-4">

                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white py-3">
                        <h6 class="mb-0">Important Information</h6>
                    </div>

                    <div class="card-body">

                        <p class="text-muted small">
                            These settings will control the design of newly
                            generated seller shipping labels after the
                            label-generation code is integrated.
                        </p>

                        <hr>

                        <p class="small mb-2">
                            <i class="bi bi-check-circle text-success me-1"></i>
                            Seller pickup address remains seller-specific.
                        </p>

                        <p class="small mb-2">
                            <i class="bi bi-check-circle text-success me-1"></i>
                            Customer delivery address remains order-specific.
                        </p>

                        <p class="small mb-2">
                            <i class="bi bi-check-circle text-success me-1"></i>
                            India Post consignment number is managed by admin.
                        </p>

                        <p class="small mb-0">
                            <i class="bi bi-info-circle text-primary me-1"></i>
                            Existing PDF files will not change automatically.
                        </p>

                    </div>
                </div>

            </div>

        </div>

    </form>

</div>

<?= $this->endSection() ?>
