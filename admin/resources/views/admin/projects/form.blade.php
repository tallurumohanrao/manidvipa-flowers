{{--<div class="form-group row">
    <label class="col-sm-3 col-form-label" for="image">Image</label>
    <div class="col-sm-9">
    {!! html()->file('image') !!}
    {!! html()->hidden('old_image', @$row->image) !!}
    @if(@$row->image && File::exists('storage/clients/'. @$row->image))
    {{ html()->img(asset('storage/'.$module.'/'. @$row->image ))->attributes(['title' => @$row->image ,'width' => '180px']) }}
    @endif
    </div>
</div>--}}
<div class="form-group row">
    <label class="col-sm-3 col-form-label" for="title">Title</label>
    <div class="col-sm-9">
    {{ html()->text('title')->class('form-control') }}
    </div>
</div>
<div class="form-group row">
    <label class="col-sm-3 col-form-label" for="description">Description</label>
    <div class="col-sm-9">
    {{ html()->textarea('description')->class('form-control editor') }}
    </div>
</div>
<div class="form-group row">
    <label class="col-sm-3 col-form-label" for="architects">Architects</label>
    <div class="col-sm-9">
    {{ html()->text('architects')->class('form-control') }}
    </div>
</div>
<div class="form-group row">
    <label class="col-sm-3 col-form-label" for="location">Location</label>
    <div class="col-sm-9">
    {{ html()->text('location')->class('form-control') }}
    </div>
</div>
<div class="form-group row">
    <label class="col-sm-3 col-form-label" for="category">Category</label>
    <div class="col-sm-9">
    {{ html()->text('category')->class('form-control') }}
    </div>
</div>
<div class="form-group row">
    <label class="col-sm-3 col-form-label" for="area">Area</label>
    <div class="col-sm-9">
    {{ html()->text('area')->class('form-control') }}
    </div>
</div>
<div class="form-group row">
    <label class="col-sm-3 col-form-label" for="project_year">Project Year</label>
    <div class="col-sm-9">
    {{ html()->text('project_year')->class('form-control') }}
    </div>
</div>
<div class="form-group row">
    <label class="col-sm-3 col-form-label" for="manufactures">Manufactures</label>
    <div class="col-sm-9">
    {{ html()->text('manufactures')->class('form-control') }}
    </div>
</div>
<div class="form-group row">
    <label class="col-sm-3 col-form-label" for="type">Type</label>
    <div class="col-sm-9">
    {{ html()->text('type')->class('form-control') }}
    </div>
</div>
<div class="form-group row">
    <label class="col-sm-3 col-form-label" for="status">Status</label>
    <div class="col-sm-2">
        {!! html()->select('status',array('1' => 'Enable', '0' => 'Disable'))->id('status')->class('form-control') !!}
    </div>
</div>
