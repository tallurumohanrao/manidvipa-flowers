<div class="row">
    <div class="col-md-2">
        <label class="col-form-label" for="name">Name</label>
        {{ html()->text('name')->class('form-control')->required() }}
    </div>
    <div class="col-md-2">
        <label class="col-form-label" for="status">Status</label>
        {!! html()->select('status',array('1' => 'Enable', '0' => 'Disable'))->id('status')->class('form-control') !!}
    </div>
</div>
