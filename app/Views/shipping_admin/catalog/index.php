<?= $this->extend('shipping_admin/layouts/main') ?>
<?= $this->section('content') ?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h1 class="admin-page-title"><?= esc($pageTitle) ?></h1>
        <div class="admin-page-subtitle">Manage your Ajobmart product catalog</div>
    </div>
    <a class="btn btn-primary" href="<?= site_url('shipping-admin/catalog/' . $type . '/add') ?>">
        <i class="bi bi-plus-lg me-1"></i> Add <?= esc(rtrim($pageTitle, 's')) ?>
    </a>
</div>

<div class="d-flex flex-wrap gap-2 mb-3">
    <a class="btn btn-sm <?= $type === 'categories' ? 'btn-dark' : 'btn-outline-secondary' ?>" href="<?= site_url('shipping-admin/catalog/categories') ?>">Categories</a>
    <a class="btn btn-sm <?= $type === 'subcategories' ? 'btn-dark' : 'btn-outline-secondary' ?>" href="<?= site_url('shipping-admin/catalog/subcategories') ?>">Subcategories</a>
    <a class="btn btn-sm <?= $type === 'product-types' ? 'btn-dark' : 'btn-outline-secondary' ?>" href="<?= site_url('shipping-admin/catalog/product-types') ?>">Product Types</a>
    <a class="btn btn-sm <?= $type === 'attributes' ? 'btn-dark' : 'btn-outline-secondary' ?>" href="<?= site_url('shipping-admin/catalog/attributes') ?>">Attributes</a>
</div>

<div class="admin-card">
    <div class="p-3 p-md-4">
        <form method="get" action="<?= site_url('shipping-admin/catalog/' . $type) ?>" class="row g-2 align-items-center mb-3">
            <div class="col-12 col-md-8 col-lg-6">
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                    <input type="search" name="search" class="form-control" value="<?= esc($search ?? '') ?>"
                           placeholder="Search <?= esc(strtolower($pageTitle)) ?> by name, slug or parent...">
                    <?php if (!empty($search)): ?>
                        <a class="btn btn-outline-secondary" href="<?= site_url('shipping-admin/catalog/' . $type) ?>">Clear</a>
                    <?php endif ?>
                    <button class="btn btn-dark" type="submit">Search</button>
                </div>
            </div>
            <div class="col-12 col-md-auto text-muted small">
                <?= count($rows) ?> result(s)<?= !empty($search) ? ' for “' . esc($search) . '”' : '' ?>
            </div>
        </form>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <?php if ($type === 'subcategories' || $type === 'attributes'): ?><th>Category</th><?php endif ?>
                        <?php if ($type === 'product-types' || ($type === 'attributes')): ?><th>Subcategory</th><?php endif ?>
                        <th>Name / Label</th>
                        <?php if ($type !== 'attributes'): ?><th>Slug</th><?php else: ?><th>Key</th><th>Input</th><th>Required</th><?php endif ?>
                        <th>Sort</th><th>Status</th><th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($rows): foreach ($rows as $r): ?>
                    <?php
                    $displayName = $type === 'attributes' ? ($r['label'] ?? '') : ($r['name'] ?? '');
                    $active = $type === 'categories' || $type === 'attributes'
                        ? (($r['status'] ?? '') === 'active')
                        : ((int)($r['status'] ?? 0) === 1);
                    ?>
                    <tr>
                        <td><?= (int)$r['id'] ?></td>
                        <?php if ($type === 'subcategories' || $type === 'attributes'): ?><td><?= esc($r['category_name'] ?? '-') ?></td><?php endif ?>
                        <?php if ($type === 'product-types' || $type === 'attributes'): ?><td><?= esc($r['subcategory_name'] ?? 'All subcategories') ?></td><?php endif ?>
                        <td class="fw-semibold"><?= esc($displayName) ?></td>
                        <?php if ($type !== 'attributes'): ?>
                            <td><code><?= esc($r['slug'] ?? '') ?></code></td>
                        <?php else: ?>
                            <td><code><?= esc($r['attribute_name'] ?? '') ?></code></td>
                            <td><?= esc($r['input_type'] ?? '') ?></td>
                            <td><?= !empty($r['is_required']) ? 'Yes' : 'No' ?></td>
                        <?php endif ?>
                        <td><?= (int)($r['sort_order'] ?? 0) ?></td>
                        <td><?= $active ? '<span class="badge bg-success-subtle text-success">Active</span>' : '<span class="badge bg-secondary-subtle text-secondary">Inactive</span>' ?></td>
                        <td class="text-end text-nowrap">
                            <a class="btn btn-sm btn-outline-primary" title="Edit" href="<?= site_url('shipping-admin/catalog/' . $type . '/edit/' . $r['id']) ?>"><i class="bi bi-pencil"></i></a>
                            <form method="post" class="d-inline" action="<?= site_url('shipping-admin/catalog/' . $type . '/delete/' . $r['id']) ?>" onsubmit="return confirm('Delete this record?')">
                                <?= csrf_field() ?>
                                <button class="btn btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="10" class="text-center text-muted py-5"><?php if (!empty($search)): ?>No matching records found for “<?= esc($search) ?>”.<?php else: ?>No records found.<?php endif ?></td></tr>
                <?php endif ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
