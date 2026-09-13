@extends('admin.layouts.app')
@section('content')
<section class="content">
	<div class="container-fluid">
        <div class="row">
            <ul class="list-inline">
                <li class="list-inline-item">
                    <div class="btn-group">
                        <p><a href="{{ route('admin.'.$module.'.images',['id'=>$row->id]) }}" class="btn btn-primary text-right">Images</a></p>
                        <p><a href="{{ route('admin.'.$module.'.weights',['id'=>$row->id]) }}" class="btn btn-primary text-right">Weights &amp; stock</a></p>
                        {{--<p><a target="_blank" href="{{ route('admin.'.$module.'.sizes',['id'=>$row->id]) }}" class="btn btn-primary text-right">Sizes</a></p>--}}
                        <p><a href="{{ route('admin.'.$module.'.reviews',['id'=>$row->id]) }}" class="btn btn-primary text-right">Reviews</a></p>
                    </div>
                </li>
            </ul>
            <ul class="list-inline">
                <li class="list-inline-item">
                    <div class="btn-group">
                        <a href="{{ route('admin.'.$module.'.index') }}" class="btn btn-danger">Cancel</a>
                    </div>
                </li>
            </ul>
        </div>
        <hr>
		<div class="card shadow mb-4">
            <div class="card-body">
                <h5 class="text-capitalize">Edit {{ Str::singular($module) }} - {{ $row->title }}</h5>
                <hr>
                {{ html()->model($row)->form('PATCH')->route('admin.'.$module.'.update', ['product'=>$row->id])->class('')->id('form')->attributes(['enctype'=>'multipart/form-data'])->open() }}
                @include('admin.'.$module.'.form')
                @include('admin.partials.save')
                {{ html()->form()->close() }}
            </div>
        </div>
    </div>
</section>
@endsection
