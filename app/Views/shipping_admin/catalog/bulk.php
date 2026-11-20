<?= $this->extend('shipping_admin/layouts/main') ?>
<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center gap-3 mb-4">
 <div><h1 class="admin-page-title"><?= esc($pageTitle) ?></h1><div class="admin-page-subtitle">Add multiple records and save them together</div></div>
 <a class="btn btn-outline-secondary" href="<?= site_url('shipping-admin/catalog/' . $type) ?>"><i class="bi bi-arrow-left me-1"></i> Back</a>
</div>
<?php if ($errors = session()->getFlashdata('errors')): ?><div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $e): ?><li><?= esc($e) ?></li><?php endforeach ?></ul></div><?php endif ?>
<?php if ($error = session()->getFlashdata('error')): ?><div class="alert alert-danger"><?= esc($error) ?></div><?php endif ?>
<div class="admin-card"><div class="p-3 p-md-4">
<form method="post" action="<?= site_url('shipping-admin/catalog/' . $type . '/bulk/save') ?>" id="catalogBulkForm">
<?= csrf_field() ?>
<?php if ($type === 'subcategories' || $type === 'product-types' || $type === 'attributes'): ?>
<div class="mb-3"><label class="form-label fw-semibold">Category <span class="text-danger">*</span></label>
<select class="form-select" id="bulkCategory" name="category_id" <?= $type === 'subcategories' ? 'required' : '' ?>><option value="">Select category</option><?php foreach ($categories as $c): ?><option value="<?= (int)$c['id'] ?>"><?= esc($c['name']) ?></option><?php endforeach ?></select></div>
<?php endif ?>
<?php if ($type === 'product-types' || $type === 'attributes'): ?>
<div class="mb-3"><label class="form-label fw-semibold">Subcategory <span class="text-danger">*</span></label><select class="form-select" id="bulkSubcategory" name="subcategory_id" required disabled><option value="">Select category first</option></select></div>
<?php endif ?>
<?php if ($type === 'attributes'): ?>
<div class="mb-3"><label class="form-label fw-semibold">Product Type <span class="text-danger">*</span></label><select class="form-select" id="bulkProductType" name="product_type_id" required disabled><option value="">Select subcategory first</option></select></div>
<?php endif ?>
<div class="d-flex justify-content-between align-items-center mb-3"><h5 class="mb-0">Records to add</h5><button type="button" class="btn btn-sm btn-outline-primary" id="addBulkRow"><i class="bi bi-plus-lg me-1"></i> Add another</button></div>
<div id="bulkRows" class="vstack gap-3"></div>
<div class="mt-4 d-flex gap-2"><button class="btn btn-primary" type="submit"><i class="bi bi-check-lg me-1"></i> Save All</button><a class="btn btn-light border" href="<?= site_url('shipping-admin/catalog/' . $type) ?>">Cancel</a></div>
</form></div></div>
<template id="bulkRowTemplate">
<div class="border rounded-3 p-3 bulk-row"><div class="d-flex justify-content-between mb-3"><strong class="row-title">New record</strong><button type="button" class="btn btn-sm btn-outline-danger remove-row">Remove</button></div>
<?php if ($type === 'attributes'): ?>
<div class="row g-3"><div class="col-md-4"><label class="form-label">Attribute Key *</label><input class="form-control" data-field="attribute_name" placeholder="net_weight" required></div><div class="col-md-4"><label class="form-label">Label *</label><input class="form-control" data-field="label" placeholder="Net Weight" required></div><div class="col-md-4"><label class="form-label">Input Type</label><select class="form-select attribute-input" data-field="input_type"><option value="text">Text</option><option value="number">Number</option><option value="textarea">Textarea</option><option value="select">Dropdown / Select</option><option value="date">Date</option><option value="checkbox">Checkbox</option></select></div><div class="col-md-6 options-field" style="display:none"><label class="form-label">Options (one per line)</label><textarea class="form-control" data-field="options" rows="3"></textarea></div><div class="col-md-3"><label class="form-label">Sort Order</label><input type="number" min="0" class="form-control" data-field="sort_order" value="0"></div><div class="col-md-3 d-flex align-items-end"><div class="form-check mb-2"><input type="checkbox" class="form-check-input" data-field="is_required" value="1"><label class="form-check-label">Required</label></div></div><div class="col-md-3"><label class="form-label">Status</label><select class="form-select" data-field="status"><option value="active">Active</option><option value="inactive">Inactive</option></select></div></div>
<?php else: ?>
<div class="row g-3"><div class="col-md-6"><label class="form-label"><?= $type === 'categories' ? 'Category Name' : ($type === 'subcategories' ? 'Subcategory Name' : 'Product Type Name') ?> *</label><input class="form-control" data-field="name" required></div><?php if ($type !== 'categories'): ?><div class="col-md-6"><label class="form-label">Description</label><textarea class="form-control" data-field="description" rows="2"></textarea></div><?php endif ?><div class="col-md-3"><label class="form-label">Sort Order</label><input type="number" min="0" class="form-control" data-field="sort_order" value="0"></div><div class="col-md-3"><label class="form-label">Status</label><select class="form-select" data-field="status"><?php if ($type === 'categories'): ?><option value="active">Active</option><option value="inactive">Inactive</option><?php else: ?><option value="1">Active</option><option value="0">Inactive</option><?php endif ?></select></div></div>
<?php endif ?>
</div></template>
<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
(function(){
 const type = <?= json_encode($type) ?>;
 const rows = document.getElementById('bulkRows'), tpl = document.getElementById('bulkRowTemplate');
 let count=0;
 function addRow(){ const node=tpl.content.firstElementChild.cloneNode(true); const idx=count++; node.querySelector('.row-title').textContent='Record '+(idx+1); node.querySelectorAll('[data-field]').forEach(el=>{el.name='rows['+idx+']['+el.dataset.field+']';}); node.querySelector('.remove-row').addEventListener('click',()=>{if(rows.children.length>1)node.remove();}); const input=node.querySelector('.attribute-input'); if(input){const wrap=node.querySelector('.options-field'); const toggle=()=>wrap.style.display=['select','checkbox'].includes(input.value)?'':'none'; input.addEventListener('change',toggle);toggle();} rows.appendChild(node); }
 document.getElementById('addBulkRow').addEventListener('click',addRow); addRow();
 const cat=document.getElementById('bulkCategory'), sub=document.getElementById('bulkSubcategory'), pt=document.getElementById('bulkProductType');
 async function load(url, select, placeholder){select.innerHTML='<option value="">Loading...</option>';select.disabled=true;try{const r=await fetch(url,{headers:{'X-Requested-With':'XMLHttpRequest'}});const j=await r.json();select.innerHTML='<option value="">'+placeholder+'</option>';(j.items||[]).forEach(x=>{const o=document.createElement('option');o.value=x.id;o.textContent=x.name;select.appendChild(o);});select.disabled=false;}catch(e){select.innerHTML='<option value="">Unable to load</option>';}}
 cat?.addEventListener('change',()=>{if(sub){sub.innerHTML='<option value="">Select subcategory</option>';sub.disabled=true;}if(pt){pt.innerHTML='<option value="">Select product type</option>';pt.disabled=true;}if(cat.value&&sub)load('<?= site_url('shipping-admin/catalog/subcategories-json') ?>?category_id='+encodeURIComponent(cat.value),sub,'Select subcategory');});
 sub?.addEventListener('change',()=>{if(pt){pt.innerHTML='<option value="">Select product type</option>';pt.disabled=true;}if(sub.value&&pt)load('<?= site_url('shipping-admin/catalog/product-types-json') ?>?subcategory_id='+encodeURIComponent(sub.value),pt,'Select product type');});
})();
</script>
<?= $this->endSection() ?>
