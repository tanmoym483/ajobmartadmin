
<?= $this->extend('shipping_admin/layouts/main') ?>

<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Add Home Banner</h4>
    <a href="<?= site_url('admin/home-banners') ?>" class="btn btn-outline-secondary">
        Back to Banners
    </a>
</div>

<form id="bannerForm"
      action="<?= site_url('admin/home-banners/store') ?>"
      method="post"
      enctype="multipart/form-data">

    <?= csrf_field() ?>

    <div id="formMessage" class="alert d-none" role="alert"></div>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Banner Details</h5>
        </div>

        <div class="card-body">

            <div class="mb-3">
                <label class="form-label">Banner Title</label>
                <input type="text"
                       name="title"
                       class="form-control"
                       placeholder="Example: Special Deals"
                       maxlength="150"
                       required>
                <div class="invalid-feedback" data-error="title"></div>
            </div>

            <div class="mb-3">
                <label class="form-label">Banner Image</label>

                <input type="file"
                    name="image"
                    id="bannerImage"
                    class="form-control"
                    accept=".jpg,.jpeg,.png,.webp,.avif"
                    required>

                <small class="text-muted">
                    Recommended: 1200 × 400 px. JPG, PNG or WebP.
                    Maximum 512 KB. Minimum 600 × 200 px.
                    Aspect ratio: 2:1 to 4:1.
                </small>

                <div class="invalid-feedback" data-error="image"></div>

                <div class="mt-3">
                    <img id="bannerPreview"
                         src=""
                         alt="Banner Preview"
                         style="display:none;width:100%;max-width:450px;border-radius:8px;">
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Destination URL</label>
                <input type="text"
                       name="target_url"
                       class="form-control"
                       placeholder="/products/search?category=3"
                       maxlength="500">
                <div class="invalid-feedback" data-error="target_url"></div>
            </div>

            <div class="mb-3">
                <label class="form-label">Open Link</label>
                <select name="open_new_tab" class="form-select">
                    <option value="0">Same Tab</option>
                    <option value="1">New Tab</option>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Display Order</label>
                <input type="number"
                       name="sort_order"
                       class="form-control"
                       value="0"
                       min="0">
                <div class="invalid-feedback" data-error="sort_order"></div>
            </div>

            <div class="mb-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>

        </div>

        <div class="card-footer">
            <button type="submit" id="submitBanner" class="btn btn-primary">
                <span class="button-text">Save Banner</span>
                <span class="spinner-border spinner-border-sm ms-2 d-none"
                      id="submitSpinner"></span>
            </button>
        </div>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function () {

    const form = document.getElementById('bannerForm');
    const imageInput = document.getElementById('bannerImage');
    const preview = document.getElementById('bannerPreview');
    const message = document.getElementById('formMessage');
    const submitButton = document.getElementById('submitBanner');
    const spinner = document.getElementById('submitSpinner');

    let previewUrl = null;

    function showMessage(type, text) {
        message.className = 'alert alert-' + type;
        message.textContent = text;
        message.classList.remove('d-none');
    }

    function clearMessage() {
        message.className = 'alert d-none';
        message.textContent = '';
    }

    function clearErrors() {
        form.querySelectorAll('.is-invalid').forEach(function (field) {
            field.classList.remove('is-invalid');
        });

        form.querySelectorAll('[data-error]').forEach(function (element) {
            element.textContent = '';
        });
    }

    function showErrors(errors) {
        Object.keys(errors || {}).forEach(function (fieldName) {
            const field = form.querySelector('[name="' + fieldName + '"]');
            const errorElement = form.querySelector('[data-error="' + fieldName + '"]');

            if (field) {
                field.classList.add('is-invalid');
            }

            if (errorElement) {
                errorElement.textContent = errors[fieldName];
            }
        });
    }

    imageInput.addEventListener('change', function () {
        const file = this.files[0];

        if (previewUrl) {
            URL.revokeObjectURL(previewUrl);
            previewUrl = null;
        }

        if (!file) {
            preview.style.display = 'none';
            preview.removeAttribute('src');
            return;
        }

        previewUrl = URL.createObjectURL(file);
        preview.src = previewUrl;
        preview.style.display = 'block';
    });

    form.addEventListener('submit', async function (event) {
        event.preventDefault();

        clearMessage();
        clearErrors();

        if (!form.reportValidity()) {
            return;
        }

        submitButton.disabled = true;
        spinner.classList.remove('d-none');

        try {
            const formData = new FormData(form);

            const response = await fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            });

            const result = await response.json();

            // Update CSRF token if the server returned a fresh hash.
            if (result.csrfHash) {
                const csrfInput = form.querySelector(
                    'input[name="<?= csrf_token() ?>"]'
                );

                if (csrfInput) {
                    csrfInput.value = result.csrfHash;
                }
            }

            if (!response.ok || result.status !== 'success') {
                showMessage('danger', result.message || 'Unable to save banner.');
                showErrors(result.errors || {});
                return;
            }

            showMessage('success', result.message || 'Banner added successfully.');

            form.reset();
            clearErrors();

            if (previewUrl) {
                URL.revokeObjectURL(previewUrl);
                previewUrl = null;
            }

            preview.style.display = 'none';
            preview.removeAttribute('src');

        } catch (error) {
            console.error('Banner submission error:', error);
            showMessage('danger', 'Something went wrong. Please try again.');
        } finally {
            submitButton.disabled = false;
            spinner.classList.add('d-none');
        }
    });

});
</script>

<?= $this->endSection() ?>
