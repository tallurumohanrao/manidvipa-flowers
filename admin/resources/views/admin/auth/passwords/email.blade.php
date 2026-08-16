@extends('admin.layouts.auth')
@section('title','Reset Password')
@section('content')
<h4 class="mb-3 f-w-400">{{ __('Reset Password') }}</h4>
{!! Form::open(['method' => 'POST', 'route' => ['admin.password.email']]) !!}
<div class="input-group mb-2">
    <div class="input-group-prepend">
        <span class="input-group-text"><i class="feather icon-mail"></i></span>
    </div>
    {!! Form::email('email', null ,['class' => 'form-control', 'placeholder' => 'Email', 'autocomplete' => 'off']) !!}
</div>
@error('email')
    <span class="invalid-feedback" role="alert">
        <strong>{{ $message }}</strong>
    </span>
@enderror
{!! Form::button('Send Password Reset Link',['type'=>'submit','class'=>'btn btn-primary mb-4']) !!}
{!! Form::close() !!}
@endsection
