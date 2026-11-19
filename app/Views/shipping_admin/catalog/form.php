<?= $this->extend('shipping_admin/layouts/main') ?>
<?= $this->section('content') ?>

<?php
$isEdit = !empty($row['id']);
$name = old('name', $row['name'] ?? '');
$statusDefault = ($type === 'categories' || $type === 'attributes') ? 'active' : '1';
$status = old('status', $row['status'] ?? $statusDefault);
$categorySelected = (string)old('category_id', $row['category_id'] ?? '');
$subcategorySelected = (string)old('subcategory_id', $row['subcategory_id'] ?? '');
$producttypeSelected = (string)old(
    'product_type_id',
    $row['product_type_id'] ?? $row['producttype_id'] ?? ''
);

$options = '';
if (!empty($row['options_json'])) {
    $decoded = json_decode($row['options_json'], true);
    if (is_array($decoded)) $options = implode("\n", $decoded);
}
$options = old('options', $options);
?>

<div class="d-flex justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h1 class="admin-page-title"><?= esc($pageTitle) ?></h1>
        <div class="admin-page-subtitle">Enter the catalog details below</div>
    </div>
    <a class="btn btn-outline-secondary" href="<?= site_url('shipping-admin/catalog/' . $type) ?>"><i class="bi bi-arrow-left me-1"></i> Back</a>
</div>

<?php if ($errors = session()->getFlashdata('errors')): ?>
<div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $error): ?><li><?= esc($error) ?></li><?php endforeach ?></ul></div>
<?php endif ?>

