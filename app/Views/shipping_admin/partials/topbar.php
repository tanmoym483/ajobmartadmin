
<?php
$adminUsername = session()->get('shipping_admin_username') ?? 'Admin';
?>

<header class="admin-topbar">

    <div class="d-flex align-items-center gap-3">
        <button type="button"
                class="btn btn-light d-lg-none"
                id="adminSidebarToggle">
            <i class="bi bi-list fs-5"></i>
        </button>

        <div class="fw-semibold d-none d-sm-block">
            Ajobmart Administration
        </div>
    </div>

    <div class="d-flex align-items-center gap-3">

        <div class="text-end d-none d-sm-block">
            <div class="small fw-semibold"><?= esc($adminUsername) ?></div>
            <div class="text-secondary" style="font-size:11px;">
                Administrator
            </div>
        </div>

        <div class="rounded-circle bg-primary-subtle text-primary
                    d-flex align-items-center justify-content-center"
             style="width:38px;height:38px;">
            <i class="bi bi-person-fill"></i>
        </div>

        <form method="post" action="<?= site_url('shipping-admin/logout') ?>">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-sm btn-outline-danger">
                <i class="bi bi-box-arrow-right"></i>
                <span class="d-none d-md-inline">Logout</span>
            </button>
        </form>

    </div>
</header>
