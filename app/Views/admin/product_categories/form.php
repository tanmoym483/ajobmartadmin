<?= $this->extend('shipping_admin/layouts/main') ?>

<?= $this->section('content') ?>
<?php
$categoryName = old(
    'category_name',
    $category['category_name'] ?? ''
);

$status = old(
    'status',
    $category['status'] ?? 'active'
);

$sortOrder = old(
    'sort_order',
    $category['sort_order'] ?? 0
);
?>

<div class="container-fluid py-3">

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>
            <h4 class="mb-1">
                <?= esc($title) ?>
            </h4>

            <small class="text-muted">
                Create or update product category
            </small>
        </div>

        <a href="<?= site_url('shipping-admin/categories') ?>"
           class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i>
            Back
        </a>

    </div>

    <!-- Validation Errors -->
    <?php if (session()->getFlashdata('errors')): ?>

        <div class="alert alert-danger">

            <ul class="mb-0">

                <?php foreach (session()->getFlashdata('errors') as $error): ?>

                    <li><?= esc($error) ?></li>

                <?php endforeach; ?>

            </ul>

        </div>

    <?php endif; ?>

    <!-- Form -->
    <div class="card border-0 shadow-sm">

        <div class="card-body p-4">

            <form action="<?= esc($action) ?>"
                  method="post">

                <?= csrf_field() ?>

                <!-- Category Name -->
                <div class="mb-3">

                    <label class="form-label">
                        Category Name
                        <span class="text-danger">*</span>
                    </label>

                    <input type="text"
                           name="category_name"
                           class="form-control"
                           value="<?= esc($categoryName) ?>"
                           placeholder="Enter category name"
                           required>

                </div>

                <!-- Sort Order -->
                <div class="mb-3">

                    <label class="form-label">
                        Sort Order
                    </label>

                    <input type="number"
                           name="sort_order"
                           class="form-control"
                           value="<?= esc($sortOrder) ?>"
                           min="0">

                    <small class="text-muted">
                        Lower number displays first.
                    </small>

                </div>

                <!-- Status -->
                <div class="mb-4">

                    <label class="form-label">
                        Status
                    </label>

                    <select name="status"
                            class="form-select"
                            required>

                        <option value="active"
                            <?= $status === 'active' ? 'selected' : '' ?>>
                            Active
                        </option>

                        <option value="inactive"
                            <?= $status === 'inactive' ? 'selected' : '' ?>>
                            Inactive
                        </option>

                    </select>

                </div>

                <!-- Buttons -->
                <div class="d-flex gap-2">

                    <button type="submit"
                            class="btn btn-primary">

                        <i class="bi bi-check-lg"></i>

                        <?= isset($category['id'])
                            ? 'Update Category'
                            : 'Save Category' ?>

                    </button>

                    <a href="<?= site_url('shipping-admin/categories') ?>"
                       class="btn btn-light border">
                        Cancel
                    </a>

                </div>

            </form>

        </div>

    </div>

</div>

<?= $this->endSection() ?>
