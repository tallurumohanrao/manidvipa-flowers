@extends('admin.layouts.app')
@section('content')
<section class="content">
	<div class="container-fluid">		
		<div class="card shadow mb-4">
            <div class="card-body">
                <ul class="list-inline mb-3 text-right">
                    <li class="list-inline-item float-left">
                        <h4 class="heading text-capitalize">
                            <a href="{{ route('admin.'.$module.'.index') }}">Featured Products</a>
                        </h4>
                    </li>
                    <li class="list-inline-item">
                        <div class="btn-group">
                          <a class="btn btn-danger" href="javascript:;" id="delete_all" data-url="{{ route('admin.'.$module.'.massdestroy') }}" role="button">Delete</a>
                        </div>
                    </li>
                </ul>
                <hr>
            	<div class="row">
                    <div class="col-md-2">
                        {{ html()->form('POST')->route('admin.'.$module.'.store')->class('form-horizontal')->id('form')->open() }}
                        <label for="products" class="col-form-label">Add Featured Products</label>
                    </div>
                    <div class="col-md-5">
                        {!! html()->multiselect('products[]',$products)->id('products')->class('select2 form-control') !!}
                    </div>
                    <button type="submit" class="btn btn-primary">Submit</button>
                    {{ html()->form()->close() }}
                    </div>
                </div>
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
                                    <th>Product Title</th>
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
                                    <td>{{$row->created_at}}</td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="javascript:;" class="delete" data-id="{{ $row->id }}" data-url="{{ route('admin.'.$module.'.destroy',['featuredproduct'=>$row->id]) }}"><i class="fas fa-trash text-danger p-1"></i></a>
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
</section>
@endsection
