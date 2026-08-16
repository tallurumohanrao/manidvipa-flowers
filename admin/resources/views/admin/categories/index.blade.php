@extends('admin.layouts.app')
@section('content')
<section class="content">
    <div class="container-fluid">
		<div class="card shadow mb-4">
            <div class="card-body">
                @include('admin.includes.index')
                {{ html()->form('GET')->route('admin.'.$module.'.index')->id('search')->open() }}
                <div class="row">
                    <div class="col-md-12 d-flex align-items-center justify-content-between">
                        @include('admin.includes.items')
                        <ul class="list-inline">
                            <li class="list-inline-item">
                            {{ html()->text('title',request('title'))->class('form-control')->placeholder('Title') }}
                            </li>
                            <li class="list-inline-item">
                                {!! html()->select('status', array('' => 'Status', '1' => 'Enable', '0' => 'Disable'))->value(request('status'))->id('status')->class('custom-select custom-select-sm form-control form-control-sm w-80') !!}
                            </li>
                            <li class="list-inline-item">
                            {!! html()->button('Search','submit')->class('btn btn-primary form-control form-control-sm') !!}
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
                                    <th>Title</th>
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
                                            {!! html()->checkbox('id[]', false, $row->id)->id($row->id)->class('custom-control-input') !!}
                                        <label class="custom-control-label" for="{{ $row->id }}"></label>
                                        </div>
                                    </td>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $row->title }}</td>
                                    <td>{{ html()->img(asset('storage/'.$module.'/'. @$row->image ), null)->attributes(array('title' => @$row->title ,'width' => '70px')) }}</td>
                                    <td>
                                        <label class="switch">
                                        {{ html()->checkbox('status', $row->status, null)->class('status')->id('status_'.$row->id)->attributes(['data-id'=>$row->id,'data-url'=>route('admin.'.$module.'.update.status',['id'=>$row->id])]) }}
                                        <span class="slider round"></span>
                                        </label>
                                    </td>
                                    <td>{{$row->created_at}}</td>
                                    <td>
                                        <div class="btn-group">
                                        @can($module.'_edit')
                                            <a href="{{ route('admin.'.$module.'.edit',['category'=>$row->id]) }}"><i class="fas fa-edit p-1"></i></a>
                                        @endcan
                                        @can($module.'_delete')
                                            <a href="javascript:;" class="delete" data-id="{{ $row->id }}" data-url="{{ route('admin.'.$module.'.destroy',['category'=>$row->id]) }}"><i class="fas fa-trash text-danger p-1"></i></a>
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
