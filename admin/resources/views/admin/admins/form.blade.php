<div class="form-group row">
    <label class="col-sm-3 col-form-label" for="name">Name</label>
    <div class="col-sm-9">
        {{ html()->text('name')->class('form-control')->placeholder('Name')->required() }}
    </div>
</div>

<div class="form-group row">
    <label class="col-sm-3 col-form-label" for="email">Email</label>
    <div class="col-sm-9">
    {{ html()->email('email')->class('form-control')->placeholder('Email')->required() }}
    </div>
</div>

<div class="form-group row">
    <label class="col-sm-3 col-form-label" for="image">Image</label>
    <div class="col-sm-8">
        {!! html()->file('image') !!}
        {!! html()->hidden('old_image', @$admin->image) !!}
    </div>
    <div class="col-sm-1">
    @if(@$admin->image && File::exists('storage/admins/'. @$admin->image ))
    {{ html()->img(asset('storage/'.$module.'/'. @$admin->image ))->attributes(['title' => @$admin->image ,'class' => 'img-radius w-100']) }}
    @endif
    </div>
</div>
<div class="form-group row">
    <label class="col-sm-3 col-form-label" for="password">Password</label>
    <div class="col-sm-9">
        {{ html()->password('password')->class('form-control') }}
    </div>
</div>

<div class="form-group row">
    <label class="col-sm-3 col-form-label" for="password_confirmation">Confirm Password</label>
    <div class="col-sm-9">
        {{ html()->password('password_confirmation')->class('form-control') }}
    </div>
</div>

<div class="form-group row">
    <label class="col-sm-3 col-form-label" for="roles">Roles</label>
    <div class="col-sm-9">
        {!! html()->multiselect('roles[]',\App\Models\Admin\Role::where('status', 1)->pluck('name', 'id'),$selected ?? [])->id('roles')->class('form-control select2')->attributes(['multiple'=>true, 'required'=>true]) !!}
    </div>
</div>

<div class="form-group row">
    <label class="col-sm-3 col-form-label" for="status">Status</label>
    <div class="col-sm-2">
        {!! html()->select('status',[''=>'Status','1' => 'Enable', '0' => 'Disable'])->class('form-control')->required() !!}
    </div>
</div>
