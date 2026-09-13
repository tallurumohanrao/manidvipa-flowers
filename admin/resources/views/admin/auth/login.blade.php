@extends('admin.layouts.auth')
@section('title','Admin Login')
@section('content')
<h4 class="mb-3 f-w-400">Login into your account</h4>
{{ html()->form('POST')->route('admin.login')->open() }}
<div class="input-group mb-2">
    <label for="email" class="sr-only">Email address</label>
    <div class="input-group-prepend">
        <span class="input-group-text"><i class="feather icon-mail"></i></span>
    </div>
    {{ html()->email('email')->id('email')->class('form-control')->placeholder('Email')->attributes(['autocomplete'=>'email', 'required'=>true, 'autofocus'=>true]) }}
</div>
<div class="input-group mb-3">
    <label for="password" class="sr-only">Password</label>
    <div class="input-group-prepend">
        <span class="input-group-text"><i class="feather icon-lock"></i></span>
    </div>
    {{ html()->password('password')->id('password')->class('form-control')->placeholder('Password')->attributes(['autocomplete'=>'current-password', 'required'=>true]) }}
</div>

<div class="form-group text-left mt-2">
    <div class="checkbox checkbox-primary d-inline">
        {!! html()->checkbox('remember')->id('remember')->class('custom-control-input') !!}
        <label for="remember" class="cr">Remember me</label>
    </div>
</div>

{{ html()->submit('Login')->class('btn btn-primary mb-4') }}
{{ html()->form()->close() }}
<p class="mb-2 text-muted">Forgot password? {{ html()->a(route('admin.password.request'),'Reset')->class('f-w-400') }}</p>
@endsection
