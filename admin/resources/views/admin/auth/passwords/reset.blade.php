@extends('admin.layouts.auth')
@section('content')
<h4 class="mb-3 f-w-400">Reset Password</h4>
{!! Form::open(['method' => 'POST', 'route' => ['password.update']]) !!}
{!! Form::hidden('token', $token) !!}
    <div class="input-group mb-2">
        <div class="input-group-prepend">
            <span class="input-group-text"><i class="feather icon-mail"></i></span>
        </div>
        {!! Form::email('email', null ,['class' => 'form-control', 'placeholder' => 'Email', 'autocomplete' => 'off']) !!}
    </div>
    <div class="input-group mb-3">
        <div class="input-group-prepend">
            <span class="input-group-text"><i class="feather icon-lock"></i></span>
        </div>
        {!! Form::password('password', ['placeholder' => 'Password','class' => 'form-control', 'autocomplete' => 'off']) !!}
    </div>
    <div class="input-group mb-3">
        <div class="input-group-prepend">
            <span class="input-group-text"><i class="feather icon-lock"></i></span>
        </div>
        {!! Form::password('password_confirmation', ['placeholder' => 'Password Confirmation','class' => 'form-control', 'autocomplete' => 'off']) !!}
    </div>
    {!! Form::button('Reset Password',['type'=>'submit','class'=>'btn btn-primary mb-4']) !!}
{!! Form::close() !!}
<p class="mb-2 text-muted">Back to {{ link_to_route('admin.login','Login','',['class'=>'f-w-400']) }}?</p>
@endsection
