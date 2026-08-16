@extends('admin.layouts.app')
@section('content')
<section class="content">
	<div class="container-fluid">
		<h4 class="heading text-capitalize">
            <a href="/admin/units">Units</a>
        </h4>
		<hr>
		<ul class="list-inline mb-3 text-right">
			<li class="list-inline-item">
				<div class="btn-group">
                @can('units_create')
                <a href="{{ route('admin.units.create') }}" class="btn btn-primary">Create</a>
                @endcan
                @can('units_delete')
                    <a class="btn btn-danger" href="javascript:;" id="delete_all" data-url="{{ route('admin.units.massdestroy') }}" role="button">Delete</a>
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
                                            {!! html()->checkbox('selectAll')->id('selectAll')->class('custom-control-input') !!}
                                            <label class="custom-control-label" for="selectAll"></label>
                                        </div>
                                    </th>
                                    <th>S.No</th>
                                    <th>Name</th>
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
                                    <td>{{ $row->name }}</td>
                                    <td>
                                        <label class="switch">
                                        {{ html()->checkbox('status', $row->status, null)->class('status')->id('status_'.$row->id)->attributes(['data-id'=>$row->id,'data-url'=>route('admin.units.update.status',['id'=>$row->id])]) }}
                                        <span class="slider round"></span>
                                        </label>
                                    </td>
                                    <td>{{$row->created_at}}</td>
                                    <td>
                                        <div class="btn-group">
                                        @can('units_edit')
                                            <a href="{{ route('admin.units.edit',['unit'=>$row->id]) }}"><i class="fas fa-edit p-1"></i></a>
                                        @endcan
                                        @can('units_delete')
                                            <a href="javascript:;" class="delete" data-id="{{ $row->id }}" data-url="{{ route('admin.units.destroy',['unit'=>$row->id]) }}"><i class="fas fa-trash text-danger p-1"></i></a>
                                        @endcan
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
