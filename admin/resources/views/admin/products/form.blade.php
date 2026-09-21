<div class="row">
    <div class="col-md-6">
        <label class="col-form-label" for="title">Title</label>
        {{ html()->text('title')->class('form-control') }}
    </div>
    <div class="col-md-2">
        <label class="col-form-label" for="sku">SKU</label>
        {{ html()->text('sku')->class('form-control')->placeholder('MF-RED-ROSES') }}
        <small class="form-text text-muted">Unique code. Use uppercase letters, numbers and hyphens. Leave blank to generate one.</small>
    </div>
    <div class="col-md-2">
        <label class="col-form-label" for="qty">Display Quantity</label>
        {{ html()->text('qty')->class('form-control')->placeholder('5 KG, 100 bunches, 100') }}
        <small class="form-text text-muted">Shown in the admin product list. Order stock is managed under Weights &amp; stock.</small>
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
    <div class="col-md-4">
        <label class="col-form-label" for="price_visibility">Customer Price Display</label>
        {!! html()->select('price_visibility', $priceVisibilityModes)->id('price_visibility')->value(old('price_visibility', data_get($row, 'price_visibility', 'inherit') ?: 'inherit'))->class('form-control')->required() !!}
        <small class="form-text text-muted">Cost price always remains private. Enquiry Only and Coming Soon also prevent cart and checkout.</small>
    </div>
    <div class="col-md-4">
        <label class="col-form-label" for="price_visible_from">Automatically Show Everywhere From</label>
        <input type="datetime-local" name="price_visible_from" id="price_visible_from" class="form-control" value="{{ old('price_visible_from', data_get($row, 'price_visible_from') ? \Carbon\Carbon::parse(data_get($row, 'price_visible_from'))->format('Y-m-d\TH:i') : '') }}">
        <small class="form-text text-muted">Optional. At this time the price becomes public and ordering is enabled.</small>
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
