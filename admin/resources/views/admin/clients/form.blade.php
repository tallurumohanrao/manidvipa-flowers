<div class="form-group row">
    <label class="col-sm-3 col-form-label" for="image">Image</label>
    <div class="col-sm-9">
    {!! html()->file('image') !!}
    {!! html()->hidden('old_image', @$row->image) !!}
    @if(@$row->image && File::exists('storage/clients/'. @$row->image))
    {{ html()->img(asset('storage/'.$module.'/'. @$row->image ))->attributes(['title' => @$row->image ,'width' => '180px']) }}
    @endif
    </div>
</div>
<div class="form-group row">
    <label class="col-sm-3 col-form-label" for="url">URL</label>
    <div class="col-sm-9">
    {{ html()->text('url')->class('form-control') }}
    </div>
</div>
<div class="form-group row">
    <label class="col-sm-3 col-form-label" for="status">Status</label>
    <div class="col-sm-2">
        {!! html()->select('status',array('1' => 'Enable', '2' => 'Disable'))->id('status')->class('form-control') !!}
    </div>
</div>
