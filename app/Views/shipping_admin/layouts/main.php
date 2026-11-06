
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title><?= esc($pageTitle ?? 'Admin Dashboard') ?> | Ajobmart</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
          rel="stylesheet">

    <style>
        :root {
            --admin-sidebar-width: 255px;
            --admin-sidebar-bg: #172337;
            --admin-sidebar-text: #b8c5d8;
            --admin-primary: #2563eb;
            --admin-bg: #f4f6fa;
        }

        body {
            margin: 0;
            background: var(--admin-bg);
            color: #202b3c;
            font-family: Arial, sans-serif;
        }

        .admin-sidebar {
            position: fixed;
            inset: 0 auto 0 0;
            width: var(--admin-sidebar-width);
            background: var(--admin-sidebar-bg);
            color: #fff;
            z-index: 1040;
            overflow-y: auto;
            transition: transform .25s ease;
        }

        .admin-brand {
            height: 68px;
            display: flex;
            align-items: center;
            padding: 0 22px;
            border-bottom: 1px solid rgba(255,255,255,.09);
            font-size: 20px;
            font-weight: 700;
            color: #fff;
            text-decoration: none;
        }

        .admin-brand span {
            color: #60a5fa;
            margin-left: 5px;
        }

        .sidebar-label {
            padding: 22px 22px 9px;
            color: #7f91aa;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .sidebar-link {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 3px 12px;
            padding: 11px 13px;
            color: var(--admin-sidebar-text);
            text-decoration: none;
            border-radius: 8px;
            font-size: 14px;
            transition: .2s;
        }

        .sidebar-link:hover,
        .sidebar-link.active {
            background: #263b59;
            color: #fff;
        }

        .sidebar-link.active {
            box-shadow: inset 3px 0 #60a5fa;
        }

        .sidebar-link i {
            font-size: 17px;
            width: 20px;
        }

        .admin-main {
            margin-left: var(--admin-sidebar-width);
            min-height: 100vh;
        }

        .admin-topbar {
            height: 68px;
            background: #fff;
            border-bottom: 1px solid #e7eaf0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 26px;
            position: sticky;
            top: 0;
            z-index: 1020;
        }

        .admin-content {
            padding: 28px;
        }

        .admin-page-title {
            font-size: 23px;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .admin-page-subtitle {
            color: #7b8494;
            font-size: 14px;
        }

        .admin-card {
            background: #fff;
            border: 1px solid #edf0f5;
            border-radius: 13px;
            box-shadow: 0 2px 8px rgba(20, 35, 60, .035);
        }

        .admin-stat-icon {
            width: 45px;
            height: 45px;
            border-radius: 11px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 21px;
        }

        .admin-mobile-overlay {
            display: none;
        }

        @media (max-width: 991.98px) {
            .admin-sidebar {
                transform: translateX(-100%);
            }

            .admin-sidebar.show {
                transform: translateX(0);
            }

            .admin-main {
                margin-left: 0;
            }

            .admin-content {
                padding: 18px 14px;
            }

            .admin-topbar {
                padding: 0 15px;
            }

            .admin-mobile-overlay.show {
                display: block;
                position: fixed;
                inset: 0;
                background: rgba(0,0,0,.4);
                z-index: 1030;
            }
        }
    </style>

    <?= $this->renderSection('styles') ?>
</head>

<body>

<?= $this->include('shipping_admin/partials/sidebar') ?>

<div class="admin-mobile-overlay" id="adminMobileOverlay"></div>

<div class="admin-main">

    <?= $this->include('shipping_admin/partials/topbar') ?>

    <main class="admin-content">

        <?php foreach (['success' => 'success', 'error' => 'danger'] as $key => $class): ?>
            <?php if ($message = session()->getFlashdata($key)): ?>
                <div class="alert alert-<?= $class ?> alert-dismissible fade show">
                    <?= esc($message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>

        <?= $this->renderSection('content') ?>

    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
    (function () {
        const sidebar = document.getElementById('adminSidebar');
        const overlay = document.getElementById('adminMobileOverlay');
        const toggle = document.getElementById('adminSidebarToggle');

        function closeSidebar() {
            sidebar?.classList.remove('show');
            overlay?.classList.remove('show');
        }

        toggle?.addEventListener('click', function () {
            sidebar?.classList.toggle('show');
            overlay?.classList.toggle('show');
        });

        overlay?.addEventListener('click', closeSidebar);
    })();
</script>

<?= $this->renderSection('scripts') ?>

</body>
</html>
