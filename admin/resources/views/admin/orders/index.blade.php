@extends('admin.layouts.app')
@section('content')
<section class="content">
	<div class="container-fluid">
		<div class="card shadow mb-4">
            <div class="card-body">
                <ul class="list-inline mb-3 text-right">
                    <li class="list-inline-item float-left">
                        <h4 class="heading text-capitalize">
                            <a href="{{ route('admin.'.$module.'.index') }}">{{ $module }}</a>
                        </h4>
                    </li>
                    <li class="list-inline-item">
                        <div class="btn-group">
                          <a class="btn btn-danger" href="javascript:;" id="delete_all" data-url="{{ route('admin.'.$module.'.massdestroy') }}" role="button">Delete</a>
                        </div>
                    </li>
                </ul><hr>
                {{ html()->form('GET')->route('admin.'.$module.'.index')->id('search')->open() }}
                <div class="row">
                    <div class="col-md-12 d-flex align-items-center justify-content-between">
                        @include('admin.includes.items')
                        <ul class="list-inline">
                            <li class="list-inline-item">
                            {{ html()->text('orderId',request('orderId'))->class('form-control form-control-sm')->placeholder('Order Id') }}
                            </li>
                            
                            <li class="list-inline-item">
                                {!! html()->select('orderStatus', $orderStatuses)->placeholder('-- Order Status --')->value(request('orderStatus'))->id('orderStatus')->class('custom-select custom-select-sm form-control form-control-sm w-80') !!}
                            </li>
                            
                            <li class="list-inline-item">
                                {!! html()->select('shippingStatus', $shippingStatuses)->placeholder('-- Shipping Status --')->value(request('shippingStatus'))->id('shippingStatus')->class('custom-select custom-select-sm form-control form-control-sm w-80') !!}
                            </li>
                            
                            <li class="list-inline-item">
                                {!! html()->select('paymentStatus', paymentStatuses())->placeholder('-- Payment Status --')->value(request('paymentStatus'))->id('paymentStatus')->class('custom-select custom-select-sm form-control form-control-sm w-80') !!}
                            </li>
                            
                            <li class="list-inline-item">
                                <label for="deliveryDate">Delivery Date</label>
                                {!! html()->date('deliveryDate')->placeholder('-- Delivery Date --')->value(request('deliveryDate'))->id('deliveryDate')->class('form-control form-control-sm w-80') !!}
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
                                    <th>Order ID</th>
                                    <th>Name</th>
                                    <th>Contact</th>
                                    <th>User Id</th>
                                    <th>Amount</th>
                                    <th>Order Status</th>
                                    <th>Payment Status</th>
                                    <th>Shipping Status</th>
                                    <th>Delivery Date</th>
                                    <th>Created Date</th>
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
                                    <td><a target="_blank" href="{{ route('admin.'.$module.'.show',['order'=>$row->id]) }}">{{ $row->id }}</a></td>
                                    <td>{{ $row->name }}</td>
                                    <td>{!! $row->email .'</br>'. $row->contact_number !!}</td>
                                    <td>
                                        @if($row->user_id)
                                        <a target="_blank" href="{{ route('admin.users.show',$row->user_id) }}">{{ $row->user_id }}</a>
                                        @endif
                                    </td>
                                    <td>{!! currency($row->amount) !!}</td>
                                    <td>{{ $row->order_status }}</td>
                                    <td>{{ $row->payment_status }}</td>
                                    <td>{{ $row->shipping_status }}</td>
                                    <td>{{ $row->serve_date ? date('d/m/Y',strtotime($row->serve_date)) : null }}</td>
                                    <td>{{ date('d/m/Y h:i A',strtotime($row->created_at)) }}</td>
                                    {{-- <td>
                                        <div class="btn-group">
                                            <a href="javascript:;" class="delete" data-id="{{ $row->id }}" data-url="{{ route('admin.'.$module.'.destroy',['order'=>$row->id]) }}"><i class="fas fa-trash text-danger p-1"></i></a>
                                        </div>
                                    </td> --}}
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
