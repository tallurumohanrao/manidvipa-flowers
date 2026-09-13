@extends('admin.layouts.app')
@push('styles')
<style>
    .order-filter-panel {
        background: #f8f9fc;
        border: 1px solid #e3e6f0;
        border-radius: .5rem;
        padding: 1rem;
    }

    .order-filter-panel label {
        color: #5a5c69;
        display: block;
        font-size: .72rem;
        font-weight: 700;
        letter-spacing: .02em;
        margin-bottom: .3rem;
        text-transform: uppercase;
    }

    .order-filter-panel .form-control,
    .order-filter-panel .custom-select {
        min-height: 38px;
    }

    .order-filter-actions {
        display: flex;
        gap: .5rem;
    }

    .order-queue-actions {
        display: flex;
        flex-wrap: wrap;
        gap: .5rem;
    }

    @media (max-width: 767.98px) {
        .order-filter-actions .btn {
            flex: 1;
        }
    }
</style>
@endpush
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
                          @can($module.'_delete')
                              <a class="btn btn-danger" href="javascript:;" id="delete_all" data-url="{{ route('admin.'.$module.'.massdestroy') }}" role="button">Delete</a>
                          @endcan
                        </div>
                    </li>
                </ul><hr>
                {{ html()->form('GET')->route('admin.'.$module.'.index')->id('search')->open() }}
                <div class="order-filter-panel mb-3">
                    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center mb-3">
                        <div>
                            <h6 class="font-weight-bold text-primary mb-1">Find orders</h6>
                            <small class="text-muted">Search by order number, status, delivery date, queue, or assigned staff.</small>
                        </div>
                        <div class="d-flex align-items-center mt-3 mt-lg-0">
                            <label for="per_page" class="mb-0 mr-2 text-nowrap">Show</label>
                            {!! html()->select('per_page', pageNumbers(), request('per_page', config('ADMIN_PER_PAGE')))->id('per_page')->class('custom-select custom-select-sm')->style('width: 80px')->attributes(['onchange'=>'$("#search").submit()']) !!}
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-xl-2 col-lg-3 col-md-4 col-sm-6">
                            <label for="orderId">Order ID</label>
                            {{ html()->text('orderId',request('orderId'))->id('orderId')->class('form-control form-control-sm')->placeholder('Enter order ID') }}
                        </div>
                        <div class="form-group col-xl-2 col-lg-3 col-md-4 col-sm-6">
                            <label for="orderStatus">Order Status</label>
                            {!! html()->select('orderStatus', $orderStatuses)->placeholder('All order statuses')->value(request('orderStatus'))->id('orderStatus')->class('custom-select custom-select-sm form-control form-control-sm') !!}
                        </div>
                        <div class="form-group col-xl-2 col-lg-3 col-md-4 col-sm-6">
                            <label for="shippingStatus">Shipping Status</label>
                            {!! html()->select('shippingStatus', $shippingStatuses)->placeholder('All shipping statuses')->value(request('shippingStatus'))->id('shippingStatus')->class('custom-select custom-select-sm form-control form-control-sm') !!}
                        </div>
                        <div class="form-group col-xl-2 col-lg-3 col-md-4 col-sm-6">
                            <label for="paymentStatus">Payment Status</label>
                            {!! html()->select('paymentStatus', paymentStatuses())->placeholder('All payment statuses')->value(request('paymentStatus'))->id('paymentStatus')->class('custom-select custom-select-sm form-control form-control-sm') !!}
                        </div>
                        <div class="form-group col-xl-2 col-lg-3 col-md-4 col-sm-6">
                            <label for="deliveryDate">Delivery Date</label>
                            {!! html()->date('deliveryDate')->value(request('deliveryDate'))->id('deliveryDate')->class('form-control form-control-sm') !!}
                        </div>
                        <div class="form-group col-xl-2 col-lg-3 col-md-4 col-sm-6">
                            <label for="workflowQueue">Work Queue</label>
                            {!! html()->select('workflowQueue', $workflowQueues)->placeholder('All queues')->value(request('workflowQueue'))->id('workflowQueue')->class('custom-select custom-select-sm form-control form-control-sm') !!}
                        </div>
                        <div class="form-group col-xl-2 col-lg-3 col-md-4 col-sm-6">
                            <label for="packingStatus">Packing Status</label>
                            {!! html()->select('packingStatus', $packingStatuses)->placeholder('All packing statuses')->value(request('packingStatus'))->id('packingStatus')->class('custom-select custom-select-sm form-control form-control-sm') !!}
                        </div>
                        <div class="form-group col-xl-2 col-lg-3 col-md-4 col-sm-6">
                            <label for="packingAdmin">Packing Person</label>
                            {!! html()->select('packingAdmin', $admins)->placeholder('Any packing person')->value(request('packingAdmin'))->id('packingAdmin')->class('custom-select custom-select-sm form-control form-control-sm') !!}
                        </div>
                        <div class="form-group col-xl-2 col-lg-3 col-md-4 col-sm-6">
                            <label for="deliveryAdmin">Delivery Person</label>
                            {!! html()->select('deliveryAdmin', $admins)->placeholder('Any delivery person')->value(request('deliveryAdmin'))->id('deliveryAdmin')->class('custom-select custom-select-sm form-control form-control-sm') !!}
                        </div>
                        <div class="form-group col-xl-2 col-lg-3 col-md-4 col-sm-6 d-flex align-items-end">
                            <div class="order-filter-actions w-100">
                                {!! html()->button('<i class="fas fa-search mr-1"></i> Search','submit')->class('btn btn-primary btn-sm') !!}
                                <a href="{{ route('admin.'.$module.'.index') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
                            </div>
                        </div>
                    </div>

                    <div class="border-top pt-3">
                        <label class="mb-2">Quick queues</label>
                        <div class="order-queue-actions">
                            <a href="{{ route('admin.'.$module.'.index') }}" class="btn btn-sm {{ request('workflowQueue') ? 'btn-outline-secondary' : 'btn-secondary' }}">All Orders</a>
                            @foreach($workflowQueues as $queueKey => $queueLabel)
                                <a href="{{ route('admin.'.$module.'.index', array_merge(request()->except('page'), ['workflowQueue' => $queueKey])) }}" class="btn btn-sm {{ request('workflowQueue') === $queueKey ? 'btn-primary' : 'btn-outline-primary' }}">{{ $queueLabel }}</a>
                            @endforeach
                        </div>
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
                                    <th>Packing</th>
                                    <th>Assigned Staff</th>
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
                                        @can('users_edit')
                                            <a href="{{ route('admin.users.edit', $row->user_id) }}" title="Edit customer {{ $row->user_id }}">{{ $row->user_id }}</a>
                                        @else
                                            {{ $row->user_id }}
                                        @endcan
                                        @endif
                                    </td>
                                    <td>{!! currency($row->amount) !!}</td>
                                    <td>{{ $row->order_status }}</td>
                                    <td>{{ $row->payment_status }}</td>
                                    <td>{{ $row->shipping_status }}</td>
                                    <td>{{ $packingStatuses[$row->packing_status ?? 'not_started'] ?? 'Not Started' }}</td>
                                    <td>
                                        <div><strong>Order:</strong> {{ $row->accepted_by_name ?: 'Unassigned' }}</div>
                                        <div><strong>Packing:</strong> {{ $row->packing_admin_name ?: 'Unassigned' }}</div>
                                        <div><strong>Delivery:</strong> {{ $row->delivery_admin_name ?: 'Unassigned' }}</div>
                                    </td>
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
