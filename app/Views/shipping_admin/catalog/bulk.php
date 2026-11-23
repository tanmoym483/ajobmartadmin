
<?= $this->extend('shipping_admin/layouts/main') ?>
<?= $this->section('content') ?>

<?php
$isAttribute = $type === 'attributes';
$isSubcategory = $type === 'subcategories';
$isProductType = $type === 'product-types';
$isCategory = $type === 'categories';
?>

<!-- PAGE HEADER -->
<div class="d-flex justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h1 class="admin-page-title"><?= esc($pageTitle) ?></h1>
        <div class="admin-page-subtitle">
            Add multiple records and save them together
        </div>
    </div>

    <a class="btn btn-outline-secondary"
       href="<?= site_url('shipping-admin/catalog/' . $type) ?>">
        <i class="bi bi-arrow-left me-1"></i> Back
    </a>
</div>

<!-- FLASH MESSAGES -->
<?php if ($errors = session()->getFlashdata('errors')): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($errors as $e): ?>
                <li><?= esc($e) ?></li>
            <?php endforeach ?>
        </ul>
    </div>
<?php endif ?>

<?php if ($error = session()->getFlashdata('error')): ?>
    <div class="alert alert-danger"><?= esc($error) ?></div>
<?php endif ?>


<div class="admin-card">
    <div class="p-3 p-md-4">

        <form method="post"
              action="<?= site_url('shipping-admin/catalog/' . $type . '/bulk/save') ?>"
              id="catalogBulkForm"
              novalidate>

            <?= csrf_field() ?>


            <!-- GLOBAL CATEGORY FOR SUBCATEGORY / PRODUCT TYPE -->
            <?php if ($isSubcategory || $isProductType): ?>

                <div class="mb-3">
                    <label class="form-label fw-semibold">
                        Category <span class="text-danger">*</span>
                    </label>

                    <select class="form-select"
                            id="bulkCategory"
                            name="category_id"
                            required>

                        <option value="">Select category</option>

                        <?php foreach ($categories as $c): ?>
                            <option value="<?= (int)$c['id'] ?>">
                                <?= esc($c['name']) ?>
                            </option>
                        <?php endforeach ?>

                    </select>
                </div>

            <?php endif ?>


            <!-- GLOBAL SUBCATEGORY FOR PRODUCT TYPE -->
            <?php if ($isProductType): ?>

                <div class="mb-3">
                    <label class="form-label fw-semibold">
                        Subcategory <span class="text-danger">*</span>
                    </label>

                    <select class="form-select"
                            id="bulkSubcategory"
                            name="subcategory_id"
                            required
                            disabled>

                        <option value="">Select category first</option>

                    </select>
                </div>

            <?php endif ?>


            <!-- SINGLE RECORD ENTRY -->
            <div class="admin-card border mb-4">
                <div class="p-3">

                    <h5 class="mb-3">Add New Record</h5>

                    <div id="bulkEntry"></div>

                    <div class="d-flex justify-content-end gap-2 mt-3">

                        <?php if ($isAttribute): ?>

                            <button type="button"
                                    class="btn btn-outline-primary"
                                    id="openExistingAttributes">

                                <i class="bi bi-list-check me-1"></i>
                                Choose Existing Attributes

                            </button>

                        <?php endif ?>

                        <button type="button"
                                class="btn btn-primary"
                                id="addBulkRow">

                            <i class="bi bi-plus-lg me-1"></i>
                            Add to Table

                        </button>

                    </div>

                </div>
            </div>


            <!-- ADDED RECORDS TABLE -->
            <div class="admin-card border">

                <div class="p-3">

                    <div class="d-flex justify-content-between align-items-center mb-3">

                        <h5 class="mb-0">Added Records</h5>

                        <span class="badge bg-primary"
                              id="bulkCount">
                            0 Records
                        </span>

                    </div>

                    <div class="table-responsive">

                        <table class="table table-bordered table-hover align-middle">

                            <thead id="bulkTableHead"></thead>

                            <tbody id="bulkTableBody">

                                <tr>
                                    <td class="text-center text-muted py-4">
                                        No records added yet.
                                    </td>
                                </tr>

                            </tbody>

                        </table>

                    </div>

                    <!-- HIDDEN INPUTS FOR SUBMISSION -->
                    <div id="bulkHiddenRows"></div>

                </div>

            </div>


            <!-- SAVE BUTTONS -->
            <div class="mt-4 d-flex gap-2">

                <button class="btn btn-primary" type="submit">
                    <i class="bi bi-check-lg me-1"></i>
                    Save All
                </button>

                <a class="btn btn-light border"
                   href="<?= site_url('shipping-admin/catalog/' . $type) ?>">
                    Cancel
                </a>

            </div>

        </form>

    </div>
