<div class="row">
    <div class="col-md-6">
        <label class="col-form-label" for="title">Title</label>
        {{ html()->text('title')->class('form-control') }}
    </div>
    <div class="col-md-2">
        <label class="col-form-label" for="sku">SKU</label>
        {{ html()->text('sku')->class('form-control') }}
    </div>
    <div class="col-md-2">
        <label class="col-form-label" for="qty">Qty</label>
        {{ html()->text('qty')->class('form-control') }}
    </div>
    {{--<div class="col-md-2">
        <label class="col-form-label" for="sell_price">Sell Price</label>
        {{ html()->text('sell_price')->class('form-control') }}
    </div>
    <div class="col-md-2">
        <label class="col-form-label" for="list_price">List Price</label>
        {{ html()->text('list_price')->class('form-control') }}
    </div>
    <div class="col-md-2">
        <label class="col-form-label" for="cost_price">Cost Price</label>
        {{ html()->text('cost_price')->class('form-control') }}
    </div>--}}
    <div class="col-md-4">
        <label class="col-form-label" for="categories">Categories</label>
        {!! html()->multiselect('product_category[]',$categories,$selected)->id('product_category')->class('select2 form-control') !!}
    </div>
    <div class="col-md-2">
        <label class="col-form-label" for="priority">Priority</label>
        {{ html()->text('priority')->class('form-control') }}
    </div>
    <div class="col-md-2">
        <label class="col-form-label" for="status">Status</label>
        {!! html()->select('status',array('1' => 'Enable', '0' => 'Disable'))->id('status')->class('form-control') !!}
    </div>
    <div class="col-md-12">
        <label class="col-form-label" for="short_description">Short Description</label>
        {{ html()->textarea('short_description')->class('form-control editor') }}
    </div>
    <div class="col-md-12">
        <label class="col-form-label" for="description">Description</label>
        {{ html()->textarea('description')->class('form-control editor') }}
    </div>
</div>
@include('admin.partials.seo')
