<?= $this->extend('shipping_admin/layouts/main') ?>

<?= $this->section('content') ?>

<div class="container-fluid py-3">

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>
            <h4 class="mb-1">Product Categories</h4>
            <small class="text-muted">
                Manage Ajobmart product categories
            </small>
        </div>

        <a href="<?= site_url('shipping-admin/categories/add') ?>"
           class="btn btn-primary">
            <i class="bi bi-plus-lg"></i>
            Add Category
        </a>

    </div>

    <!-- Flash Messages -->
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

    <!-- Category Table -->
    <div class="card border-0 shadow-sm">

        <div class="card-body">

            <div class="table-responsive">

                <table class="table table-hover align-middle">

                    <thead class="table-light">
                        <tr>
                            <th width="70">ID</th>
                            <th>Category</th>
                            <th>Slug</th>
                            <th width="100">Order</th>
                            <th width="120">Status</th>
                            <th width="150" class="text-end">Actions</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php if (!empty($categories)): ?>

                        <?php foreach ($categories as $category): ?>

                            <tr>

                                <td>
                                    <?= esc($category['id']) ?>
                                </td>

                                <td>
                                    <strong>
                                        <?= esc($category['name']) ?>
                                    </strong>
                                </td>

                                <td>
                                    <code>
                                        <?= esc($category['slug']) ?>
                                    </code>
                                </td>

                                <td>
                                    <?= esc($category['sort_order']) ?>
                                </td>

                                <td>

                                    <?php if ($category['status'] === 'active'): ?>

                                        <span class="badge bg-success">
                                            Active
                                        </span>

                                    <?php else: ?>

                                        <span class="badge bg-secondary">
                                            Inactive
                                        </span>

                                    <?php endif; ?>

                                </td>

                                <td class="text-end">

                                    <a href="<?= site_url('shipping-admin/categories/edit/' . $category['id']) ?>"
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-pencil"></i>
                                    </a>

                                    <form action="<?= site_url('shipping-admin/categories/delete/' . $category['id']) ?>"
                                          method="post"
                                          class="d-inline"
                                          onsubmit="return confirm('Delete this category?')">

                                        <?= csrf_field() ?>

                                        <button type="submit"
                                                class="btn btn-sm btn-outline-danger">
                                            <i class="bi bi-trash"></i>
                                        </button>

                                    </form>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="6"
                                class="text-center text-muted py-5">
                                No categories found.
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
