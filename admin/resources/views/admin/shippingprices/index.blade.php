@extends('admin.layouts.app')
@section('content')
<section class="content">
	<div class="container-fluid">
		<h4 class="heading text-capitalize">
            <a href="{{ route('admin.'.$module.'.index') }}">Shipping Prices</a>
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
                <div class="alert alert-info">
                    Delivery charge is calculated by selected address distance first. If the order amount matches an active Free Shipping row, the delivery charge becomes ₹0 inside the service area.
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
                                    <th>Title</th>
                                    <th>From KM</th>
                                    <th>To KM</th>
                                    <th>Minimum Order Amount</th>
                                    <th>Maximum Order Amount</th>
                                    <th>Shipping Amount</th>
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
                                    <td>{{$row->id}}</td>
                                    <td>{{$row->title}}</td>
                                    <td>{{$row->from_km}}</td>
                                    <td>{{$row->to_km}}</td>
                                    <td>{!! config('app.currency') !!}{{$row->min_order_amount}}</td>
                                    <td>{!! config('app.currency') !!}{{$row->max_order_amount}}</td>
                                    <td>{!! config('app.currency') !!}{{$row->shipping_amount}}</td>
                                    <td>
                                        @can($module.'_edit')
                                        <label class="switch">
                                        {{ html()->checkbox('status', $row->status, null)->class('status')->id('status_'.$row->id)->attributes(['aria-label'=>'Toggle shipping price status', 'data-id'=>$row->id,'data-url'=>route('admin.'.$module.'.update.status',['id'=>$row->id])]) }}
                                        <span class="slider round"></span>
                                        </label>
                                        @else <span class="badge badge-{{ $row->status ? 'success' : 'secondary' }}">{{ $row->status ? 'Enabled' : 'Disabled' }}</span> @endcan
                                    </td>
                                    <td>{{$row->created_at}}</td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="{{ route('admin.'.$module.'.edit',['shippingprice'=>$row->id]) }}"><i class="fas fa-edit p-1"></i></a>
                                            <a href="javascript:;" class="delete" title="Delete shipping price" aria-label="Delete shipping price" data-id="{{ $row->id }}" data-url="{{ route('admin.'.$module.'.destroy',['shippingprice'=>$row->id]) }}"><i class="fas fa-trash text-danger p-1" aria-hidden="true"></i></a>
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
