<div class="form-group row">
    <label class="col-sm-3 col-form-label" for="type">Type</label>
    <div class="col-sm-2">
    {!! html()->select('type',array(''=>'-- Type --','decorations' => 'Decorations', 'roses' => 'Roses','photography'=>'Photography'))->id('type')->class('form-control')->required() !!}
    </div>
</div>

<div class="form-group row">
    <label class="col-sm-3 col-form-label" for="name">Name</label>
    <div class="col-sm-9">
    {{ html()->text('name')->class('form-control')->placeholder('Name')->required() }}
    </div>
</div>

<div class="form-group row">
    <label class="col-sm-3 col-form-label" for="image">Image</label>
    <div class="col-sm-9">
    {!! html()->file('image') !!}
    {!! html()->hidden('old_image', @$row->image) !!}
    @if(@$row->image && File::exists('storage/'.$module.'/'. @$row->image))
    {{ html()->img(asset('storage/'.$module.'/'. @$row->image ))->attributes(['title' => @$row->image ,'width' => '180px']) }}
    @endif
    </div>
</div>

<div class="form-group row">
    <label class="col-sm-3 col-form-label" for="price">Price</label>
    <div class="col-sm-9">
    {{ html()->text('price')->class('form-control')->placeholder('Price')->required() }}
    </div>
</div>

<div class="form-group row">
    <label class="col-sm-3 col-form-label" for="priority">Priority</label>
    <div class="col-sm-9">
    {{ html()->text('priority')->class('form-control')->placeholder('Priority')->required() }}
    </div>
</div>

<div class="form-group row">
    <label class="col-sm-3 col-form-label" for="status">Status</label>
    <div class="col-sm-2">
    {!! html()->select('status',array('1' => 'Enable', '2' => 'Disable'))->id('status')->class('form-control') !!}
    </div>
</div>