</div>


<!-- SINGLE RECORD TEMPLATE -->
<template id="bulkRowTemplate">

    <div class="border rounded-3 p-3 bulk-row">

        <div class="mb-3">
            <strong class="row-title">New Record</strong>
        </div>


        <?php if ($isAttribute): ?>

            <!-- ATTRIBUTE CATEGORY / SUBCATEGORY / PRODUCT TYPE -->
            <div class="row g-3 mb-3">

                <div class="col-md-4">

                    <label class="form-label">
                        Category <span class="text-danger">*</span>
                    </label>

                    <select class="form-select attribute-category"
                            data-field="category_id"
                            >

                        <option value="">Select category</option>

                        <?php foreach ($categories as $c): ?>
                            <option value="<?= (int)$c['id'] ?>">
                                <?= esc($c['name']) ?>
                            </option>
                        <?php endforeach ?>

                    </select>

                </div>


                <div class="col-md-4">

                    <label class="form-label">
                        Subcategory <span class="text-danger">*</span>
                    </label>

                    <select class="form-select attribute-subcategory"
                            data-field="subcategory_id"
                            
                            disabled>

                        <option value="">Select category first</option>

                    </select>

                </div>


                <div class="col-md-4">

                    <label class="form-label">
                        Product Type <span class="text-danger">*</span>
                    </label>

                    <select class="form-select attribute-product-type"
                            data-field="product_type_id"
                            
                            disabled>

                        <option value="">Select subcategory first</option>

                    </select>

                </div>

            </div>


            <!-- ATTRIBUTE DETAILS -->
            <div class="row g-3">

                <div class="col-md-4">

                    <label class="form-label">
                        Attribute Key <span class="text-danger">*</span>
                    </label>

                    <input type="text"
                           class="form-control"
                           data-field="attribute_name"
                           placeholder="net_weight"
                           >

                </div>


                <div class="col-md-4">

                    <label class="form-label">
                        Label <span class="text-danger">*</span>
                    </label>

                    <input type="text"
                           class="form-control"
                           data-field="label"
                           placeholder="Net Weight"
                           >

                </div>


                <div class="col-md-4">

                    <label class="form-label">Input Type</label>

                    <select class="form-select attribute-input"
                            data-field="input_type">

                        <option value="text">Text</option>
                        <option value="number">Number</option>
                        <option value="textarea">Textarea</option>
                        <option value="select">Dropdown / Select</option>
                        <option value="date">Date</option>
                        <option value="checkbox">Checkbox</option>

                    </select>

                </div>


                <div class="col-md-6 options-field"
                     style="display:none;">

                    <label class="form-label">
                        Options (one per line)
                    </label>

                    <textarea class="form-control"
                              data-field="options"
                              rows="3"
                              placeholder="500g&#10;1kg&#10;5kg"></textarea>

                </div>


                <div class="col-md-3">

                    <label class="form-label">Sort Order</label>

                    <input type="number"
                           min="0"
                           class="form-control"
                           data-field="sort_order"
                           value="0">

                </div>


                <div class="col-md-3 d-flex align-items-end">

                    <div class="form-check mb-2">

                        <input type="checkbox"
                               class="form-check-input"
                               data-field="is_required"
                               value="1">

                        <label class="form-check-label">
                            Required
                        </label>

                    </div>

                </div>


                <div class="col-md-3">

                    <label class="form-label">Status</label>

                    <select class="form-select"
                            data-field="status">

                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>

                    </select>

                </div>

            </div>


        <?php else: ?>

            <!-- CATEGORY / SUBCATEGORY / PRODUCT TYPE DETAILS -->
            <div class="row g-3">

                <div class="col-md-6">

                    <label class="form-label">
                        <?= $isCategory ? 'Category Name' : ($isSubcategory ? 'Subcategory Name' : 'Product Type Name') ?>
                        <span class="text-danger">*</span>
                    </label>

                    <input type="text"
                           class="form-control"
                           data-field="name"
                           >

                </div>


                <?php if (!$isCategory): ?>

                    <div class="col-md-6">

                        <label class="form-label">Description</label>

                        <textarea class="form-control"
                                  data-field="description"
                                  rows="2"></textarea>

                    </div>

                <?php endif ?>


                <div class="col-md-3">

                    <label class="form-label">Sort Order</label>

                    <input type="number"
                           min="0"
                           class="form-control"
                           data-field="sort_order"
                           value="0">

                </div>


                <div class="col-md-3">

                    <label class="form-label">Status</label>

                    <select class="form-select"
                            data-field="status">

                        <?php if ($isCategory): ?>

                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>

                        <?php else: ?>

                            <option value="1">Active</option>
                            <option value="0">Inactive</option>

                        <?php endif ?>

                    </select>

                </div>

            </div>

        <?php endif ?>

    </div>

