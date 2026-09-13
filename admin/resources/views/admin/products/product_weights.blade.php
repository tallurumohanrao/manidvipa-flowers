@extends('admin.layouts.app')
@push('styles')
<style>
    .stock-quantity-cell {
        min-width: 250px;
    }

    .stock-quantity-editor .input-group {
        flex-wrap: nowrap;
    }

    .stock-unit-label {
        min-width: 98px;
        justify-content: center;
        background-color: #fff5f5;
        color: #b31b1b;
        font-weight: 700;
        white-space: nowrap;
    }

    .stock-preview {
        display: block;
        margin-top: 6px;
        color: #169247;
        font-size: 12px;
        font-weight: 700;
        line-height: 1.25;
    }

    .stock-preview.stock-preview-danger {
        color: #dc3545;
    }

    .stock-preview.stock-preview-muted {
        color: #6c757d;
        font-weight: 600;
    }

    .stock-help {
        display: block;
        margin-top: 3px;
        color: #6c757d;
        font-size: 11px;
        line-height: 1.25;
    }
</style>
@endpush
@section('content')
<section class="content">
	<div class="container-fluid">
		<div class="d-flex flex-wrap align-items-center justify-content-between">
            <div>
                <h4 class="heading mb-1">Weights &amp; stock &mdash; {{ $product->title }}</h4>
                <p class="text-muted mb-0">Choose the selling unit first, then enter the stock count. Example: 5 in a 1 KG row shows 5 KG available; 100 in an Each Bunch row shows 100 bunches available.</p>
            </div>
            <div class="btn-group mt-2 mt-md-0">
                <a href="{{ route('admin.products.edit', ['product' => $product->id]) }}" class="btn btn-outline-primary">Edit product</a>
                <a href="{{ route('admin.products.index') }}" class="btn btn-outline-secondary">All products</a>
            </div>
        </div>
		<hr>
		<div class="card shadow mb-4">
            <div class="card-body">
            	<div class="row">
            		<div class="col-sm-12">
            		<div class="table-responsive">
                    {{ html()->form('POST')->route('admin.'.$module.'.weightsstore',['id'=>$product->id])->class('form-horizontal')->id('form')->open() }}
            			<table class="table table-bordered table-hover">
                            <thead>
                                <tr role="row">
                                    {{-- <th>
                                        <div class="custom-control custom-checkbox">
                                            {!! html()->checkbox('selectAll')->id('selectAll')->class('custom-control-input') !!}
                                            <label class="custom-control-label" for="selectAll"></label>
                                        </div>
                                    </th> --}}
                                    <th scope="col">S.No</th>
                                    <th scope="col">Weight</th>
                                    <th scope="col">Sell Price</th>
                                    <th scope="col">List Price</th>
                                    <th scope="col">Cost Price</th>
                                    {{--<th scope="col">GST %</th>
                                    <th scope="col">Enable Vat</th>--}}
                                    <th scope="col">Stock quantity &amp; unit</th>
                                    <th scope="col">Track stock</th>
                                    <th scope="col">Status</th>
                                    {{-- <th scope="col">Created At</th> --}}
                                    <th><button type="button" class="btn btn-sm btn-outline-primary" onclick="addWeights()" aria-label="Add another weight"><i class="fa fa-plus-circle" aria-hidden="true"></i> Add</button></th>
                                </tr>
                            </thead>

                            <tbody id="tablecontents">
                            @foreach($weights as $row)
                                <tr id="row-{{ $row->id }}">
                                    {{-- <td>
                                        <div class="custom-control custom-checkbox sub_chk">
                                            {!! html()->checkbox('id[]', false, $row->id)->id($row->id)->class('custom-control-input') !!}
                                            <label class="custom-control-label" for="{{ $row->id }}"></label>
                                        </div>
                                    </td> --}}
                                    <td>
                                        {{ $loop->iteration }}
                                        {{ html()->hidden('Weight['.$loop->index.'][id]', $row->id) }}
                                    </td>
                                    <td>
                                        {!! html()->select('Weight['.$loop->index.'][name]',$selectboxweights,$row->name)->class('form-control stock-weight-select')->placeholder('-- Select --','')->required()->attributes(['data-stock-weight-select'=>'1']) !!}
                                    </td>
                                    <td>
                                        {{ html()->text('Weight['.$loop->index.'][sell_price]', $row->sell_price)->class('form-control')->required() }}
                                    </td>
                                    <td>
                                        {{ html()->text('Weight['.$loop->index.'][list_price]', $row->list_price)->class('form-control')->required() }}
                                    </td>
                                    <td>
                                        {{ html()->text('Weight['.$loop->index.'][cost_price]', $row->cost_price)->class('form-control') }}
                                    </td>
                                    <td class="stock-quantity-cell">
                                        <div class="stock-quantity-editor" data-stock-editor="1">
                                            <div class="input-group">
                                                {{ html()->number('Weight['.$loop->index.'][qty]', $row->qty)->class('form-control stock-qty-input')->attributes(['min'=>0, 'step'=>'any', 'inputmode'=>'decimal', 'data-stock-qty'=>'1', 'aria-label'=>'Stock quantity for row '.$loop->iteration]) }}
                                                <div class="input-group-append">
                                                    <span class="input-group-text stock-unit-label" data-stock-unit>Unit</span>
                                                </div>
                                            </div>
                                            <small class="stock-preview" data-stock-preview></small>
                                            <small class="stock-help">The unit comes from the selected Weight row.</small>
                                        </div>
                                        @if($row->stock == 1 && $row->qty <= 0)
                                            <span class="badge badge-danger mt-1">Out of stock</span>
                                        @elseif($row->stock == 1 && $row->qty <= 5)
                                            <span class="badge badge-warning mt-1">Low stock</span>
                                        @endif
                                    </td>
                                    {{--<td>{{ html()->text('Weight['.$loop->index.'][vat_price]', $row->vat_price )->class('form-control') }}</td>
                                    <td>
                                    {{ html()->checkbox('Weight['.$loop->index.'][vat_enable]', 1, $row->vat_enable == 1)->class('form-control') }}
                                    </td>--}}
                                    <td>
                                    {{ html()->checkbox('Weight['.$loop->index.'][stock]', $row->stock == 1, 1)->class('stock-track-input')->attributes(['data-stock-track'=>'1', 'aria-label'=>'Track stock for row '.$loop->iteration]) }}
                                    </td>
                                    <td>
                                        {!! html()->select('Weight['.$loop->index.'][status]',[''=>'-- Select --','1'=>'Enable','0'=>'Disable'],$row->status)->class('form-control')->required() !!}
                                        {{-- <label class="switch">
                                        {{ html()->checkbox('status', $row->status, null)->class('status')->id('status_'.$row->id)->attributes(['data-id'=>$row->id,'data-url'=>route('admin.'.$module.'.weight.update.status',['id'=>$row->id])]) }}
                                        <span class="slider round"></span>
                                        </label> --}}
                                    </td>
                                    {{-- <td>{{$row->created_at}}</td> --}}
                                    <td>
                                        @can($module.'_delete')
                                            <a href="javascript:;" class="delete btn btn-danger" title="Delete this weight" aria-label="Delete this weight" data-id="{{ $row->id }}" data-url="{{ route('admin.'.$module.'.weight.destroy',['id'=>$row->id]) }}"><i class="fa fa-trash" aria-hidden="true"></i></a>
                                        @endcan
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                        <button type="submit" class="btn btn-primary">Save weights &amp; stock</button>
                    {{ html()->form()->close() }}
                    </div>
                    </div>
                </div>
            </div>
        </div>
	</div>
