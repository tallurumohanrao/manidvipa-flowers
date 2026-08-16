@extends('admin.layouts.app')
@section('content')
<section class="content">
	<div class="container-fluid">
		<ul class="list-inline mb-3 text-right">
            <li class="list-inline-item">
                <div class="btn-group">
                    <a href="{{ route('admin.'.$module.'.index') }}" class="btn btn-danger">Cancel</a>
				</div>
			</li>
		</ul>
        <hr>
		<div class="card shadow mb-4">
            <div class="card-body">
                <h5 class="text-capitalize">Edit {{ Str::singular($module) }}</h5>
                <hr>
                {!! Form::model($row, ['method' => 'PATCH','route' => ['admin.'.$module.'.update', $row],'id'=>'form', 'files'=>true]) !!}
                @include('admin.'.$module.'.form')
                @include('admin.partials.save')
                {!! Form::close() !!}
            </div>
        </div>
    </div>
</section>
@endsection