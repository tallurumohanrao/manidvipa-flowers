@extends('admin.layouts.auth')
@section('title','Admin Login')
@section('content')
<h4 class="mb-3 f-w-400">Login into your account</h4>
{{ html()->form('POST')->route('admin.login')->open() }}
<div class="input-group mb-2">
    <div class="input-group-prepend">
        <span class="input-group-text"><i class="feather icon-mail"></i></span>
    </div>
    {{ html()->email('email')->class('form-control')->placeholder('Email') }}
</div>
<div class="input-group mb-3">
    <div class="input-group-prepend">
        <span class="input-group-text"><i class="feather icon-lock"></i></span>
    </div>
    {{ html()->password('password')->class('form-control')->placeholder('Password') }}
</div>

<div class="form-group text-left mt-2">
    <div class="checkbox checkbox-primary d-inline">
        {!! html()->checkbox('remember')->checked()->class('custom-control-input') !!}
        <label for="remember" class="cr">Remember me</label>
    </div>
</div>

{{ html()->submit('Login')->class('btn btn-primary mb-4') }}
{{ html()->form()->close() }}
<p class="mb-2 text-muted">Forgot password? {{ html()->a(route('admin.password.request'),'Reset')->class('f-w-400') }}</p>
@endsection
