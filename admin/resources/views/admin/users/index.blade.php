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
                {{ html()->form('GET')->route('admin.'.$module.'.index')->class('form-horizontal')->id('search')->open() }}
                <div class="row">
                    <div class="col-md-12 d-flex align-items-center justify-content-between">
                        @include('admin.includes.items')
                        <ul class="list-inline">
                            <li class="list-inline-item">
                            {{ html()->text('name',request('name'))->class('form-control')->placeholder('Name') }}
                            </li>
                            <li class="list-inline-item">
                            {{ html()->email('email',request('email'))->class('form-control')->placeholder('Email') }}
                            </li>
                            <li class="list-inline-item">
                            {!! html()->select('status',[''=>'Status','1' => 'Enable', '2' => 'Disable'])->value(request('status'))->id('status')->class('form-control') !!}
                            </li>
                            <li class="list-inline-item">
                            {!! html()->button('Search','submit')->class('btn btn-primary') !!}
                            </li>
                        </ul>
                    </div>
                </div>
                {{ html()->form()->close() }}
            	<div class="row">
            		<div class="col-sm-12">
            		<div class="table-responsive">
            			<table class="table table-bordered  table-hover tablegrid">
                            <thead>
                                <tr role="row">
                                    <th>
                                        <div class="custom-control custom-checkbox">
                                            {!! html()->checkbox('selectAll')->id('selectAll')->class('custom-control-input') !!}
                                            <label class="custom-control-label" for="selectAll"></label>
                                        </div>
                                    </th>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Status</th>
                                    <th>Created At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>

                            <tbody>
                            @foreach($users as $user)
                            <tr id="row-{{ $user->id }}">
                                <td>
                                    <div class="custom-control custom-checkbox sub_chk">
                                    {!! html()->checkbox('id[]', false, $user->id)->id($user->id)->class('custom-control-input') !!}
                                    <label class="custom-control-label" for="{{ $user->id }}"></label>
                                    </div>
                                </td>
                                <td>{{$user->id}}</td>
                                <td>{{$user->name}}</td>
                                <td>{{$user->email}}</td>
                                <td>
                                    <label class="switch">
                                    {{ html()->checkbox('status', $user->status, null)->class('status')->id('status_'.$user->id)->attributes(['data-id'=>$user->id,'data-url'=>route('admin.'.$module.'.update.status',$user)]) }}
                                    <span class="slider round"></span>
                                    </label>
                                </td>
                                <td>{{$user->created_at}}</td>
                                <td>
                                @can($module.'_edit')
                                    <a href="{{ route('admin.'.$module.'.edit',$user) }}"><i class="fas fa-edit p-1"></i></a>
                                @endcan
                                @can($module.'_delete')
                                    <a href="javascript:;" class="delete" data-id="{{ $user->id }}" data-url="{{ route('admin.'.$module.'.destroy',$user) }}"><i class="fas fa-trash text-danger p-1"></i></a>
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
                		<div class="dataTables_info" id="dataTable_info" role="status" aria-live="polite">Showing {{ $users->firstItem() }} to {{ $users->lastItem() }} of {{ $users->total() }} entries</div>
                	</div>
                	<div class="col-sm-12 col-md-7">
                		{{ $users->links() }}
                	</div>
                </div>
            </div>
        </div>
	</div>
</section>
@stop
