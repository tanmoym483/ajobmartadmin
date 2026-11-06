
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Create Shipping Administrator</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">
    <style>
        body { background: #f3f5f9; }
        .setup-card { max-width: 480px; width: 100%; border: 0; border-radius: 16px; }
    </style>
</head>
<body>
<main class="container min-vh-100 d-flex align-items-center justify-content-center py-4">
    <section class="card setup-card shadow-sm">
        <div class="card-body p-4 p-md-5">
            <h3 class="mb-2">Initial Admin Setup</h3>
            <p class="text-secondary mb-4">
                Create the first administrator for India Post shipping management.
                This page closes automatically after the first account is created.
            </p>

            <?php if ($message = session()->getFlashdata('error')): ?>
                <div class="alert alert-danger"><?= esc($message) ?></div>
            <?php endif; ?>

            <form method="post"
                  action="<?= site_url('shipping-admin/setup') ?>"
                  autocomplete="off">
                <?= csrf_field() ?>

                <div class="mb-3">
                    <label class="form-label">One-time setup key</label>
                    <input type="password" name="setup_key" class="form-control"
                           autocomplete="off" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Admin username</label>
                    <input type="text" name="username" class="form-control"
                           value="<?= esc(old('username')) ?>"
                           minlength="4" maxlength="100"
                           autocomplete="username" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control"
                           minlength="12" maxlength="200"
                           autocomplete="new-password" required>
                    <div class="form-text">Use at least 12 characters.</div>
                </div>

                <div class="mb-4">
                    <label class="form-label">Confirm password</label>
                    <input type="password" name="password_confirm"
                           class="form-control" minlength="12" maxlength="200"
                           autocomplete="new-password" required>
                </div>

                <button class="btn btn-primary w-100" type="submit">
                    Create Administrator
                </button>
            </form>
        </div>
    </section>
</main>
</body>
</html>