<div class="admin-card">
<div class="p-3 p-md-4">
<form method="post" action="<?= esc($action) ?>">
    <?= csrf_field() ?>

    <?php if ($type === 'subcategories' || $type === 'attributes'): ?>
    <div class="mb-3">
        <label class="form-label fw-semibold">Category <span class="text-danger">*</span></label>
        <select class="form-select" name="category_id" id="catalogCategory" required>
            <option value="">Select category</option>
            <?php foreach ($categories as $c): ?>
            <option value="<?= (int)$c['id'] ?>" <?= $categorySelected === (string)$c['id'] ? 'selected' : '' ?>><?= esc($c['name']) ?></option>
            <?php endforeach ?>
        </select>
    </div>
    <?php endif ?>

    <?php if ($type === 'product-types' || $type === 'attributes'): ?>
    <div class="mb-3">
        <label class="form-label fw-semibold">Subcategory <?= $type === 'product-types' ? '<span class="text-danger">*</span>' : '<span class="text-muted">(optional — blank means all subcategories)</span>' ?></label>
        <select class="form-select" name="subcategory_id" id="catalogSubcategory" <?= $type === 'product-types' ? 'required' : '' ?>>
            <option value="">Select subcategory</option>
            <?php foreach ($subcategories as $s): ?>
            <option value="<?= (int)$s['id'] ?>" data-category="<?= (int)$s['category_id'] ?>" <?= $subcategorySelected === (string)$s['id'] ? 'selected' : '' ?>><?= esc($s['category_name'] . ' / ' . $s['name']) ?></option>
            <?php endforeach ?>
        </select>
    </div>
    <?php endif ?>

    <?php if ($type === 'attributes'): ?>
        <div class="mb-3">
        <label class="form-label fw-semibold">Product Type <?= $type === 'attributes' ? '<span class="text-danger">*</span>' : '<span class="text-muted">(optional — blank means all Product Type)</span>' ?></label>
        <select class="form-select" name="product_type_id" id="catalogProducttype" <?= $type === 'attributes' ? 'required' : '' ?>>
            <option value="">Select product type</option>
            <?php foreach ($producttype as $p): ?>
            <option value="<?= (int)$p['id'] ?>" data-subcategory="<?= (int)$p['subcategory_id'] ?>" <?= $producttypeSelected === (string)$p['id'] ? 'selected' : '' ?>><?= esc($p['subcategory_name'] . ' / ' . $p['name']) ?></option>
            <?php endforeach ?>
        </select>
    </div>

    <div class="mb-3">
        <label class="form-label fw-semibold">Attribute Key <span class="text-danger">*</span></label>
        <input class="form-control" name="attribute_name" value="<?= esc(old('attribute_name', $row['attribute_name'] ?? '')) ?>" placeholder="e.g. net_weight" required>
        <div class="form-text">Use lowercase letters, numbers and underscores. This key is used by the seller product form.</div>
    </div>
    <div class="mb-3">
        <label class="form-label fw-semibold">Attribute Label <span class="text-danger">*</span></label>
        <input class="form-control" name="label" value="<?= esc(old('label', $row['label'] ?? '')) ?>" placeholder="e.g. Net Weight" required>
    </div>
    <div class="mb-3">
        <label class="form-label fw-semibold">Input Type</label>
        <?php $inputType = old('input_type', $row['input_type'] ?? 'text'); ?>
        <select class="form-select" name="input_type" id="attributeInputType">
            <?php foreach (['text'=>'Text','number'=>'Number','textarea'=>'Textarea','select'=>'Dropdown / Select','date'=>'Date','checkbox'=>'Checkbox'] as $v=>$label): ?>
            <option value="<?= $v ?>" <?= $inputType === $v ? 'selected' : '' ?>><?= $label ?></option>
            <?php endforeach ?>
        </select>
    </div>
    <div class="mb-3" id="attributeOptionsWrap">
        <label class="form-label fw-semibold">Options</label>
        <textarea class="form-control" rows="5" name="options" placeholder="One option per line"><?= esc($options) ?></textarea>
        <div class="form-text">Required for Dropdown and Checkbox. Enter one option on each line.</div>
    </div>
    <div class="form-check mb-3">
        <input class="form-check-input" type="checkbox" name="is_required" value="1" id="isRequired" <?= old('is_required', $row['is_required'] ?? 0) ? 'checked' : '' ?>>
        <label class="form-check-label" for="isRequired">Required field</label>
    </div>
    <?php else: ?>
    <div class="mb-3">
        <label class="form-label fw-semibold"><?= $type === 'categories' ? 'Category Name' : ($type === 'subcategories' ? 'Subcategory Name' : 'Product Type Name') ?> <span class="text-danger">*</span></label>
        <input class="form-control" name="name" value="<?= esc($name) ?>" maxlength="<?= $type === 'categories' ? 100 : 150 ?>" required placeholder="Enter name">
    </div>
    <?php endif ?>

    <?php if ($type === 'subcategories' || $type === 'product-types'): ?>
    <div class="mb-3">
        <label class="form-label fw-semibold">Description</label>
        <textarea class="form-control" name="description" rows="3"><?= esc(old('description', $row['description'] ?? '')) ?></textarea>
    </div>
    <?php endif ?>

    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label fw-semibold">Sort Order</label>
            <input type="number" class="form-control" name="sort_order" min="0" value="<?= esc(old('sort_order', $row['sort_order'] ?? 0)) ?>">
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label fw-semibold">Status</label>
            <select class="form-select" name="status">
                <?php if ($type === 'categories' || $type === 'attributes'): ?>
                <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active</option>
                <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                <?php else: ?>
                <option value="1" <?= (string)$status === '1' ? 'selected' : '' ?>>Active</option>
                <option value="0" <?= (string)$status === '0' ? 'selected' : '' ?>>Inactive</option>
                <?php endif ?>
            </select>
        </div>
    </div>

    <div class="d-flex gap-2">
        <button class="btn btn-primary"><i class="bi bi-check-lg me-1"></i><?= $isEdit ? 'Update' : 'Save' ?></button>
        <a class="btn btn-light border" href="<?= site_url('shipping-admin/catalog/' . $type) ?>">Cancel</a>
    </div>
</form>
</div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
(function () {
    const category = document.getElementById('catalogCategory');
    const subcategory = document.getElementById('catalogSubcategory');
    const typeSelect = document.getElementById('attributeInputType');
    const optionsWrap = document.getElementById('attributeOptionsWrap');

    function filterSubcategories() {
        if (!category || !subcategory) return;
        const selectedCategory = category.value;
        Array.from(subcategory.options).forEach((option, index) => {
            if (index === 0) return;
            option.hidden = selectedCategory !== '' && option.dataset.category !== selectedCategory;
        });
        if (subcategory.selectedOptions.length && subcategory.selectedOptions[0].hidden) {
            subcategory.value = '';
        }
    }

    category?.addEventListener('change', filterSubcategories);
    filterSubcategories();

    function toggleOptions() {
        if (!typeSelect || !optionsWrap) return;
        optionsWrap.style.display = ['select', 'checkbox'].includes(typeSelect.value) ? '' : 'none';
    }
    typeSelect?.addEventListener('change', toggleOptions);
    toggleOptions();
})();
</script>
<?= $this->endSection() ?>