</template>


<?php if ($isAttribute): ?>

<div class="modal fade"
     id="existingAttributesModal"
     tabindex="-1"
     aria-hidden="true">

    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">

        <div class="modal-content">

            <div class="modal-header">

                <h5 class="modal-title">
                    <i class="bi bi-list-check me-2"></i>
                    Choose Existing Attributes
                </h5>

                <button type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"></button>

            </div>

            <div class="modal-body">

                <div class="alert alert-info">
                    Select the attributes you want to add to this product type.
                </div>

                <div class="mb-3">
                    <input type="text"
                           class="form-control"
                           id="existingAttributeSearch"
                           placeholder="Search attributes...">
                </div>

                <div id="existingAttributesLoading"
                     class="text-center py-4"
                     style="display:none;">

                    <div class="spinner-border text-primary"></div>
                    <div class="mt-2">Loading attributes...</div>

                </div>

                <div id="existingAttributesMessage"
                     class="text-center text-muted py-4"
                     style="display:none;"></div>

                <div class="table-responsive">

                    <table class="table table-bordered table-hover align-middle">

                        <thead>
                            <tr>
                                <th style="width:45px;">
                                    <input type="checkbox"
                                           id="selectAllExistingAttributes">
                                </th>
                                <th>Attribute Key</th>
                                <th>Label</th>
                                <th>Input Type</th>
                                <th>Options</th>
                            </tr>
                        </thead>

                        <tbody id="existingAttributesBody"></tbody>

                    </table>

                </div>

            </div>

            <div class="modal-footer">

                <span class="me-auto text-muted"
                      id="selectedExistingAttributeCount">
                    0 selected
                </span>

                <button type="button"
                        class="btn btn-light border"
                        data-bs-dismiss="modal">
                    Cancel
                </button>

                <button type="button"
                        class="btn btn-primary"
                        id="addSelectedExistingAttributes">

                    <i class="bi bi-plus-lg me-1"></i>
                    Add Selected

                </button>

            </div>

        </div>

    </div>

</div>

<?php endif ?>

<?= $this->endSection() ?>


<?= $this->section('scripts') ?>

