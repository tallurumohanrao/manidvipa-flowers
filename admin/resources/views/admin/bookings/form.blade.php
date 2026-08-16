
<input type="hidden" name="amount" id="amount">
<input type="hidden" name="total_amount" id="total_amount">
<input type="hidden" name="decoration_amount" id="decoration_amount">
<input type="hidden" name="cake_amount" id="cake_amount">
<input type="hidden" name="addons_amount" id="addons_amount">
<input type="hidden" name="price1_max_people" id="price1_max_people">
<input type="hidden" name="price1" id="price1">
<input type="hidden" name="price2" id="price2">

<div class="form-group row">
    <label class="col-sm-5 col-form-label" for="city">City</label>
    <div class="col-sm-5">
    {!! html()->select('city',array('hyderabad' => 'Hyderabad', 'vizag' => 'Vizag'))->placeholder('-- City --')->class('form-control') !!}
    </div>
</div>

<div class="form-group row">
    <label class="col-sm-5 col-form-label" for="booking_date">Booking Date</label>
    <div class="col-sm-5">
    {{ html()->text('booking_date')->class('form-control datepicker')->placeholder('Booking Date')->attributes(['autocomplete'=>'off'])->required() }}
    </div>
</div>

<div class="form-group row">
    <label class="col-sm-5 col-form-label" for="theater_id">Theater</label>
    <div class="col-sm-5">
    {!! html()->select('theater_id',[])->placeholder('-- Select Theater --')->class('form-control') !!}
    </div>
</div>

<div class="form-group row">
    <label class="col-sm-5 col-form-label" for="slot_id">Slot</label>
    <div class="col-sm-5">
    {!! html()->select('slot_id',[])->placeholder('-- Select Slot --')->class('form-control') !!}
    </div>
</div>

<div class="form-group row">
    <label class="col-sm-5 col-form-label" for="no_of_persons">Select No. of Persons</label>
    <div class="col-sm-5">
    {!! html()->select('no_of_persons',[])->placeholder('-- Select No. of Persons --')->class('form-control') !!}
    </div>
</div>

<div class="form-group row">
    <label class="col-sm-5 col-form-label" for="decorations">Decorations</label>
    <div class="col-sm-5">
    {{-- {!! html()->select('decorations',$decorations)->placeholder('-- Decorations --')->class('form-control') !!} --}}
    <select class="form-control" name="decorations" id="decorations">
        <option value="" selected="selected">-- Decorations --</option>
        @foreach($decorations as $decoration)
        <option value="{{ $decoration->id }}||{{ $decoration->price }}">{{ $decoration->name .' ('.$decoration->price.')' }}</option>
        @endforeach
    </select>
    </div>
</div>

<div class="form-group row">
    <label class="col-sm-5 col-form-label" for="cakes">Cakes</label>
    <div class="col-sm-5">
    <select class="form-control" name="cakes" id="cakes">
        <option value="" selected="selected">-- Cakes --</option>
        @foreach($cakes as $cake)
        <option value="{{ $cake->id .'||'. $cake->price }}">{{ $cake->name .' ('.$cake->price.')' }}</option>
        @endforeach
    </select>
    </div>
</div>


<div class="form-group row">
    <label class="col-sm-5 col-form-label" for="addons">Addons</label>
    <div class="col-sm-5">
    <select class="form-control" name="addons[]" id="addons" multiple>
        @foreach($addonsgroup as $addonsgroup_key => $addonsgroup_value)
        <optgroup label="{{ ucwords($addonsgroup_key) }}">
            @foreach($addonsgroup_value as $addon_key => $addon)
            <option value="{{ $addon['id'].'||'. $addon['price']  }}">{{ $addon['name'] . '('.$addon['price'].')' }}</option>
            @endforeach
        </optgroup>
        @endforeach
    </select>
    </div>
</div>


<div class="form-group row">
    <label class="col-sm-5 col-form-label" for="name">Name</label>
    <div class="col-sm-5">
    {!! html()->text('name')->class('form-control') !!}
    </div>
</div>

<div class="form-group row">
    <label class="col-sm-5 col-form-label" for="email">Email</label>
    <div class="col-sm-5">
    {!! html()->text('email')->class('form-control') !!}
    </div>
</div>

<div class="form-group row">
    <label class="col-sm-5 col-form-label" for="whatsapp_number">Whatsapp Number</label>
    <div class="col-sm-5">
    {!! html()->text('whatsapp_number')->class('form-control') !!}
    </div>
</div>

<div class="form-group row">
    <label class="col-sm-5 col-form-label" for="whatsapp_number">Opt-in-Food</label>
    <div class="col-sm-5">
        {{ html()->checkbox('food', false, 1) }}
    </div>
</div>

<div class="form-group row">
    <label class="col-sm-5 col-form-label" for="booking_status_id">Booking Status</label>
    <div class="col-sm-5">
    {!! html()->select('booking_status_id',bookingstatuses())->placeholder('-- Booking Status --')->class('form-control') !!}
    </div>
</div>

<div class="form-group row">
    <label class="col-sm-5 col-form-label" for="payment_status">Payment Status</label>
    <div class="col-sm-5">
    {!! html()->select('payment_status',paymentstatuses())->placeholder('-- Payment Status --')->class('form-control') !!}
    </div>
</div>

<div class="form-group row">
    <label class="col-sm-5 col-form-label" for="amount_paid">Amount Paid</label>
    <div class="col-sm-5">
    {!! html()->text('amount_paid')->class('form-control') !!}
    </div>
</div>

<div class="form-group row">
    <label class="col-sm-5 col-form-label" for="payment_method">Payment Method</label>
    <div class="col-sm-5">
        {!! html()->select('payment_method',paymentmethods())->placeholder('-- Payment Method --')->class('form-control') !!}
    </div>
</div>

<div class="form-group row">
    <label class="col-sm-5 col-form-label" for="transaction_id">Transaction Id</label>
    <div class="col-sm-5">
    {!! html()->text('transaction_id')->class('form-control') !!}
    </div>
</div>
