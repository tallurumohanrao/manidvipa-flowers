<!DOCTYPE html>
<html lang="en">
<head>
    <title>@yield('title')</title>
	<!-- Meta -->
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<meta content="IE=edge" http-equiv="X-UA-Compatible">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('storage/website/'.config('SITE_FAVICON')) }}">
    <link rel="stylesheet" href="{{ asset('assets/admin/css/sb-admin-2.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/admin/css/auth/style.css') }}" />
</head>
<!-- [ auth-signin ] start -->
<div class="auth-wrapper">
    <div class="col-md-4">
        <div class="auth-content container">
            <div class="card">
                <div class="row align-items-center">
					<div class="card-body">
                        @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                        @endif
						@yield('content')
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
</body>
</html>
