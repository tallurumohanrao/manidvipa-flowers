@extends('admin.layouts.auth')
@section('title','Reset Password')
@section('content')
<h4 class="mb-3 f-w-400">{{ __('Reset Password') }}</h4>
@if (session('status'))
    <div class="alert alert-success" role="alert">{{ session('status') }}</div>
@endif
<form method="POST" action="{{ route('admin.password.email') }}">
@csrf
<div class="input-group mb-2">
    <div class="input-group-prepend">
        <span class="input-group-text"><i class="feather icon-mail"></i></span>
    </div>
    <input type="email" name="email" value="{{ old('email') }}" class="form-control" placeholder="Email" autocomplete="email" required>
</div>
@error('email')
    <span class="text-danger d-block mb-2">{{ $message }}</span>
@enderror
<button type="submit" class="btn btn-primary mb-4">Send Password Reset Link</button>
</form>
@endsection
