@extends('admin.layouts.app')
@section('content')
<section class="content">
	<div class="container-fluid">
        <div class="row">
            <div class="col-md-4 col-lg-4 col-sm-12 col-xs-12 offset-md-2">
                @include('admin.account.edit')
            </div>
            <div class="col-md-4 col-lg-4 col-sm-12 col-xs-12 offset-md-2">
                @include('admin.account.change-password')
            </div>
        </div>
    </div>
</section>
@endsection