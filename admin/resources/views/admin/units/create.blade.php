@extends('admin.layouts.app')
@section('content')
<section class="content">
	<div class="container-fluid">
		<ul class="list-inline mb-3 text-right">
            <li class="list-inline-item">
                <div class="btn-group">
                    <a href="/admin/units" class="btn btn-danger">Cancel</a>
				</div>
			</li>
		</ul>
        <hr>
		<div class="card shadow mb-4">
            <div class="card-body">
                <h5>Create Unit</h5>
                <hr>
                {{ html()->form('POST')->route('admin.units.store')->class('form-horizontal')->id('form')->attributes(['enctype'=>'multipart/form-data'])->open() }}
                @include('admin.units.form')
                @include('admin.partials.save')
                {{ html()->form()->close() }}
            </div>
        </div>
    </div>
</section>
@endsection
