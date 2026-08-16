@extends('admin.layouts.app')
@section('content')
<section class="content">
	<div class="container-fluid">
		<ul class="list-inline mb-3 text-right">
            <li class="list-inline-item">
                <div class="btn-group">
                    <a href="{{ route('admin.'.crudName().'.index') }}" class="btn btn-danger">Cancel</a>
				</div>
			</li>
		</ul>
        <hr>
		<div class="card shadow mb-4">
            <div class="card-body">
                <h5>Create Client</h5>
                <hr>
                {!! Form::open(['method' => 'POST','route' => ['admin.'.crudName().'.store'],'id'=>'form', 'files'=>true]) !!}
                @include('admin.'.crudName().'.form')
                @include('admin.partials.save')
                {!! Form::close() !!}
            </div>
        </div>
    </div>
</section>
@endsection