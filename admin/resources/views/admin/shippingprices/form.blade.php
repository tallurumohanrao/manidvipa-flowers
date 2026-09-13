<div class="row">
    <div class="col-md-4">
        <label class="col-form-label" for="title">Title</label>
        {{ html()->text('title')->class('form-control') }}
        <small class="text-muted">Example: Upto 5 KM, 5 to 10 KM, Free Shipping.</small>
    </div>

    <div class="col-md-2">
        <label class="col-form-label" for="from_km">From KM</label>
        {{ html()->text('from_km')->class('form-control') }}
        <small class="text-muted">Leave empty for free shipping by order amount.</small>
    </div>

    <div class="col-md-2">
        <label class="col-form-label" for="to_km">To KM</label>
        {{ html()->text('to_km')->class('form-control') }}
        <small class="text-muted">Required for paid delivery charges.</small>
    </div>

    <div class="col-md-2">
        <label class="col-form-label" for="min_order_amount">Minimum Order Amount</label>
        {{ html()->text('min_order_amount')->class('form-control') }}
        <small class="text-muted">Use 1200 for free shipping above ₹1200.</small>
    </div>

    <div class="col-md-2">
        <label class="col-form-label" for="max_order_amount">Maximum Order Amount</label>
        {{ html()->text('max_order_amount')->class('form-control') }}
    </div>

    <div class="col-md-2">
        <label class="col-form-label" for="shipping_amount">Shipping Amount</label>
        {{ html()->text('shipping_amount')->class('form-control') }}
        <small class="text-muted">Use 0 for free shipping.</small>
    </div>

    <div class="col-md-2">
        <label class="col-form-label" for="status">Status</label>
        {!! html()->select('status',array('1' => 'Enable', '0' => 'Disable'))->id('status')->class('form-control') !!}
    </div>

</div>
