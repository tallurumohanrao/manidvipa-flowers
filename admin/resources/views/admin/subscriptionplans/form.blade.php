<div class="row">
    <div class="col-md-6">
        <label class="col-form-label" for="title">Plan Title</label>
        {{ html()->text('title')->class('form-control')->placeholder('Corporate Office Flower Subscription') }}
    </div>

    <div class="col-md-3">
        <label class="col-form-label" for="business_type">Business Type</label>
        {!! html()->select('business_type', $businessTypes)->id('business_type')->class('form-control')->placeholder('-- Business Type --') !!}
    </div>

    <div class="col-md-3">
        <label class="col-form-label" for="subscription_type">Package Type</label>
        {!! html()->select('subscription_type', $subscriptionTypes)->id('subscription_type')->value(old('subscription_type', data_get($row, 'subscription_type', 'Premium Arrangements')))->class('form-control')->placeholder('-- Package Type --') !!}
        <small class="form-text text-muted">Use Premium Arrangements for offices, hospitals and hotels. Use Loose Flowers for kg-based puja supply.</small>
    </div>

    <div class="col-md-3">
        <label class="col-form-label" for="billing_cycle">Billing Cycle</label>
        {!! html()->select('billing_cycle', $billingCycles)->id('billing_cycle')->class('form-control')->placeholder('-- Billing Cycle --') !!}
    </div>

    <div class="col-md-3">
        <label class="col-form-label" for="flower_grade">Flower Grade</label>
        {!! html()->select('flower_grade', $flowerGrades)->id('flower_grade')->class('form-control')->placeholder('-- Flower Grade --') !!}
    </div>

    <div class="col-md-3">
        <label class="col-form-label" for="starting_price">Starting Price</label>
        {{ html()->number('starting_price')->class('form-control')->attributes(['step' => '0.01'])->placeholder('2499') }}
    </div>

    <div class="col-md-2">
        <label class="col-form-label" for="price_suffix">Price Suffix</label>
        {{ html()->text('price_suffix')->class('form-control')->placeholder('/ month') }}
    </div>

    <div class="col-md-3">
        <label class="col-form-label" for="delivery_frequency">Delivery Frequency</label>
        {{ html()->text('delivery_frequency')->class('form-control')->placeholder('Daily or 3 days per week') }}
    </div>

    <div class="col-md-3">
        <label class="col-form-label" for="refresh_frequency">Refresh Frequency</label>
        {{ html()->text('refresh_frequency')->class('form-control')->placeholder('2 refreshes per week') }}
    </div>

    <div class="col-md-3">
        <label class="col-form-label" for="cta_label">CTA Label</label>
        {{ html()->text('cta_label')->class('form-control')->placeholder('Request Corporate Plan') }}
    </div>

    <div class="col-md-12">
        <label class="col-form-label" for="short_description">Short Description</label>
        {{ html()->text('short_description')->class('form-control')->placeholder('Fresh reception, desk and meeting-room flowers for offices.') }}
    </div>

    <div class="col-md-4">
        <label class="col-form-label" for="included_quantity_text">Quantity / Scope Clarity</label>
        {{ html()->text('included_quantity_text')->class('form-control')->placeholder('Not sold by kg; includes finished arrangement service') }}
        <small class="form-text text-muted">This answers: is the plan kg-based or arrangement-based?</small>
    </div>

    <div class="col-md-4">
        <label class="col-form-label" for="included_arrangement_count">Included Arrangements</label>
        {{ html()->text('included_arrangement_count')->class('form-control')->placeholder('1 reception arrangement + 2 desk arrangements') }}
    </div>

    <div class="col-md-4">
        <label class="col-form-label" for="arrangement_size">Arrangement Size</label>
        {{ html()->text('arrangement_size')->class('form-control')->placeholder('Medium reception + small desk arrangements') }}
    </div>

    <div class="col-md-6">
        <label class="col-form-label" for="flower_examples">Flower Examples</label>
        {{ html()->textarea('flower_examples')->class('form-control')->rows(6)->placeholder("Oriental lilies\nOrchids\nTulips\nPremium roses\nAnthuriums") }}
        <small class="form-text text-muted">Add one flower type per line. For corporate packages, list premium arrangement flowers.</small>
    </div>

    <div class="col-md-4">
        <label class="col-form-label" for="extra_quantity_note">Extra Quantity / Pricing Note</label>
        {{ html()->textarea('extra_quantity_note')->class('form-control')->rows(6)->placeholder('Extra desk arrangements or imported flowers are quoted separately.') }}
    </div>

    <div class="col-md-2">
        <label class="col-form-label" for="minimum_commitment">Minimum Commitment</label>
        {{ html()->text('minimum_commitment')->class('form-control')->placeholder('1 month') }}
    </div>

    <div class="col-md-6">
        <label class="col-form-label" for="description">Detailed Description</label>
        {{ html()->textarea('description')->class('form-control')->rows(5)->placeholder('Explain this subscription plan in detail.') }}
    </div>

    <div class="col-md-6">
        <label class="col-form-label" for="image">Plan Image</label>
        <div class="row">
            <div class="col-md-8">
                {!! html()->file('image') !!}
                {!! html()->hidden('old_image', @$row->image) !!}
                <small class="form-text text-muted">Recommended: 800x600 JPG/WebP. Leave empty to keep current image.</small>
            </div>
            <div class="col-md-4">
                @if(@$row->image && File::exists('storage/'.$module.'/'. @$row->image))
                    {{ html()->img(asset('storage/'.$module.'/'. @$row->image ))->attributes(['title' => @$row->image ,'width' => '100%']) }}
                @endif
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <label class="col-form-label" for="included_items">Included Items</label>
        {{ html()->textarea('included_items')->class('form-control')->rows(6)->placeholder("Reception flower bowl\nDesk flowers\nFestival add-ons") }}
        <small class="form-text text-muted">Add one item per line.</small>
    </div>

    <div class="col-md-4">
        <label class="col-form-label" for="features">Business Features</label>
        {{ html()->textarea('features')->class('form-control')->rows(6)->placeholder("Dedicated WhatsApp support\nMonthly billing support\nCustom delivery timing") }}
        <small class="form-text text-muted">Add one feature per line.</small>
    </div>

    <div class="col-md-4">
        <label class="col-form-label" for="ideal_for">Ideal For</label>
        {{ html()->textarea('ideal_for')->class('form-control')->rows(6)->placeholder("Corporate offices\nHospitals\nHotels") }}
        <small class="form-text text-muted">Add one use case per line.</small>
    </div>

    <div class="col-md-2">
        <label class="col-form-label" for="sort_order">Sort Order</label>
        {{ html()->number('sort_order')->class('form-control')->placeholder('10') }}
    </div>

    <div class="col-md-2">
        <label class="col-form-label" for="is_featured">Show on Home</label>
        {!! html()->select('is_featured', ['1' => 'Yes', '0' => 'No'])->id('is_featured')->class('form-control') !!}
    </div>

    <div class="col-md-2">
        <label class="col-form-label" for="status">Status</label>
        {!! html()->select('status', ['1' => 'Enable', '0' => 'Disable'])->id('status')->class('form-control') !!}
    </div>
</div>
