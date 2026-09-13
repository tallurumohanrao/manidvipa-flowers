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
                                    <th>Coupon</th>
                                    <th>Usage Limit</th>
                                    <th>Usage Limit Per User</th>
                                    <th>Minimum Purchage Amount</th>
                                    <th>Is Percentage Discount</th>
                                    <th>Discount</th>
                                    <th>Start Date</th>
                                    <th>End Date</th>
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
                                    <td>{{ $row->id }}</td>
                                    <td>{{$row->title }}</td>
                                    <td>{{$row->coupon_code}}</td>
                                    <td>{{$row->usage_limit}}</td>
                                    <td>{{$row->usage_limit_per_user}}</td>
                                    <td>{{$row->minimum_purchage_amount}}</td>
                                    <td>{{$row->is_percentage_discount == 1 ? 'Yes' : 'No' }}</td>
                                    <td>{{$row->discount}}</td>
                                    <td>{{$row->start_date}}</td>
                                    <td>{{$row->end_date}}</td>
                                    <td>
                                        @can($module.'_edit')
                                        <label class="switch">
                                        {{ html()->checkbox('status', $row->status, null)->class('status')->id('status_'.$row->id)->attributes(['aria-label'=>'Toggle coupon status', 'data-id'=>$row->id,'data-url'=>route('admin.'.$module.'.update.status',$row->id)]) }}
                                        <span class="slider round"></span>
                                        </label>
                                        @else <span class="badge badge-{{ $row->status ? 'success' : 'secondary' }}">{{ $row->status ? 'Enabled' : 'Disabled' }}</span> @endcan
                                    </td>
                                    <td>{{$row->created_at}}</td>
                                    <td>
                                    @can($module.'_edit')
                                            <a href="{{ route('admin.'.$module.'.edit',['coupon'=>$row->id]) }}" title="Edit coupon" aria-label="Edit coupon"><i class="fas fa-edit p-1" aria-hidden="true"></i></a>
                                        @endcan
                                        @can($module.'_delete')
                                            <a href="javascript:;" class="delete" title="Delete coupon" aria-label="Delete coupon" data-id="{{ $row->id }}" data-url="{{ route('admin.'.$module.'.destroy',['coupon'=>$row->id]) }}"><i class="fas fa-trash text-danger p-1" aria-hidden="true"></i></a>
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