</section>
<script>
    var row_no ={{ $weights->count() + 1 }};
    var stockWeightOptionsHtml = @json($shtml);
</script>
@endsection
@push('script')
<script>
function normalizeStockWeightName(name)
{
    return String(name || '')
        .toLowerCase()
        .replace(/[._-]+/g, ' ')
        .replace(/\s+/g, ' ')
        .trim();
}

function formatStockNumber(value)
{
    var number = Number(value);

    if (!Number.isFinite(number)) {
        number = 0;
    }

    return number.toFixed(2).replace(/\.?0+$/, '') || '0';
}

function stockUnitForWeight(weightName, quantity)
{
    var name = normalizeStockWeightName(weightName);
    var number = Number(quantity);
    var isSingle = Number.isFinite(number) && Math.abs(number - 1) < 0.00001;

    if (!name) {
        return 'units';
    }

    if (name.includes('bunch')) {
        return isSingle ? 'bunch' : 'bunches';
    }

    if (name.includes('stem')) {
        return isSingle ? 'stem' : 'stems';
    }

    if (name.includes('piece') || name.includes('pcs') || name.includes('each one') || name === 'each') {
        return isSingle ? 'piece' : 'pieces';
    }

    if (name.includes('packet')) {
        return isSingle ? 'packet' : 'packets';
    }

    if (/^1\s*(kg|kgs|kilogram|kilograms)$/.test(name)) {
        return 'KG';
    }

    if (/^1\s*(ltr|liter|litre|liters|litres|l)$/.test(name)) {
        return 'LTR';
    }

    if (/\b(kg|kgs|kilogram|kilograms|gram|grams|grm|gm|g|ml|ltr|liter|litre|liters|litres|l)\b/.test(name)) {
        return isSingle ? 'pack' : 'packs';
    }

    return isSingle ? 'unit' : 'units';
}