<script>
(function () {

    'use strict';

    const type = <?= json_encode($type) ?>;

    const isAttribute = type === 'attributes';
    const isSubcategory = type === 'subcategories';
    const isProductType = type === 'product-types';

    const form = document.getElementById('catalogBulkForm');
    const entry = document.getElementById('bulkEntry');
    const template = document.getElementById('bulkRowTemplate');

    const addButton = document.getElementById('addBulkRow');

    const tableHead = document.getElementById('bulkTableHead');
    const tableBody = document.getElementById('bulkTableBody');

    const hiddenRows = document.getElementById('bulkHiddenRows');
    const countLabel = document.getElementById('bulkCount');

    let count = 0;


    // ------------------------------------------
    // API URLS
    // ------------------------------------------

    const subcategoryUrl =
        <?= json_encode(site_url('shipping-admin/catalog/subcategories-json')) ?>;

    const productTypeUrl =
        <?= json_encode(site_url('shipping-admin/catalog/product-types-json')) ?>;
    
    const existingAttributesUrl =
    <?= json_encode(site_url('shipping-admin/catalog/existing-attributes-json')) ?>;


    // ------------------------------------------
    // TABLE COLUMNS
    // ------------------------------------------

    let columns;

    if (isAttribute) {

        columns = [
            ['Category', 'category_name'],
            ['Subcategory', 'subcategory_name'],
            ['Product Type', 'product_type_name'],
            ['Attribute Key', 'attribute_name'],
            ['Label', 'label'],
            ['Input Type', 'input_type'],
            ['Options', 'options'],
            ['Sort Order', 'sort_order'],
            ['Required', 'is_required'],
            ['Status', 'status']
        ];

    } else if (type === 'categories') {

        columns = [
            ['Category Name', 'name'],
            ['Sort Order', 'sort_order'],
            ['Status', 'status']
        ];

    } else {

        columns = [
            ['Name', 'name'],
            ['Description', 'description'],
            ['Sort Order', 'sort_order'],
            ['Status', 'status']
        ];

    }


    // ------------------------------------------
    // TABLE HEADER
    // ------------------------------------------

    tableHead.innerHTML = `
        <tr>
            ${columns.map(([label]) => `<th>${label}</th>`).join('')}
            <th style="width:90px;">Action</th>
        </tr>
    `;


    // ------------------------------------------
    // LOAD DROPDOWN DATA
    // ------------------------------------------

    async function loadOptions(url, select, placeholder) {

        select.innerHTML = '<option value="">Loading...</option>';
        select.disabled = true;

        try {

            const response = await fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) {
                throw new Error('Unable to load records');
            }

            const data = await response.json();

            select.innerHTML = '';

            const firstOption = document.createElement('option');

            firstOption.value = '';
            firstOption.textContent = placeholder;

            select.appendChild(firstOption);

            (data.items || []).forEach(item => {

                const option = document.createElement('option');

                option.value = item.id;
                option.textContent = item.name;

                select.appendChild(option);

            });

            select.disabled = false;

        } catch (error) {

            console.error(error);

            select.innerHTML =
                '<option value="">Unable to load</option>';

            select.disabled = true;

        }

    }


    // ------------------------------------------
    // CREATE ENTRY FORM
    // ------------------------------------------

    const node = template.content.firstElementChild.cloneNode(true);

    node.querySelector('.row-title').textContent =
        'Enter Record Details';

    entry.appendChild(node);


    // ------------------------------------------
    // ATTRIBUTE DEPENDENT DROPDOWNS
    // ------------------------------------------

    if (isAttribute) {

        const category =
            node.querySelector('.attribute-category');

        const subcategory =
            node.querySelector('.attribute-subcategory');

        const productType =
            node.querySelector('.attribute-product-type');


        category.addEventListener('change', function () {

            subcategory.innerHTML =
                '<option value="">Select category first</option>';

            subcategory.disabled = true;

            productType.innerHTML =
                '<option value="">Select subcategory first</option>';

            productType.disabled = true;

            if (!category.value) return;

            loadOptions(
                subcategoryUrl + '?category_id=' +
                encodeURIComponent(category.value),

                subcategory,
                'Select subcategory'
            );

        });


        subcategory.addEventListener('change', function () {

            productType.innerHTML =
                '<option value="">Select subcategory first</option>';

            productType.disabled = true;

            if (!subcategory.value) return;

            loadOptions(
                productTypeUrl + '?subcategory_id=' +
                encodeURIComponent(subcategory.value),

                productType,
                'Select product type'
            );

        });

    }


    // ------------------------------------------
    // ATTRIBUTE INPUT TYPE OPTIONS
    // ------------------------------------------

    const inputType = node.querySelector('.attribute-input');

    if (inputType) {

        const optionsWrap = node.querySelector('.options-field');

        function toggleOptions() {

            const show = ['select', 'checkbox'].includes(inputType.value);

            optionsWrap.style.display = show ? '' : 'none';

            if (!show) {
                optionsWrap.querySelector('textarea').value = '';
            }

        }

        inputType.addEventListener('change', toggleOptions);

        toggleOptions();

    }


    // ------------------------------------------
    // RESET ENTRY FORM
    // ------------------------------------------

    function resetEntry() {

        node.querySelectorAll('[data-field]').forEach(field => {

            if (field.type === 'checkbox') {

                field.checked = false;

            } else if (field.type === 'number') {

                field.value = '0';

            } else if (field.tagName === 'SELECT') {

                field.selectedIndex = 0;

            } else {

                field.value = '';

            }

        });


        // Reset dependent attribute dropdowns
        if (isAttribute) {

            const category =
                node.querySelector('.attribute-category');

            const subcategory =
                node.querySelector('.attribute-subcategory');

            const productType =
                node.querySelector('.attribute-product-type');

            category.selectedIndex = 0;

            subcategory.innerHTML =
                '<option value="">Select category first</option>';

            subcategory.disabled = true;

            productType.innerHTML =
                '<option value="">Select subcategory first</option>';

            productType.disabled = true;

        }


        if (inputType) {
    inputType.dispatchEvent(new Event('change'));
}

    }


    // ------------------------------------------
    // ADD RECORD TO TABLE
    // ------------------------------------------

    function addRecord() {

        const fields = [
            ...node.querySelectorAll('[data-field]')
        ];

        // Validate the current entry only when "Add to Table" is clicked.
        // The form has novalidate so a reset/empty entry cannot block Save All.
        let requiredFields = [];

        if (isAttribute) {
            requiredFields = [
                node.querySelector('.attribute-category'),
                node.querySelector('.attribute-subcategory'),
                node.querySelector('.attribute-product-type'),
                node.querySelector('[data-field="attribute_name"]'),
                node.querySelector('[data-field="label"]')
            ];

            const currentInputType = node.querySelector('.attribute-input')?.value;
            if (['select', 'checkbox'].includes(currentInputType)) {
                requiredFields.push(node.querySelector('[data-field="options"]'));
            }
        } else {
            requiredFields = [
                node.querySelector('[data-field="name"]')
            ];
        }

        for (const field of requiredFields) {
            if (!field || !String(field.value || '').trim()) {
                alert('Please fill all required fields before adding this record.');
                if (field) {
                    field.focus();
                    if (typeof field.reportValidity === 'function') {
                        // Display the browser's validation hint without blocking Save All.
                        field.setCustomValidity('Please complete this field.');
                        field.reportValidity();
                        field.setCustomValidity('');
                    }
                }
                return;
            }
        }

        // Collect database values
        const record = {};

        fields.forEach(field => {

            const key = field.dataset.field;

            record[key] = field.type === 'checkbox'
                ? (field.checked ? '1' : '0')
                : field.value.trim();

        });


        // Collect selected dropdown display names
        const displayRecord = { ...record };

        if (isAttribute) {

            displayRecord.category_name =
                node.querySelector('.attribute-category')
                    .selectedOptions[0].textContent;

            displayRecord.subcategory_name =
                node.querySelector('.attribute-subcategory')
                    .selectedOptions[0].textContent;

            displayRecord.product_type_name =
                node.querySelector('.attribute-product-type')
                    .selectedOptions[0].textContent;

        }


        // Remove empty message
        if (tableBody.querySelector('.text-muted')) {
            tableBody.innerHTML = '';
        }


        // Create table row
        const tr = document.createElement('tr');

        columns.forEach(([label, key]) => {

            const td = document.createElement('td');

            let value = displayRecord[key] ?? '';

            if (key === 'is_required') {

                value = value === '1' ? 'Yes' : 'No';

            }

            if (key === 'options') {

                value = value
                    ? value.split('\n').join(', ')
                    : '-';

            }

            if (key === 'status') {

                value = ['active', '1'].includes(value)
                    ? 'Active'
                    : 'Inactive';

            }

            td.textContent = value || '-';

            tr.appendChild(td);

        });


        // Create hidden inputs for submission
        const index = count++;

        const hiddenGroup = document.createElement('div');

        hiddenGroup.dataset.recordIndex = index;


        // Only submit actual database fields.
        // Display names are not submitted.
        Object.entries(record).forEach(([key, value]) => {

            const input = document.createElement('input');

            input.type = 'hidden';

            input.name = `rows[${index}][${key}]`;

            input.value = value;

            hiddenGroup.appendChild(input);

        });


        hiddenRows.appendChild(hiddenGroup);


        // Remove button
        const actionTd = document.createElement('td');

        const removeButton = document.createElement('button');

        removeButton.type = 'button';

        removeButton.className =
            'btn btn-sm btn-outline-danger';

        removeButton.innerHTML =
            '<i class="bi bi-trash"></i>';

        removeButton.title = 'Remove record';

        actionTd.appendChild(removeButton);

        tr.appendChild(actionTd);


        // Remove record from table and form
        removeButton.addEventListener('click', function () {

            tr.remove();

            hiddenGroup.remove();

            updateCount();

            if (!tableBody.children.length) {
                showEmptyMessage();
            }

        });


        tableBody.appendChild(tr);

        updateCount();

        // Reset input fields for next record
        resetEntry();

    }


    // ------------------------------------------
    // UPDATE COUNT
    // ------------------------------------------

    function updateCount() {

        const total = hiddenRows.children.length;

        countLabel.textContent = total + ' Records';

    }


    // ------------------------------------------
    // EMPTY TABLE MESSAGE
    // ------------------------------------------

    function showEmptyMessage() {

        const tr = document.createElement('tr');

        const td = document.createElement('td');

        td.colSpan = columns.length + 1;

        td.className =
            'text-center text-muted py-4';

        td.textContent =
            'No records added yet.';

        tr.appendChild(td);

        tableBody.appendChild(tr);

    }

    // ------------------------------------------
// EXISTING ATTRIBUTE MODAL
// ------------------------------------------

if (isAttribute) {

    const openExistingButton =
        document.getElementById('openExistingAttributes');

    const modalElement =
        document.getElementById('existingAttributesModal');

    const existingModal =
        bootstrap.Modal.getOrCreateInstance(modalElement);

    const existingBody =
        document.getElementById('existingAttributesBody');

    const loading =
        document.getElementById('existingAttributesLoading');

    const message =
        document.getElementById('existingAttributesMessage');

    const searchInput =
        document.getElementById('existingAttributeSearch');

    const selectAll =
        document.getElementById('selectAllExistingAttributes');

    const selectedCount =
        document.getElementById('selectedExistingAttributeCount');

    const addSelectedButton =
        document.getElementById('addSelectedExistingAttributes');

    let availableAttributes = [];


    // Load existing attributes
    async function fetchExistingAttributes() {

        const categoryId =
            node.querySelector('.attribute-category').value;

        const subcategoryId =
            node.querySelector('.attribute-subcategory').value;

        const productTypeId =
            node.querySelector('.attribute-product-type').value;

        if (!categoryId || !subcategoryId || !productTypeId) {

            alert('Please select Category, Subcategory and Product Type first.');
            return;

        }

        existingBody.innerHTML = '';

        message.style.display = 'none';
        loading.style.display = 'block';

        searchInput.value = '';
        selectAll.checked = false;

        selectedCount.textContent = '0 selected';

        existingModal.show();

        try {

            const url = new URL(existingAttributesUrl, window.location.origin);

            url.searchParams.set('category_id', categoryId);
            url.searchParams.set('subcategory_id', subcategoryId);
            url.searchParams.set('product_type_id', productTypeId);

            const response = await fetch(url.toString(), {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) {
                throw new Error('Unable to fetch existing attributes.');
            }

            const data = await response.json();

            loading.style.display = 'none';

            if (!data.status || !data.items || !data.items.length) {

                message.textContent =
                    data.message || 'No additional attributes found.';

                message.style.display = 'block';

                availableAttributes = [];

                return;

            }

            availableAttributes = data.items;

            renderExistingAttributes(availableAttributes);

        } catch (error) {

            console.error(error);

            loading.style.display = 'none';

            message.textContent =
                'Unable to load attributes. Please try again.';

            message.style.display = 'block';

        }

    }


    // Render modal table
    function renderExistingAttributes(items) {

        existingBody.innerHTML = '';

        if (!items.length) {

            message.textContent = 'No matching attributes found.';
            message.style.display = 'block';

            return;

        }

        message.style.display = 'none';

        items.forEach((item, index) => {

            const tr = document.createElement('tr');

            const checkboxTd = document.createElement('td');

            const checkbox = document.createElement('input');

            checkbox.type = 'checkbox';
            checkbox.className = 'form-check-input existing-attribute-check';
            checkbox.dataset.index = index;

            checkboxTd.appendChild(checkbox);

            tr.appendChild(checkboxTd);

            const keyTd = document.createElement('td');
            keyTd.textContent = item.attribute_name || '-';
            tr.appendChild(keyTd);

            const labelTd = document.createElement('td');
            labelTd.textContent = item.label || '-';
            tr.appendChild(labelTd);

            const typeTd = document.createElement('td');
            typeTd.textContent = item.input_type || '-';
            tr.appendChild(typeTd);

            const optionsTd = document.createElement('td');

            let options = [];

            try {

                options = item.options_json
                    ? JSON.parse(item.options_json)
                    : [];

            } catch (error) {

                options = [];

            }

            optionsTd.textContent =
                Array.isArray(options) ? options.join(', ') : '-';

            tr.appendChild(optionsTd);

            existingBody.appendChild(tr);

            checkbox.addEventListener('change', updateExistingCount);

        });

        updateExistingCount();

    }


    // Update selected count
    function updateExistingCount() {

        const checked = existingBody.querySelectorAll(
            '.existing-attribute-check:checked'
        ).length;

        selectedCount.textContent = checked + ' selected';

    }


    // Select all visible attributes
    selectAll.addEventListener('change', function () {

        existingBody.querySelectorAll('tr').forEach(tr => {

            if (tr.style.display === 'none') return;

            const checkbox =
                tr.querySelector('.existing-attribute-check');

            if (checkbox) {
                checkbox.checked = selectAll.checked;
            }

        });

        updateExistingCount();

    });


    // Search attributes
    searchInput.addEventListener('input', function () {

        const keyword = this.value.toLowerCase().trim();

        existingBody.querySelectorAll('tr').forEach(tr => {

            tr.style.display =
                tr.textContent.toLowerCase().includes(keyword)
                    ? ''
                    : 'none';

        });

        selectAll.checked = false;

    });


    // Add selected attributes to the main table
    addSelectedButton.addEventListener('click', function () {

        const category =
            node.querySelector('.attribute-category');

        const subcategory =
            node.querySelector('.attribute-subcategory');

        const productType =
            node.querySelector('.attribute-product-type');

        if (!category.value || !subcategory.value || !productType.value) {

            alert('Please select all three hierarchy fields.');
            return;

        }

        const checked = existingBody.querySelectorAll(
            '.existing-attribute-check:checked'
        );

        if (!checked.length) {

            alert('Please select at least one attribute.');
            return;

        }

        const categoryName =
            category.selectedOptions[0].textContent;

        const subcategoryName =
            subcategory.selectedOptions[0].textContent;

        const productTypeName =
            productType.selectedOptions[0].textContent;

        let added = 0;

        checked.forEach(checkbox => {

            const item =
                availableAttributes[Number(checkbox.dataset.index)];

            if (!item) return;

            // Avoid adding the same attribute twice to the pending table.
            const alreadyAdded = [
                ...hiddenRows.querySelectorAll('[data-record-index]')
            ].some(group => {

                const values = {};

                group.querySelectorAll('input').forEach(input => {

                    const match = input.name.match(/\[([^\]]+)\]$/);

                    if (match) {
                        values[match[1]] = input.value;
                    }

                });

                return values.product_type_id === productType.value &&
                       values.attribute_name === item.attribute_name;

            });

            if (alreadyAdded) return;

            let options = [];

            try {

                options = item.options_json
                    ? JSON.parse(item.options_json)
                    : [];

            } catch (error) {

                options = [];

            }

            const record = {

                category_id: category.value,
                subcategory_id: subcategory.value,
                product_type_id: productType.value,

                attribute_name: item.attribute_name || '',
                label: item.label || '',
                input_type: item.input_type || 'text',

                options: Array.isArray(options)
                    ? options.join('\n')
                    : '',

                sort_order: '0',

                is_required: String(item.is_required ?? '0'),

                status: 'active'

            };

            const displayRecord = {

                ...record,

                category_name: categoryName,
                subcategory_name: subcategoryName,
                product_type_name: productTypeName

            };

            // Add visible table row.
            if (tableBody.querySelector('.text-muted')) {
                tableBody.innerHTML = '';
            }

            const tr = document.createElement('tr');

            columns.forEach(([label, key]) => {

                const td = document.createElement('td');

                let value = displayRecord[key] ?? '';

                if (key === 'is_required') {
                    value = value === '1' ? 'Yes' : 'No';
                }

                if (key === 'options') {
                    value = value ? value.split('\n').join(', ') : '-';
                }

                if (key === 'status') {
                    value = 'Active';
                }

                td.textContent = value || '-';

                tr.appendChild(td);

            });


            // Add hidden database fields.
            const index = count++;

            const hiddenGroup = document.createElement('div');

            hiddenGroup.dataset.recordIndex = index;

            Object.entries(record).forEach(([key, value]) => {

                const input = document.createElement('input');

                input.type = 'hidden';

                input.name = `rows[${index}][${key}]`;

                input.value = value;

                hiddenGroup.appendChild(input);

            });

            hiddenRows.appendChild(hiddenGroup);


            // Remove button.
            const actionTd = document.createElement('td');

            const removeButton = document.createElement('button');

            removeButton.type = 'button';

            removeButton.className =
                'btn btn-sm btn-outline-danger';

            removeButton.innerHTML =
                '<i class="bi bi-trash"></i>';

            removeButton.addEventListener('click', function () {

                tr.remove();
                hiddenGroup.remove();

                updateCount();

                if (!tableBody.children.length) {
                    showEmptyMessage();
                }

            });

            actionTd.appendChild(removeButton);

            tr.appendChild(actionTd);

            tableBody.appendChild(tr);

            added++;

        });

        updateCount();

        if (added === 0) {

            alert('Selected attributes are already present in the table.');

            return;

        }

        existingModal.hide();

        alert(added + ' existing attribute(s) added successfully.');

    });


    // Open modal button
    openExistingButton.addEventListener(
        'click',
        fetchExistingAttributes
    );

}

    // ------------------------------------------
    // ADD BUTTON
    // ------------------------------------------

    addButton.addEventListener('click', addRecord);


    // ------------------------------------------
    // FORM SUBMISSION
    // ------------------------------------------

    form.addEventListener('submit', function (e) {

        // At least one table row must be present.
        if (hiddenRows.children.length === 0) {
            e.preventDefault();
            alert('Please add at least one record before saving.');
            return;
        }

        // Validate global parent selections for these two catalog types.
        // Attribute records carry their own category/subcategory/product type IDs.
        if (isSubcategory || isProductType) {
            const globalCategory = document.getElementById('bulkCategory');

            if (globalCategory && !globalCategory.value) {
                e.preventDefault();
                alert('Please select a category before saving.');
                globalCategory.focus();
                return;
            }
        }

        if (isProductType) {
            const globalSubcategory = document.getElementById('bulkSubcategory');

            if (globalSubcategory && !globalSubcategory.value) {
                e.preventDefault();
                alert('Please select a subcategory before saving.');
                globalSubcategory.focus();
                return;
            }
        }

    });


    // ------------------------------------------
    // GLOBAL CATEGORY / SUBCATEGORY
    // FOR SUBCATEGORY AND PRODUCT TYPE
    // ------------------------------------------

    if (isSubcategory || isProductType) {

        const cat = document.getElementById('bulkCategory');

        const sub = document.getElementById('bulkSubcategory');


        if (isProductType) {

            cat.addEventListener('change', function () {

                sub.innerHTML =
                    '<option value="">Select category first</option>';

                sub.disabled = true;

                if (!cat.value) return;

                loadOptions(
                    subcategoryUrl + '?category_id=' +
                    encodeURIComponent(cat.value),

                    sub,
                    'Select subcategory'
                );

            });

        }

    }

})();
</script>

<?= $this->endSection() ?>
