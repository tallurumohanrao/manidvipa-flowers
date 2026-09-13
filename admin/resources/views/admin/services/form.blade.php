<div class="row">
    <div class="col-md-4">
    <label class="col-form-label" for="title">Title</label>
    {{ html()->text('title')->class('form-control')->placeholder('Title') }}
    </div>

    <div class="col-md-2">
        <label class="col-form-label" for="category_id">Category</label>
        {!! html()->select('category_id',$categories)->id('type')->class('form-control')->placeholder('-- Category --') !!}
    </div>

    <div class="col-md-2">
        <label class="col-form-label" for="status">Service Type</label>
        {!! html()->select('type',serviceTypes())->id('type')->class('form-control')->placeholder('-- Service Type --') !!}
    </div>

    <div class="col-md-6">
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

<div class="col-md-8">
    <label class="col-form-label" for="icon">Description</label>
    {{ html()->textarea('description')->class('form-control')->placeholder('Description') }}
</div>

<div class="col-md-3">
    <label class="col-form-label" for="price">Price</label>
    {{ html()->text('price')->class('form-control')->placeholder('Price') }}
</div>

<div class="col-md-3">
    <label class="col-form-label" for="helpline_1">Helpline 1</label>
    {{ html()->text('helpline_1')->class('form-control')->placeholder('Helpline 1') }}
</div>

<div class="col-md-3">
    <label class="col-form-label" for="helpline_2">Helpline 2</label>
    {{ html()->text('helpline_2')->class('form-control')->placeholder('Helpline 2') }}
</div>

<div class="col-md-2">
    <label class="col-form-label" for="priority">Priority</label>
    {{ html()->text('priority')->class('form-control')->placeholder('Priority') }}
</div>

<div class="col-md-2">
    <label class="col-form-label" for="status">Status</label>
    {!! html()->select('status',array('1' => 'Enable', '0' => 'Disable'))->id('status')->class('form-control') !!}
</div>

</div>
