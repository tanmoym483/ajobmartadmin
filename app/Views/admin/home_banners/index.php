<?= $this->extend('shipping_admin/layouts/main') ?>

<?= $this->section('content') ?>



<?php
$currentPath = trim(uri_string(), '/');
?>

<div class="container-fluid py-3">

    <!-- PAGE HEADER -->
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">

        <div>
            <h4 class="fw-bold mb-1">Home Banners</h4>
            <p class="text-muted mb-0">
                Manage banners displayed on the customer homepage.
            </p>
        </div>

        <a href="<?= site_url('admin/home-banners/create') ?>"
           class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i>
            Add New Banner
        </a>

    </div>


    <!-- FLASH MESSAGES -->
    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?= esc(session()->getFlashdata('success')) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?= esc(session()->getFlashdata('error')) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>


    <!-- BANNER TABLE -->
    <div class="card border-0 shadow-sm">

        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">

            <h6 class="fw-bold mb-0">
                <i class="bi bi-images me-2"></i>
                All Homepage Banners
            </h6>

            <span class="badge bg-light text-dark">
                <?= count($banners ?? []) ?> Banners
            </span>

        </div>


        <div class="card-body p-0">

            <?php if (!empty($banners)): ?>

                <div class="table-responsive">

                    <table class="table table-hover align-middle mb-0">

                        <thead class="table-light">

                            <tr>
                                <th class="ps-3" width="60">#</th>
                                <th width="220">Banner Image</th>
                                <th>Banner Details</th>
                                <th>Destination</th>
                                <th width="100">Order</th>
                                <th width="110">Status</th>
                                <th width="150" class="text-center">Actions</th>
                            </tr>

                        </thead>


                        <tbody>

                            <?php foreach ($banners as $index => $banner): ?>

                                <tr>

                                    <td class="ps-3">
                                        <?= $index + 1 ?>
                                    </td>


                                    <!-- IMAGE -->
                                    <td>

                                        <img
                                        src="<?= site_url('index.php/shared-images/' . ltrim($banner['image'], '/')) ?>"
                                        alt="<?= esc($banner['title']) ?>"
                                        class="aj-banner-thumb"
                                        loading="lazy">
                                        <small>
    <?= esc(site_url('shared-images/' . ltrim($banner['image'], '/'))) ?>
</small>

                                    </td>


                                    <!-- DETAILS -->
                                    <td>

                                        <div class="fw-semibold">
                                            <?= esc($banner['title']) ?>
                                        </div>

                                        <small class="text-muted">
                                            ID: #<?= (int) $banner['id'] ?>
                                        </small>

                                        <div class="small text-muted mt-1">

                                            <?php if (!empty($banner['open_new_tab'])): ?>
                                                <i class="bi bi-box-arrow-up-right me-1"></i>
                                                Opens in new tab
                                            <?php else: ?>
                                                <i class="bi bi-window me-1"></i>
                                                Same tab
                                            <?php endif; ?>

                                        </div>

                                    </td>


                                    <!-- DESTINATION -->
                                    <td>

                                        <?php if (!empty($banner['target_url'])): ?>

                                            <a href="<?= esc($banner['target_url']) ?>"
                                               target="_blank"
                                               rel="noopener noreferrer"
                                               class="text-decoration-none small">

                                                <?= esc(mb_strimwidth(
                                                    $banner['target_url'],
                                                    0,
                                                    35,
                                                    '...'
                                                )) ?>

                                                <i class="bi bi-box-arrow-up-right ms-1"></i>

                                            </a>

                                        <?php else: ?>

                                            <span class="text-muted small">
                                                No destination
                                            </span>

                                        <?php endif; ?>

                                    </td>


                                    <!-- ORDER -->
                                    <td>

                                        <span class="badge bg-light text-dark border">
                                            <?= (int) $banner['sort_order'] ?>
                                        </span>

                                    </td>


                                    <!-- STATUS -->
                                    <td>

                                        <?php if ($banner['status'] === 'active'): ?>

                                            <span class="badge bg-success-subtle text-success">
                                                Active
                                            </span>

                                        <?php else: ?>

                                            <span class="badge bg-secondary-subtle text-secondary">
                                                Inactive
                                            </span>

                                        <?php endif; ?>

                                    </td>


                                    <!-- ACTIONS -->
                                    <td>

                                        <div class="d-flex justify-content-center gap-2">

                                            <a href="<?= site_url('admin/home-banners/edit/' . $banner['id']) ?>"
                                               class="btn btn-sm btn-outline-primary"
                                               title="Edit Banner">

                                                <i class="bi bi-pencil-square"></i>

                                            </a>


                                            <button type="button"
                                                    class="btn btn-sm btn-outline-danger"
                                                    title="Delete Banner"
                                                    onclick="deleteBanner(<?= (int) $banner['id'] ?>, '<?= esc(addslashes($banner['title'])) ?>')">

                                                <i class="bi bi-trash"></i>

                                            </button>

                                        </div>

                                        <form id="deleteBannerForm<?= (int) $banner['id'] ?>"
                                              action="<?= site_url('admin/home-banners/delete/' . $banner['id']) ?>"
                                              method="post"
                                              class="d-none">

                                            <?= csrf_field() ?>

                                        </form>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            <?php else: ?>

                <!-- EMPTY STATE -->
                <div class="text-center py-5">

                    <div class="mb-3">
                        <i class="bi bi-images text-muted"
                           style="font-size:55px;"></i>
                    </div>

                    <h5 class="fw-semibold">No Banners Found</h5>

                    <p class="text-muted mb-3">
                        You haven't uploaded any homepage banners yet.
                    </p>

                    <a href="<?= site_url('admin/home-banners/create') ?>"
                       class="btn btn-primary">

                        <i class="bi bi-plus-lg me-1"></i>
                        Upload Your First Banner

                    </a>

                </div>

            <?php endif; ?>

        </div>

    </div>

</div>


<!-- DELETE CONFIRMATION -->
<script>
function deleteBanner(id, title) {

    if (!confirm('Are you sure you want to delete "' + title + '"?')) {
        return;
    }

    const form = document.getElementById('deleteBannerForm' + id);

    if (form) {
        form.submit();
    }

}
</script>


<!-- CUSTOM STYLING -->
<style>

.aj-banner-thumb {
    width: 190px;
    height: 65px;
    object-fit: cover;
    border-radius: 7px;
    border: 1px solid #e5e7eb;
    background: #f8f9fa;
}

.table th {
    font-size: 13px;
    font-weight: 600;
    white-space: nowrap;
}

.table td {
    font-size: 13px;
}

.table tbody tr:last-child td {
    border-bottom: 0;
}

@media(max-width: 767px) {

    .aj-banner-thumb {
        width: 130px;
        height: 50px;
    }

}

</style>








<?= $this->endSection() ?>
