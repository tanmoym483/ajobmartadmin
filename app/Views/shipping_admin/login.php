
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Shipping Admin Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">
    <style>
        body { background: #f3f5f9; }
        .login-card { max-width: 430px; width: 100%; border: 0; border-radius: 16px; }
    </style>
</head>
<body>
<main class="container min-vh-100 d-flex align-items-center justify-content-center py-4">
    <section class="card login-card shadow-sm">
        <div class="card-body p-4 p-md-5">
            <div class="mb-4">
                <div class="text-primary fw-semibold mb-2">AJOBMART</div>
                <h3 class="mb-2">Shipping Admin</h3>
                <p class="text-secondary">Sign in to manage India Post PIN codes and shipping zones.</p>
            </div>

            <?php foreach (['success' => 'success', 'error' => 'danger'] as $key => $class): ?>
                <?php if ($message = session()->getFlashdata($key)): ?>
                    <div class="alert alert-<?= $class ?>"><?= esc($message) ?></div>
                <?php endif; ?>
            <?php endforeach; ?>

            <form method="post"
                  action="<?= site_url('shippingadmin/login') ?>"
                  autocomplete="on">
                <?= csrf_field() ?>

                <div class="mb-3">
                    <label class="form-label">Username</label>
                    <input type="text" name="username" class="form-control"
                           maxlength="100" autocomplete="username" required>
                </div>

                <div class="mb-4">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control"
                           autocomplete="current-password" required>
                </div>

                <button type="submit" class="btn btn-primary w-100">
                    Sign In
                </button>
            </form>
        </div>
    </section>
</main>
</body>
</html>