function stockPreviewForWeight(weightName, quantity, isTracked)
{
    var qtyText = formatStockNumber(quantity);
    var unit = stockUnitForWeight(weightName, quantity);
    var name = String(weightName || '').trim();

    if (!name) {
        return 'Select a weight/unit first.';
    }

    if (!isTracked) {
        return 'Tracking off - customers can order this option without a stock limit.';
    }

    if (unit === 'pack' || unit === 'packs') {
        return qtyText + ' ' + unit + ' of ' + name + ' available';
    }

    return qtyText + ' ' + unit + ' available';
}

function refreshStockEditor(row)
{
    if (!row) {
        return;
    }

    var weightSelect = row.querySelector('[data-stock-weight-select]');
    var quantityInput = row.querySelector('[data-stock-qty]');
    var trackInput = row.querySelector('[data-stock-track]');
    var unitLabel = row.querySelector('[data-stock-unit]');
    var preview = row.querySelector('[data-stock-preview]');

    if (!weightSelect || !quantityInput || !unitLabel || !preview) {
        return;
    }

    var weightName = weightSelect.value;
    var quantity = quantityInput.value;
    var isTracked = !trackInput || trackInput.checked;
    var unit = stockUnitForWeight(weightName, quantity);
    var numericQuantity = Number(quantity);

    unitLabel.textContent = unit;
    preview.textContent = stockPreviewForWeight(weightName, quantity, isTracked);
    preview.classList.toggle('stock-preview-muted', !weightName || !isTracked);
    preview.classList.toggle('stock-preview-danger', Boolean(weightName) && isTracked && Number.isFinite(numericQuantity) && numericQuantity <= 0);
}

function refreshStockEditors()
{
    document.querySelectorAll('#tablecontents tr').forEach(function(row) {
        refreshStockEditor(row);
    });
}

function addWeights()
{
    var currentRowNo = row_no;
    var newWeightRowId = 'row-new-' + currentRowNo;
    var row = '<tr id="' + newWeightRowId + '">';
    row += '<td>' + currentRowNo + '<input name="Weight[' + currentRowNo + '][id]" type="hidden" value=""></td>';
    row += '<td><select class="select2 form-control stock-weight-select" data-stock-weight-select="1" autocomplete="off" name="Weight[' + currentRowNo + '][name]" required>' + stockWeightOptionsHtml + '</select></td>';
    row += '<td><input name="Weight[' + currentRowNo + '][sell_price]" class="form-control" type="text" required></td>';
    row += '<td><input name="Weight[' + currentRowNo + '][list_price]" class="form-control" type="text" required></td>';
    row += '<td><input name="Weight[' + currentRowNo + '][cost_price]" class="form-control" type="text"></td>';
    row += '<td class="stock-quantity-cell"><div class="stock-quantity-editor" data-stock-editor="1"><div class="input-group"><input class="form-control stock-qty-input" autocomplete="off" data-stock-qty="1" name="Weight[' + currentRowNo + '][qty]" type="number" min="0" step="any" inputmode="decimal" aria-label="Stock quantity for row ' + currentRowNo + '"><div class="input-group-append"><span class="input-group-text stock-unit-label" data-stock-unit>Unit</span></div></div><small class="stock-preview" data-stock-preview></small><small class="stock-help">The unit comes from the selected Weight row.</small></div></td>';
    row += '<td><input autocomplete="off" name="Weight[' + currentRowNo + '][stock]" class="stock-track-input" data-stock-track="1" type="checkbox" value="1" checked aria-label="Track stock for row ' + currentRowNo + '"></td>';
    row += '<td><select class="form-control" autocomplete="off" name="Weight[' + currentRowNo + '][status]"><option value="1">Enable</option><option value="0">Disable</option></select></td>';
    row += '<td><button type="button" class="btn btn-danger remove-new-weight" aria-label="Remove this new weight"><i class="fa fa-trash" aria-hidden="true"></i></button></td>';
    row += '</tr>';
    $('#tablecontents').append(row);
    refreshStockEditor(document.getElementById(newWeightRowId));
    row_no++;
}

document.addEventListener('input', function(event) {
    if (event.target.matches('[data-stock-qty]')) {
        refreshStockEditor(event.target.closest('tr'));
    }
});

document.addEventListener('change', function(event) {
    if (event.target.matches('[data-stock-weight-select], [data-stock-track]')) {
        refreshStockEditor(event.target.closest('tr'));
    }
});

document.addEventListener('click', function(event) {
    var removeButton = event.target.closest('.remove-new-weight');

    if (removeButton) {
        removeButton.closest('tr').remove();
    }
});

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', refreshStockEditors);
} else {
    refreshStockEditors();
}
</script>
@endpush
