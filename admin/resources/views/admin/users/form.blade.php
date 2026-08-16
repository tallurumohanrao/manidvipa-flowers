<div class="form-group row">
    <label class="col-sm-3 col-form-label" for="name">Name</label>
    <div class="col-sm-9">
        {{ html()->text('name')->class('form-control')->placeholder('Name') }}
    </div>
</div>

<div class="form-group row">
    <label class="col-sm-3 col-form-label" for="email">Email</label>
    <div class="col-sm-9">
    {{ html()->email('email')->class('form-control')->placeholder('Email') }}
    </div>
</div>

<div class="form-group row">
    <label class="col-sm-3 col-form-label" for="password">Password</label>
    <div class="col-sm-9">
    {{ html()->password('password')->class('form-control') }}
    </div>
</div>

<div class="form-group row">
    <label class="col-sm-3 col-form-label" for="confirmation_password">Confirm Password</label>
    <div class="col-sm-9">
        {{ html()->password('confirmation_password')->class('form-control') }}
    </div>
</div>
<div class="form-group row">
    <label class="col-sm-3 col-form-label" for="status">Status</label>
    <div class="col-sm-9">
    {!! html()->select('status',[''=>'Status','1' => 'Enable', '2' => 'Disable'])->class('form-control') !!}
    </div>
</div>
