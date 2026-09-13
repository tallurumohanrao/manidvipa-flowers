@extends('admin.layouts.app')
@section('content')
<section class="content">
	<div class="container-fluid">
        @php($canEditSettings = \Illuminate\Support\Facades\Gate::allows($module.'_edit'))
		<ul class="list-inline mb-3 text-right">
            <li class="list-inline-item">
                <div class="btn-group">
                @if($canEditSettings)
                    <a href="javascript:$('#form').submit();" class="btn btn-primary">Save</a>
                @endif
                    <a href="{{ route('admin.'.$module.'.index') }}" class="btn btn-danger">Cancel</a>
				</div>
			</li>
		</ul>
        <hr>
		<div class="card shadow mb-4">
            <div class="card-body">
            @if($canEditSettings)
                {{ html()->form('POST')->route('admin.'.$module.'.store')->id('form')->attributes(['enctype'=>'multipart/form-data'])->open() }}
                @include('admin.'.$module.'.fields')
                {!! html()->button('Save','submit')->class('btn btn-primary d-none')->id('save') !!}
                {{ html()->form()->close() }}
            @else
                @include('admin.'.$module.'.fields')
            @endif
            </div>
        </div>
    </div>
</section>
@stop
