@extends('admin.layouts.app')
@section('content')
<section class="content">
	<div class="container-fluid">
		<h4 class="heading text-capitalize">
            {{ link_to_route('admin.'.$module.'.index', $module, NULL, ['class' => '']) }}
        </h4>
		<hr>
		<ul class="list-inline mb-3 text-right">
			<li class="list-inline-item">
				<div class="btn-group">
                @can($module.'_create')
                {{ link_to_route('admin.'.$module.'.create', 'Create', NULL, ['class' => 'btn btn-primary']) }}
                @endcan
                @can($module.'_delete')
                    <a class="btn btn-danger" href="javascript:;" id="delete_all" data-url="{{ route('admin.'.$module.'.massdestroy') }}" role="button">Delete</a>
                @endcan
				</div>
			</li>
		</ul>
		<div class="card shadow mb-4">
            <div class="card-body">
                {!! Form::open(['method' => 'GET','id' => 'search','route' => ['admin.'.$module.'.index']]) !!}
                <div class="row">
                    <div class="col-md-12 d-flex align-items-center justify-content-between">
                        @include('admin.includes.items')
                        <ul class="list-inline">
                            <li class="list-inline-item">
                            {!! Form::text('profileId', request('profileId') ,['class' => 'form-control', 'placeholder' => 'profile Id', 'autocomplete' => 'off']) !!}
                            </li>
                            <li class="list-inline-item">
                            {!! Form::select('status', array(''=>'Status','1' => 'Enable', '0' => 'Disable'), request('status'), [ 'class' => 'form-control', 'autocomplete' => 'off' ]); !!}
                            </li>
                            <li class="list-inline-item">
                            {!! Form::button('Search', ['type'=>'submit', 'class'=>'btn btn-primary']) !!}
                            </li>
                        </ul>
                    </div>
                </div>
                {!! Form::close() !!}
            	<div class="row">
            		<div class="col-sm-12">
                        <div class="table-responsive">
                            <table class="table table-bordered  table-hover tablegrid">
                                <thead>
                                    <tr role="row">
                                        <th>
                                            <div class="custom-control custom-checkbox">
                                                {!! Form::checkbox('selectAll', NULL, NULL ,['id' => 'selectAll', 'class' => 'custom-control-input', 'aria-label' => 'Select All' ]) !!}
                                                <label class="custom-control-label" for="selectAll"></label>
                                            </div>
                                        </th>
                                        <th>Profile Id</th>
                                        <th>Name</th>
                                        <th>DOB</th>
                                        <th>Country</th>
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
                                                {!! Form::checkbox('id[]', $row->id, NULL, ['id'=>$row->id,'class' => 'custom-control-input']) !!}
                                                <label class="custom-control-label" for="{{ $row->id }}"></label>
                                            </div>
                                        </td>
                                        <td>{{$row->profile_id}}</td>
                                        <td>{!! $row->name !!}</td>
                                        <td>{!! $row->dob !!}</td>
                                        <td>{!! $row->country !!}</td>
                                        <td>
                                            <label class="switch">
                                            {{ Form::checkbox('status', null, $row->status,['class'=>'status','id'=>'status_'.$row->id,'data-id'=>$row->id,'data-url'=>route('admin.'.$module.'.update.status',$row)]) }}
                                            <span class="slider round"></span>
                                            </label>
                                        </td>
                                        <td>{{$row->created_at}}</td>
                                        <td>
                                            @can($module.'_edit')
                                                <a href="{{ route('admin.'.$module.'.edit',$row) }}" title="Edit item" aria-label="Edit item"><i class="fas fa-edit p-1" aria-hidden="true"></i></a>
                                            @endcan
                                            @can($module.'_delete')
                                                <a href="javascript:;" class="delete" title="Delete item" aria-label="Delete item" data-id="{{ $row->id }}" data-url="{{ route('admin.'.$module.'.destroy',$row) }}"><i class="fas fa-trash text-danger p-1" aria-hidden="true"></i></a>
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
