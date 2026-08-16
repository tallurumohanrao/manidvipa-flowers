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
                <h5>Edit Post</h5>
                <hr>
                {{ html()->model($row)->form('PATCH')->route('admin.'.$module.'.update', ['post'=>$row->id])->attributes(['enctype'=>'multipart/form-data'])->open() }}
                @include('admin.'.$module.'.form')
                @include('admin.partials.save')
                {{ html()->form()->close() }}
                {{-- @include('admin.posts.comments') --}}
            </div>
        </div>
    </div>
</section>
@endsection
