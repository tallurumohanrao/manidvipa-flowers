@extends('admin.layouts.auth')
@section('content')
<h4 class="mb-3 f-w-400">Reset Password</h4>
<form method="POST" action="{{ route('admin.password.update') }}">
    @csrf
    <input type="hidden" name="token" value="{{ $token }}">
    <div class="input-group mb-2">
        <div class="input-group-prepend">
            <span class="input-group-text"><i class="feather icon-mail"></i></span>
        </div>
        <input type="email" name="email" value="{{ old('email', $email ?? null) }}" class="form-control" placeholder="Email" autocomplete="email" required>
    </div>
    @error('email')
        <span class="text-danger d-block mb-2">{{ $message }}</span>
    @enderror
    <div class="input-group mb-3">
        <div class="input-group-prepend">
            <span class="input-group-text"><i class="feather icon-lock"></i></span>
        </div>
        <input type="password" name="password" class="form-control" placeholder="Password" autocomplete="new-password" required>
    </div>
    @error('password')
        <span class="text-danger d-block mb-2">{{ $message }}</span>
    @enderror
    <div class="input-group mb-3">
        <div class="input-group-prepend">
            <span class="input-group-text"><i class="feather icon-lock"></i></span>
        </div>
        <input type="password" name="password_confirmation" class="form-control" placeholder="Password Confirmation" autocomplete="new-password" required>
    </div>
    @error('password_confirmation')
        <span class="text-danger d-block mb-2">{{ $message }}</span>
    @enderror
    <button type="submit" class="btn btn-primary mb-4">Reset Password</button>
</form>
<p class="mb-2 text-muted">Back to <a href="{{ route('admin.login') }}" class="f-w-400">Login</a>?</p>
@endsection
