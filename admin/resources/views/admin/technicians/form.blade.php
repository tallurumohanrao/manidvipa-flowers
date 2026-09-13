<div class="row">
    <div class="col-md-3">
    <label class="col-form-label" for="name">Name</label>
    {{ html()->text('name')->class('form-control') }}
    </div>

    <div class="col-md-2">
        <label class="col-form-label" for="category_id">Services</label>
        {!! html()->select('services',$categories)->id('type')->class('form-control')->placeholder('-- Select --') !!}
    </div>

    <div class="col-md-2">
        <label class="col-form-label" for="experiance">Experiance</label>
        {{ html()->text('experiance')->class('form-control') }}
    </div>

    <div class="col-md-2">
        <label class="col-form-label" for="mobile">Mobile</label>
        {{ html()->text('mobile')->class('form-control')->required() }}
    </div>

    <div class="col-md-3">
        <label class="col-form-label" for="email">Email</label>
        {{ html()->text('email')->class('form-control') }}
    </div>

    <div class="col-md-4">
        <label class="col-form-label" for="address">Address</label>
        {{ html()->textarea('address')->class('form-control')->required() }}
    </div>
    {{-- <div class="col-md-2">
        <label class="col-form-label" for="status">Service Type</label>
        {!! html()->select('type',serviceTypes())->id('type')->class('form-control')->placeholder('-- Service Type --') !!}
    </div> --}}

    <div class="col-md-7">
        <label class="col-form-label" for="image">Image</label>
        <div class="row">
            <div class="col-md-9">
            {!! html()->file('image') !!}
            {!! html()->hidden('old_image', @$row->image) !!}
            </div>
            <div class="col-md-3">
                @if(@$row->image && File::exists('storage/'.$module.'/'. @$row->image))
                {{ html()->img(asset('storage/'.$module.'/'. @$row->image ))->attributes(['title' => @$row->image ,'width' => '100%']) }}
                @endif
            </div>
        </div>
    </div>
    {{-- <div class="col-md-6">
        <label class="col-form-label" for="icon">Icon</label>
        {{ html()->text('icon')->class('form-control')->placeholder('Icon') }}
    </div> --}}

    <div class="col-md-2">
        <label class="col-form-label" for="priority">Priority</label>
        {{ html()->text('priority')->class('form-control') }}
    </div>

    <div class="col-md-2">
        <label class="col-form-label" for="status">Status</label>
        {!! html()->select('status',array('1' => 'Enable', '0' => 'Disable'))->id('status')->class('form-control') !!}
    </div>
</div>
