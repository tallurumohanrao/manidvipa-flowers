@extends('admin.layouts.app')
@section('content')
<section class="content">
	<div class="container-fluid">
		<h4 class="heading text-capitalize">{{ $module }}</h4>
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
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Designation</th>
                                    <th>Image</th>
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
                                    <td>{{ $row->id }}</td>
                                    <td>{{$row->name}}</td>
                                    <td>{{$row->designation}}</td>
                                    <td>{{ Html::image(asset('storage/'.$module.'/'. @$row->image ), null , array('title' => @$row->image ,'width' => '70px')) }}</td>
                                    <td>
                                        <label class="switch">
                                        {{ Form::checkbox('status', null, $row->status,['class'=>'status','id'=>'status_'.$row->id,'data-id'=>$row->id,'data-url'=>route('admin.'.$module.'.update.status',$row)]) }}
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
            </div>
        </div>
	</div>
</section>
@endsection
