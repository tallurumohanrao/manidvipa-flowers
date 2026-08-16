<div class="form-group row">
    <label class="col-sm-3 col-form-label" for="name">Name</label>
    <div class="col-sm-9">
    {{ html()->text('name')->class('form-control')->placeholder('Name')->required() }}
    </div>
</div>

<div class="form-group row">
    <label class="col-sm-3 col-form-label" for="description">Description</label>
    <div class="col-sm-9">
    {{ html()->textarea('description')->class('editor form-control')->placeholder('Description')->required() }}
    </div>
</div>

<div class="form-group row">
    <label class="col-sm-3 col-form-label" for="status">Status</label>
    <div class="col-sm-2">
    {!! html()->select('status',array('1' => 'Enable', '2' => 'Disable'))->id('status')->class('form-control') !!}
    </div>
</div>
