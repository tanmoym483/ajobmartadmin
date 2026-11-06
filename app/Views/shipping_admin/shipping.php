
<?= $this->extend('shipping_admin/layouts/main') ?>

<?= $this->section('content') ?>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">
    <style>
        body { background: #f3f5f9; color: #202936; }
        .page-card { border: 0; border-radius: 14px; }
        .table th { white-space: nowrap; }
        .stat-label { font-size: .85rem; color: #6b7280; }
        .stat-value { font-size: 1.25rem; font-weight: 700; }
    </style>


<main class="container-fluid px-3 px-lg-4 py-4">

    <div class="mb-4">
        <h3 class="mb-1">Shipping Management</h3>
        <p class="text-secondary mb-0">
            Import PIN codes and manage India Post origin-to-destination zones.
        </p>
    </div>

    <?php foreach (['success' => 'success', 'error' => 'danger'] as $key => $class): ?>
        <?php if ($message = session()->getFlashdata($key)): ?>
            <div class="alert alert-<?= $class ?>"><?= esc($message) ?></div>
        <?php endif; ?>
    <?php endforeach; ?>

    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card page-card shadow-sm h-100">
                <div class="card-body">
                    <div class="stat-label">PIN directory records shown</div>
                    <div class="stat-value"><?= count($pins) ?></div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card page-card shadow-sm h-100">
                <div class="card-body">
                    <div class="stat-label">Mappings shown</div>
                    <div class="stat-value"><?= count($mappings) ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Import PIN CSV -->
    <section class="card page-card shadow-sm mb-4">
        <div class="card-body p-3 p-md-4">
            <h5>1. Import PIN directory CSV</h5>
            <p class="text-secondary small">
                Imports unique six-digit PIN codes. Existing manual metro and
                local-area values are preserved.
            </p>
            <form action="<?= site_url('shipping-admin/import') ?>"
                  method="post" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <div class="row g-3 align-items-end">
                    <div class="col-12 col-md-8 col-lg-6">
                        <label class="form-label">CSV file</label>
                        <input type="file" name="pin_csv" class="form-control"
                               accept=".csv,text/csv" required>
                    </div>
                    <div class="col-12 col-md-auto">
                        <button type="submit" class="btn btn-primary">
                            Import / Update PINs
                        </button>
                    </div>
                </div>
                <div class="form-text">CSV only; maximum 20 MB.</div>
            </form>
        </div>
    </section>

    <!-- Add zone mapping -->
    <section class="card page-card shadow-sm mb-4">
        <div class="card-body p-3 p-md-4">
            <h5>2. Add manual India Post zone mapping</h5>
            <p class="text-secondary small">
                Select a verified tariff zone for the seller pickup PIN and
                customer delivery PIN pair.
            </p>

            <form method="post" action="<?= site_url('shipping-admin/mapping/save') ?>">
                <?= csrf_field() ?>
                <div class="row g-3">
                    <div class="col-12 col-md-6 col-xl-3">
                        <label class="form-label">Origin / seller pickup PIN</label>
                        <input name="origin_pincode" class="form-control"
                               inputmode="numeric" pattern="[0-9]{6}"
                               maxlength="6" minlength="6"
                               value="<?= esc(old('origin_pincode')) ?>"
                               required>
                    </div>

                    <div class="col-12 col-md-6 col-xl-3">
                        <label class="form-label">Destination / customer PIN</label>
                        <input name="destination_pincode" class="form-control"
                               inputmode="numeric" pattern="[0-9]{6}"
                               maxlength="6" minlength="6"
                               value="<?= esc(old('destination_pincode')) ?>"
                               required>
                    </div>

                    <div class="col-12 col-md-6 col-xl-3">
                        <label class="form-label">Zone</label>
                        <select name="zone_code" class="form-select" required>
                            <option value="">Select zone</option>
                            <option value="local" <?= old('zone_code') === 'local' ? 'selected' : '' ?>>LOCAL</option>
                            <option value="within_state" <?= old('zone_code') === 'within_state' ? 'selected' : '' ?>>WITHIN_STATE</option>
                            <option value="zone_metro" <?= old('zone_code') === 'zone_metro' ? 'selected' : '' ?>>ZONE_METRO</option>
                            <option value="other_state" <?= old('zone_code') === 'other_state' ? 'selected' : '' ?>>OTHER_STATE</option>
                        </select>
                    </div>

                    <div class="col-12 col-md-6 col-xl-3">
                        <label class="form-label">Notes</label>
                        <input name="notes" class="form-control"
                               maxlength="500" value="<?= esc(old('notes')) ?>"
                               placeholder="Optional verification notes">
                    </div>

                    <div class="col-12">
                        <button class="btn btn-success" type="submit">
                            Save Mapping
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </section>

    <!-- Search -->
    <section class="card page-card shadow-sm mb-4">
        <div class="card-body">
            <form method="get" action="<?= site_url('shipping-admin') ?>"
                  class="row g-2">
                <div class="col-12 col-md-8 col-lg-6">
                    <input type="search" name="search" class="form-control"
                           value="<?= esc($search ?? '') ?>"
                           placeholder="Search PIN, city, district, state, zone">
                </div>
                <div class="col-6 col-md-auto">
                    <button class="btn btn-dark w-100" type="submit">Search</button>
                </div>
                <div class="col-6 col-md-auto">
                    <a class="btn btn-outline-secondary w-100"
                       href="<?= site_url('shipping-admin') ?>">Clear</a>
                </div>
            </form>
        </div>
    </section>

    <!-- Zone mapping list -->
    <section class="card page-card shadow-sm mb-4">
        <div class="card-body p-0">
            <div class="p-3 p-md-4 border-bottom">
                <h5 class="mb-0">3. Existing zone mappings</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Origin PIN</th>
                        <th>Destination PIN</th>
                        <th>Zone</th>
                        <th>Notes</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if ($mappings): ?>
                        <?php foreach ($mappings as $row): ?>
                            <tr>
                                <td><?= esc($row['id']) ?></td>
                                <td><?= esc($row['origin_pincode']) ?></td>
                                <td><?= esc($row['destination_pincode']) ?></td>
                                <td><span class="badge text-bg-info"><?= esc(strtoupper($row['zone_code'])) ?></span></td>
                                <td><?= esc($row['notes'] ?? '') ?></td>
                                <td>
                                    <?php if ((int) $row['is_active'] === 1): ?>
                                        <span class="badge text-bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge text-bg-secondary">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <form method="post"
                                          action="<?= site_url('shipping-admin/mapping/toggle/' . $row['id']) ?>">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-sm btn-outline-primary">
                                            <?= (int) $row['is_active'] === 1 ? 'Deactivate' : 'Activate' ?>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="7" class="text-center text-secondary py-4">No mappings found.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <!-- PIN directory list -->
    <section class="card page-card shadow-sm">
        <div class="card-body p-0">
            <div class="p-3 p-md-4 border-bottom">
                <h5 class="mb-0">4. PIN directory</h5>
                <small class="text-secondary">Up to 100 matching records are shown.</small>
            </div>
            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0">
                    <thead class="table-light">
                    <tr>
                        <th>PIN</th><th>City / locality</th><th>District</th><th>State</th><th>Status</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if ($pins): ?>
                        <?php foreach ($pins as $row): ?>
                            <tr>
                                <td><?= esc($row['pincode']) ?></td>
                                <td><?= esc($row['city_name'] ?? '') ?></td>
                                <td><?= esc($row['district_name'] ?? '') ?></td>
                                <td><?= esc($row['state_name'] ?? '') ?></td>
                                <td><?= (int) $row['is_active'] === 1 ? 'Active' : 'Inactive' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="text-center text-secondary py-4">No PINs found. Import a CSV first.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>

</main>

<?= $this->endSection() ?>