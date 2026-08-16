@extends('admin.layouts.app')
@section('content')
<section class="content">
	<div class="container-fluid">
        <h4 class="heading text-capitalize">
            <a href="{{ route('admin.'.$module.'.index') }}">Payment Statuses</a>
        </h4>
		<hr>
		<ul class="list-inline mb-3 text-right">
			<li class="list-inline-item">
				<div class="btn-group">
                    <a href="{{ route('admin.'.$module.'.create') }}" class="btn btn-primary">Create</a>
                  {{--<a class="btn btn-danger" href="javascript:;" id="delete_all" data-url="{{ route('admin.'.$module.'.massdestroy') }}" role="button">Delete</a>--}}
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
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Subject</th>
                                    <th>Message</th>
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
                                    <td>{{$row->id}}</td>
                                    <td>{{$row->name}}</td>
                                    <td>{{$row->subject}}</td>
                                    <td>{!! $row->body_html !!}</td>
                                    <td>{{$row->created_at}}</td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="{{ route('admin.'.$module.'.edit',['paymentstatus'=>$row->id]) }}"><i class="fas fa-edit p-1"></i></a>
                                            {{--<a href="javascript:;" class="delete btn btn-danger" data-id="{{ $row->id }}" data-url="{{ route('admin.'.$module.'.destroy',$row) }}"><i class="ti-trash"></i></a>--}}
                                        </div>
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
