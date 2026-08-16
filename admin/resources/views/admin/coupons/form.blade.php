<div class="row">
    <div class="col-md-3">
        <label class="col-form-label" for="title">Title</label>
        {{ html()->text('title')->class('form-control')->required() }}
    </div>
    <div class="col-md-2">
        <label class="col-form-label" for="coupon_code">Coupon</label>
        {{ html()->text('coupon_code')->class('form-control')->required() }}
    </div>
    <div class="col-md-2">
        <label class="col-form-label" for="usage_limit">Usage Limit</label>
        {{ html()->text('usage_limit')->class('form-control') }}
    </div>
    <div class="col-md-2">
        <label class="col-form-label" for="usage_limit_per_user">Usage Limit Per User</label>
        {{ html()->text('usage_limit_per_user')->class('form-control') }}
    </div>
    <div class="col-md-3">
        <label class="col-form-label" for="minimum_purchage_amount">Minimum Purchage Amount</label>
        {{ html()->text('minimum_purchage_amount')->class('form-control') }}
    </div>
    <div class="col-md-2">
        <label class="col-form-label" for="is_percentage_discount">Is Percentage Discount</label>
        {{ html()->checkbox('is_percentage_discount', null,1) }}
    </div>
    <div class="col-md-2">
        <label class="col-form-label" for="discount">Discount</label>
        {{ html()->text('discount')->class('form-control')->required() }}
    </div>
    <div class="col-md-2">
        <label class="col-form-label" for="start_date">Start Date</label>
        {{ html()->date('start_date')->class('form-control') }}
    </div>
    <div class="col-md-2">
        <label class="col-form-label" for="end_date">End Date</label>
        {{ html()->date('end_date')->class('form-control') }}
    </div>
    <div class="col-md-2">
        <label class="col-form-label" for="status">Status</label>
        {!! html()->select('status',array('1' => 'Enable', '0' => 'Disable'))->id('status')->class('form-control') !!}
    </div>
</div>
