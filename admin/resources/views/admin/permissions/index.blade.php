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
            			<table class="table table-bordered table-hover tablegrid">
                            <thead>
                                <tr role="row">
                                    <th>
                                        @can($module.'_delete')
                                        <div class="custom-control custom-checkbox">
                                            {!! html()->checkbox('selectAll')->id('selectAll')->class('custom-control-input') !!}
                                            <label class="custom-control-label" for="selectAll"><span class="sr-only">Select all permissions</span></label>
                                        </div>
                                        @endcan
                                    </th>
                                    <th>ID</th>
                                    <th>Group</th>
                                    <th>Module</th>
                                    <th>View</th>
                                    <th>Create</th>
                                    <th>Edit</th>
                                    <th>Delete</th>
                                    <th>Group Sort Order</th>
                                    <th>Module Sort Order</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>

                            <tbody>
                            @foreach($data as $row)
                                <tr id="row-{{ $row->id }}">
                                    <td>
                                        @can($module.'_delete')
                                        <div class="custom-control custom-checkbox sub_chk">
                                            {!! html()->checkbox('id[]', false, $row->id)->id($row->id)->class('custom-control-input') !!}
                                            <label class="custom-control-label" for="{{ $row->id }}"><span class="sr-only">Select {{ $row->module }}</span></label>
                                        </div>
                                        @endcan
                                    </td>
                                    <td>{{ $row->id }}</td>
                                    <td>{{ $row->group_name }}</td>
                                    <td>
                                        @can($module.'_edit')
                                            <a href="{{ route('admin.'.$module.'.edit',$row) }}">{{ $row->module }}</a>
                                        @else
                                            {{ $row->module }}
                                        @endcan
                                    </td>
                                    <td>{{ $row->view }}</td>
                                    <td>{{ $row->create }}</td>
                                    <td>{{ $row->edit }}</td>
                                    <td>{{ $row->delete }}</td>
                                    <td>{{ $row->group_sort_order }}</td>
                                    <td>{{ $row->module_sort_order }}</td>
                                    <td>
                                        @can($module.'_edit')
                                            <label class="switch">
                                                {{ html()->checkbox('status', $row->status, null)->class('status')->id('status_'.$row->id)->attributes(['aria-label'=>'Toggle '.$row->module.' permission status', 'data-id'=>$row->id, 'data-url'=>route('admin.permissions.update.status', ['id'=>$row->id])]) }}
                                                <span class="slider round"></span>
                                            </label>
                                        @else
                                            <span class="badge badge-{{ $row->status ? 'success' : 'secondary' }}">{{ $row->status ? 'Enabled' : 'Disabled' }}</span>
                                        @endcan
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                        @can($module.'_edit')
                                            <a href="{{ route('admin.'.$module.'.edit',$row) }}" title="Edit {{ $row->module }}" aria-label="Edit {{ $row->module }}"><i class="fas fa-edit p-1" aria-hidden="true"></i></a>
                                        @endcan
                                        @if($row->module !== 'Permissions')
                                            @can($module.'_delete')
                                                <a href="javascript:;" class="delete" title="Delete {{ $row->module }}" aria-label="Delete {{ $row->module }}" data-id="{{ $row->id }}" data-url="{{ route('admin.'.$module.'.destroy',$row) }}"><i class="fas fa-trash text-danger p-1" aria-hidden="true"></i></a>
                                            @endcan
                                        @endif
                                        </div>
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
                		<div class="dataTables_info" id="dataTable_info" role="status" aria-live="polite">Showing {{ $data->firstItem() }} to {{ $data->lastItem() }} of {{ $data->total() }} entries</div>
                	</div>
                	<div class="col-sm-12 col-md-7">
                		{{ $data->links() }}
                	</div>
                </div>
            </div>
        </div>
	</div>
</section>
@endsection
