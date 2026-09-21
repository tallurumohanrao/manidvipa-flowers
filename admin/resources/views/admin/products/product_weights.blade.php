@extends('admin.layouts.app')
@push('styles')
<style>
.inventory-card{border-left:4px solid #1cc88a}.selling-option{border:1px solid #dce3ef;border-left:4px solid #4e73df;border-radius:8px;margin-bottom:18px}.selling-option .card-header{background:#f7f9fc}.selling-option label{color:#4b5563;font-size:13px;font-weight:700}.option-box{background:#fbfcff;border:1px solid #e7ebf2;border-radius:6px;padding:14px;height:100%}.option-preview{color:#b31b1b;font-size:15px;font-weight:700}.option-help{font-size:11px}.workflow-step{align-items:center;background:#eef3ff;border-radius:50%;color:#4e73df;display:inline-flex;font-weight:800;height:30px;justify-content:center;margin-right:8px;width:30px}
</style>
@endpush
@section('content')
<section class="content"><div class="container-fluid">
<div class="d-flex flex-wrap align-items-center justify-content-between"><div><h4 class="heading mb-1">Inventory &amp; selling options — {{ $product->title }}</h4><p class="text-muted mb-1">Inventory is the real stock pool. Selling options are the customer quantities and prices connected to that pool.</p><p class="text-muted mb-0"><strong>Default option</strong> is shown first on home, category cards, the product page, cart and order details.</p></div><div class="btn-group mt-2 mt-md-0"><a href="{{ route('admin.products.edit',['product'=>$product->id]) }}" class="btn btn-outline-primary">Edit product</a><a href="{{ route('admin.products.index') }}" class="btn btn-outline-secondary">All products</a></div></div><hr>
@if($errors->any())<div class="alert alert-danger"><strong>Please correct these details.</strong><ul class="mb-0 mt-2">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

<div class="card shadow mb-4 inventory-card"><div class="card-header"><h5 class="mb-0"><span class="workflow-step">1</span>Real inventory</h5></div><div class="card-body">
<p class="text-muted">Enter the total physical stock once. Example: Lotus = 2,500 Flowers; Marigold = 25,000 Grams. All connected selling options share this quantity.</p>
<form method="POST" action="{{ route('admin.products.inventorystore',['id'=>$product->id]) }}">@csrf
<div class="table-responsive"><table class="table table-bordered"><thead><tr><th>Base unit</th><th>Available stock</th><th>Track stock</th><th>Status</th><th></th></tr></thead><tbody id="inventory-rows">
@foreach($inventoryPools as $index=>$pool)
@include('admin.products.partials.inventory_pool',['index'=>$index,'pool'=>$pool])
@endforeach
</tbody></table></div>
<div class="d-flex justify-content-between"><button type="button" class="btn btn-outline-success" id="add-inventory"><i class="fa fa-plus-circle"></i> Add inventory pool</button><button type="submit" class="btn btn-success"><i class="fa fa-save"></i> Save inventory</button></div>
</form></div></div>

<div class="card shadow mb-4"><div class="card-header"><h5 class="mb-0"><span class="workflow-step">2</span>Customer selling options</h5></div><div class="card-body">
@if($inventoryPools->isEmpty())<div class="alert alert-warning">Create and save an inventory pool first. Selling options need real inventory to calculate availability correctly.</div>@endif
<form method="POST" action="{{ route('admin.products.weightsstore',['id'=>$product->id]) }}">@csrf
<div id="selling-options">@foreach($weights as $index=>$row)@include('admin.products.partials.selling_option',['index'=>$index,'row'=>$row])@endforeach</div>
<div class="d-flex flex-wrap justify-content-between"><button type="button" class="btn btn-outline-primary" id="add-selling-option" @disabled($inventoryPools->isEmpty())><i class="fa fa-plus-circle"></i> Add selling option</button><button type="submit" class="btn btn-primary" @disabled($inventoryPools->isEmpty())><i class="fa fa-save"></i> Save selling options</button></div>
</form></div></div>
</div></section>
<template id="inventory-template">@include('admin.products.partials.inventory_pool',['index'=>'__INDEX__','pool'=>null])</template>
<template id="selling-option-template">@include('admin.products.partials.selling_option',['index'=>'__INDEX__','row'=>null])</template>
@endsection
@push('script')
<script>
(function(){
const inventoryRows=document.getElementById('inventory-rows'),inventoryTemplate=document.getElementById('inventory-template'),addInventory=document.getElementById('add-inventory');let inventoryIndex={{ $inventoryPools->count() }};
addInventory.addEventListener('click',()=>{const box=document.createElement('tbody');box.innerHTML=inventoryTemplate.innerHTML.replaceAll('__INDEX__',String(inventoryIndex++));const row=box.firstElementChild;inventoryRows.appendChild(row);row.querySelector('[data-remove-inventory]').addEventListener('click',()=>row.remove())});
document.querySelectorAll('[data-remove-inventory]').forEach(button=>button.addEventListener('click',()=>button.closest('tr').remove()));

const list=document.getElementById('selling-options'),template=document.getElementById('selling-option-template'),add=document.getElementById('add-selling-option');let next={{ $weights->count() }};
const num=v=>{const n=Number(v||0);return Number.isFinite(n)?String(Number(n.toFixed(3))):'0'};
function selectedUnit(card){const select=card.querySelector('[data-unit]'),option=select.options[select.selectedIndex];return{code:option?.dataset.code||'',base:option?.dataset.base||'',decimal:option?.dataset.decimal==='1',singular:option?.dataset.singular||'Unit',plural:option?.dataset.plural||'Units'}}
function update(card){const q=card.querySelector('[data-quantity]').value,u=selectedUnit(card),label=card.querySelector('[data-label]').value.trim(),mode=card.querySelector('[data-mode]').value,custom=card.querySelector('[data-custom]').checked,inventory=card.querySelector('[data-inventory]');card.querySelector('[data-preview]').textContent=label||(q&&u.code?num(q)+' '+(Number(q)===1?u.singular:u.plural):'Complete quantity and unit');card.querySelector('[data-quantity]').step=u.decimal?'0.001':'1';card.querySelectorAll('[data-custom-fields] input[type=number]').forEach(input=>input.step=u.decimal?'0.001':'1');Array.from(inventory.options).forEach((option,index)=>{if(index===0)return;option.disabled=Boolean(u.base&&option.dataset.base!==u.base)});if(inventory.selectedOptions[0]?.disabled)inventory.value='';card.querySelector('[data-rates]').hidden=mode!=='automatic';card.querySelector('[data-totals]').querySelectorAll('input').forEach(i=>i.readOnly=mode==='automatic');card.querySelector('[data-custom-fields]').hidden=!custom;if(mode==='automatic'&&q){['sell','list','cost'].forEach(t=>{const rate=card.querySelector('[data-rate-'+t+']'),total=card.querySelector('[data-total-'+t+']');total.value=rate.value===''?'':(Number(q)*Number(rate.value)).toFixed(2)})}}
function init(card){card.querySelectorAll('input,select').forEach(f=>f.addEventListener((f.type==='text'||f.type==='number')?'input':'change',()=>update(card)));const remove=card.querySelector('[data-remove]');if(remove)remove.addEventListener('click',()=>card.remove());update(card)}
if(add)add.addEventListener('click',()=>{const box=document.createElement('div');box.innerHTML=template.innerHTML.replaceAll('__INDEX__',String(next++));const card=box.firstElementChild;list.appendChild(card);init(card);card.scrollIntoView({behavior:'smooth',block:'center'})});list.querySelectorAll('[data-option]').forEach(init);
})();
</script>
@endpush
