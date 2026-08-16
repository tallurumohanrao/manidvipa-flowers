@extends('admin.layouts.app')
@section('content')
<section class="content">
	<div class="container-fluid">
		<h4 class="heading text-capitalize">
            <a href="{{ route('admin.'.$module.'.index') }}">{{ $module }}</a>
        </h4>
		<hr>
		<ul class="list-inline mb-3 text-right">
			<li class="list-inline-item">
				<div class="btn-group">
                @can($module.'_create')
                <a href="{{ route('admin.'.$module.'.create') }}" class="btn btn-primary">Create</a>
                @endcan
                @can($module.'_delete')
                    <a class="btn btn-danger" href="javascript:;" id="delete_all" data-url="{{ route('admin.'.$module.'.massdestroy') }}" role="button">Delete</a>
                @endcan
				</div>
			</li>
		</ul>
		<div class="card shadow mb-4">
            <div class="card-body">
            	<div class="row">
            		<div class="col-sm-12">
            		<div class="table-responsive">
            			<table class="table table-bordered table-hover">
                            <thead>
                                <tr role="row">
                                    <th>
                                        <div class="custom-control custom-checkbox">
                                            {!! html()->checkbox('selectAll')->id('selectAll')->class('custom-control-input') !!}
                                            <label class="custom-control-label" for="selectAll"></label>
                                        </div>
                                    </th>
                                    <th>ID</th>
                                    <th>Title</th>
                                    <th>Image</th>
                                    <th>Page</th>
                                    <th>Priority</th>
                                    <th>Status</th>
                                    <th>Created At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>

                            <tbody>
                            @foreach($data as $row)
                                <tr id="row-{{ $row->id }}">
                                    <td>
                                        <div class="custom-control custom-checkbox sub_chk">
                                            {!! html()->checkbox('id[]', false, $row->id)->id($row->id)->class('custom-control-input') !!}
                                            <label class="custom-control-label" for="{{ $row->id }}"></label>
                                        </div>
                                    </td>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{$row->title}}</td>
                                    <td>{{ html()->img(asset('storage/'.$module.'/'. @$row->image ), null)->attributes(array('title' => @$row->name ,'width' => '70px')) }}</td>
                                    <td>@if($row->page) {{ config('app.pages')[$row->page] }} @endif</td>
                                    <td>{{$row->priority}}</td>
                                    <td>
                                        <label class="switch">
                                            {{ html()->checkbox('status', $row->status, null)->class('status')->id('status_'.$row->id)->attributes(['data-id'=>$row->id,'data-url'=>route('admin.'.$module.'.update.status',$row)]) }}
                                        <span class="slider round"></span>
                                        </label>
                                    </td>
                                    <td>{{$row->created_at}}</td>
                                    <td>
                                        @can($module.'_edit')
                                            <a href="{{ route('admin.'.$module.'.edit',$row) }}"><i class="fas fa-edit p-1"></i></a>
                                        @endcan
                                        @can($module.'_delete')
                                            <a href="javascript:;" class="delete" data-id="{{ $row->id }}" data-url="{{ route('admin.'.$module.'.destroy',$row) }}"><i class="fas fa-trash text-danger p-1"></i></a>
                                        @endcan
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    </div>
                </div>
                <div class="row">
                	<div class="col-sm-12 col-md-5">
                		<p>Showing {{ $data->firstItem() }} to {{ $data->lastItem() }} of {{ $data->total() }} entries</p>
                	</div>
                	<div class="col-sm-12 col-md-7">
                        {{ $data->onEachSide(config('onEachSide'))->links() }}
                	</div>
                </div>
            </div>
        </div>
	</div>
</section>
@endsection
