@extends('admin.layouts.app')
@push('styles')
<style>
    .order-status-text {
        font-weight: 700;
    }

    .order-text-warning {
        color: #b36b00;
    }

    .order-text-primary {
        color: #2f55d4;
    }

    .order-text-success {
        color: #16864f;
    }

    .order-text-danger {
        color: #d52b1e;
    }

    .order-text-muted {
        color: #6c757d;
    }
</style>
@endpush
@section('content')
@php
$billing = null;
$shipping = null;
if($billingaddress){
    $b_address[] = $billingaddress->address_line1;
    $b_address[] = $billingaddress->address_line2;
    $b_address[] = $billingaddress->landmark;
    $b_address[] = $billingaddress->city;
    $b_address[] = $billingaddress->state;
    $b_address[] = $billingaddress->country;
    $billing = implode(', ',array_filter($b_address)).'.';
}
if($shippingaddress){
    $s_address[] = $shippingaddress->address_line1;
    $s_address[] = $shippingaddress->address_line2;
    $s_address[] = $shippingaddress->landmark;
    $s_address[] = $shippingaddress->city;
    $s_address[] = $shippingaddress->state;
    $s_address[] = $shippingaddress->country;
    $shipping = implode(', ',array_filter($s_address)).'.';
}
$statusTextClass = function ($status) {
    $value = strtolower(trim((string) ($status ?: 'pending')));

    if(preg_match('/cancel|fail|reject|return|refund/', $value)){
        return 'order-text-danger';
    }

    if(preg_match('/complete|paid|delivered|success/', $value)){
        return 'order-text-success';
    }

    if(preg_match('/accepted|process|dispatch|out for delivery|shipped|packed|ready/', $value)){
        return 'order-text-primary';
    }

    if(preg_match('/pending|checkout|not started|confirm/', $value)){
        return 'order-text-warning';
    }

    return 'order-text-muted';
};
@endphp

<section class="content">
    <div class="container-fluid">
        <h4 class="heading text-capitalize">Order ID #{{ $order->id }}</h4>
        <hr>
        <div class="card shadow mb-4">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-12">
                        <p>Order Status : <span id="BookingHtml" class="order-status-text {{ $statusTextClass($order->order_status) }}">{{ $order->order_status ?: 'Pending' }}</span>
                            <a href="{{ route('admin.'.$module.'.index') }}" class="btn btn-danger float-right">Back to Orders</a></p>
                        @can($module.'_edit')
                        <button type="button" class="btn-primary" data-toggle="modal" data-target="#BookingModalCenter">Change</button>
                        <button type="button" class="btn btn-success btn-sm ml-2" data-toggle="modal" data-target="#WorkflowModalCenter">Manage Workflow</button>
                        @endcan
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-md-3 col-sm-6 mb-3">
                        <div class="card border-left-primary h-100">
                            <div class="card-body py-3">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Order Manager</div>
                                <div class="h6 mb-1">{{ $order->accepted_by_name ?: 'Unassigned' }}</div>
                                <small class="text-muted">
                                    @if($order->accepted_at)
                                        Accepted {{ date(config('app.datetime'),strtotime($order->accepted_at)) }}
                                    @else
                                        Accepts and verifies the order.
                                    @endif
                                </small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-3">
                        <div class="card border-left-warning h-100">
                            <div class="card-body py-3">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Packing Person</div>
                                <div class="h6 mb-1">{{ $order->packing_admin_name ?: 'Unassigned' }}</div>
                                <small class="text-muted">Packing: <span class="order-status-text {{ $statusTextClass($packingStatuses[$order->packing_status ?? 'not_started'] ?? 'Not Started') }}">{{ $packingStatuses[$order->packing_status ?? 'not_started'] ?? 'Not Started' }}</span></small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-3">
                        <div class="card border-left-info h-100">
                            <div class="card-body py-3">
                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Delivery Person</div>
                                <div class="h6 mb-1">{{ $order->delivery_admin_name ?: 'Unassigned' }}</div>
                                <small class="text-muted">Delivery: <span class="order-status-text {{ $statusTextClass($order->shipping_status ?: 'Pending') }}">{{ $order->shipping_status ?: 'Pending' }}</span></small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-3">
                        <div class="card border-left-success h-100">
                            <div class="card-body py-3">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Current Flow</div>
                                <div class="small">
                                    <div class="mb-1">Order: <span class="order-status-text {{ $statusTextClass($order->order_status) }}">{{ $order->order_status ?: 'Pending' }}</span></div>
                                    <div class="mb-1">Packing: <span class="order-status-text {{ $statusTextClass($packingStatuses[$order->packing_status ?? 'not_started'] ?? 'Not Started') }}">{{ $packingStatuses[$order->packing_status ?? 'not_started'] ?? 'Not Started' }}</span></div>
                                    <div>Shipping: <span class="order-status-text {{ $statusTextClass($order->shipping_status ?: 'Pending') }}">{{ $order->shipping_status ?: 'Pending' }}</span></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-3 col-sm-6">
                        <p><strong class="muted">Customer Information </strong></p>
                        <address>
                            <strong>{{ $order->name }}</strong> <br />
                            {{ $order->email }}
                        </address>
                        <p>
                            <strong class="muted">Order Date</strong>
                        </p>
                        <address>
                            <strong>
                                @if($order->created_at)
                                {{ date(config('app.datetime'),strtotime($order->created_at)) }}
                                @else
                                NILL
                                @endif
                            </strong>
                        </address>
                        <p>
                            <strong class="muted">Last Updated Date</strong>
                        </p>
                        <address>
                            <strong>
                                @if($order->updated_at)
                                {{ date(config('app.datetime'),strtotime($order->updated_at)) }}
                                @else
                                NILL
                                @endif
                            </strong>
                        </address>
                        <p>
                            <strong class="muted">Delivery Information</strong>
                            @can($module.'_edit')
                            <button type="button" class="btn-primary" data-toggle="modal" data-target="#DeliveryPreferenceModalCenter">Change</button>
                            @endcan
                        </p>
                        <address>
                            Delivery Date:
                            <strong id="DeliveryDateHtml" class="order-status-text {{ $order->serve_date ? 'order-text-success' : 'order-text-muted' }}">
                                {{ $order->serve_date ? date('d/m/Y',strtotime($order->serve_date)) : 'NILL' }}
                            </strong><br />
                            Time Slot:
                            <strong id="DeliverySlotHtml" class="order-status-text {{ $deliveryTimeSlotLabel ? 'order-text-primary' : 'order-text-muted' }}">
                                {{ $deliveryTimeSlotLabel ?: 'To be confirmed' }}
                            </strong>
                        </address>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        @if($billing)
                        <p><strong class="muted">Billing Address</strong></p>
                        <address>
                            <strong>{{ $billingaddress->full_name ?? '' }}</strong><br />
                            <p>{!! $billing !!}</p>
                        </address>
                        @endif
                        @if($shipping)
                        <p><strong class="muted">Shipping Address</strong></p>
                        <address>
                            <strong>{{ $shippingaddress->full_name ?? '' }}</strong><br />
                            <p>{!! $shipping !!}</p>
                        </address>
                        @endif
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <p>
                            <strong class="muted">Payment Information</strong>
                            @can($module.'_edit')
                            <button type="button" class="btn-primary" data-toggle="modal" data-target="#PaymentModalCenter">Change</button>
                            @endcan
                        </p>
                        <address>
                            {{--<strong>Payment Status: {{ $order->orderPayment->payment_status ?? '' }}</strong>--}}
                            <p class="text-capitalize">Payment Status: <span id="PaymentHtml" class="order-status-text {{ $statusTextClass($order->payment_status ?: 'Pending') }}">{{ $order->payment_status ?: 'Pending' }}</span></p>

                            Payment Method: <span class="text-capitalize">{{ $order->payment_method }}</span><br />

                            ReferenceNo: {{ $order->transaction_id ?? '' }}<br />
                            Amount Paid: {!! currency($order->payment_amount ?? null) !!}<br />
                            Last Updated Date:
                            @if($order->order_payment_updated_at)
                            {{ date(config('app.datetime'),strtotime($order->order_payment_updated_at)) }}
                            @else NILL @endif
                        </address>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <p>
                            <strong class="muted">Shipping Information</strong>
                            @can($module.'_edit')
                            <button type="button" class="btn-primary" data-toggle="modal" data-target="#ShippingModalCenter">Change</button>
                            @endcan
                        </p>
                        <address>
                            <strong>Shipping Status: <span id="ShippingHtml" class="order-status-text {{ $statusTextClass($order->shipping_status ?: 'Pending') }}">{{ $order->shipping_status ?: 'Pending' }}</span></strong><br />

                            Shipping Type: {{ $order->shipping_type }}<br />
                            @if($order->shipping_tracking_no)
                            Tracking No.: {{ $order->shipping_tracking_no }}<br />
                            @endif
                            @if($order->order_tracking_link)
                            Tracking Link.: {{ $order->order_tracking_link }}<br />
                            @endif
                            Amount Paid: {!! currency($order->shipping_amount) !!}<br />
                            Last Updated Date:
                            @if($order->order_shipping_updated_at)
                            {{ date(config('app.datetime'),strtotime($order->order_shipping_updated_at)) }}
                            @else NILL @endif
                        </address>
                    </div>

                </div>
                <div class="row">
                    <div class="col-sm-12">
                    <div class="table-responsive">
                        <table class="table table-bordered  table-hover tablegrid">
                            <thead>
                                <tr role="row">
                                    <th>S.No.</th>
                                    <th>Product ID</th>
                                    <th>Product Title</th>
                                    <th>SKU</th>
                                    <th>Quantity</th>
                                    <th>Price</th>
                                    <th class="text-right">Total</th>
                                </tr>
                            </thead>

                            <tbody>
                            @foreach($products as $product)
                                <tr id="row-{{ $product->id }}">
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $product->id }}</td>
                                    <td>
                                        @if($product->order_product_id)
                                        <a target="_blank" href="{{ route('admin.products.edit',['product'=>$product->product_id]) }}">{{ $product->product_title }}</a>
                                        @else
                                        {{ $product->product_title }}
                                        @endif
                                    </td>
                                    <td>{{ $product->sku }}</td>
                                    <td>{{ $product->quantity }}</td>
                                    <td>{!! currency($product->sell_price) !!}</td>
                                    <td class="text-right">{!! currency($product->amount) !!}</td>
                                </tr>
                            @endforeach
                            @foreach($orderlineitems as $orderlineitem)
                            <tr>
                                @if(in_array($orderlineitem->title,['Sub Total','Total']))
                                <td colspan="6" class="text-right border-right-0"><b>{!! $orderlineitem->title !!} : </b></td>
                                <td class="text-right border-left-0"><b>{!! currency($orderlineitem->amount) !!}</b></td>
                                @elseif(stripos($orderlineitem->title,'Delivery Time Slot:') === 0)
                                <td colspan="6" class="text-right border-right-0" id="DeliveryLineItemTitle">{!! $orderlineitem->title !!} : </td>
                                <td class="text-right border-left-0">{!! currency($orderlineitem->amount) !!}</td>
                                @else
                                <td colspan="6" class="text-right border-right-0">{!! $orderlineitem->title !!} : </td>
                                <td class="text-right border-left-0">{!! currency($orderlineitem->amount) !!}</td>
                                @endif
                            </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    </div>
                </div>

                <div class="row">
                     <div class="col-sm-12">
                     <div class="table-responsive">
                         <table class="table table-bordered table-hover tablegrid">
                            <thead>
                                <tr role="row">
                                    <th colspan="6">Order Workflow Timeline</th>
                                </tr>
                                <tr role="row">
                                    <th>S.No.</th>
                                    <th>Activity</th>
                                    <th>From</th>
                                    <th>To / Note</th>
                                    <th>By</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                            @forelse($workflowEvents as $workflowEvent)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ ucwords(str_replace('_',' ', $workflowEvent->event_type)) }}</td>
                                    <td>{{ $workflowEvent->from_value ?: '-' }}</td>
                                    <td>
                                        {{ $workflowEvent->to_value ?: '-' }}
                                        @if($workflowEvent->note)
                                            <div class="text-muted small">{{ $workflowEvent->note }}</div>
                                        @endif
                                    </td>
                                    <td>{{ $workflowEvent->admin_name ?: 'System' }}</td>
                                    <td>{{ $workflowEvent->created_at ? date(config('app.datetime'),strtotime($workflowEvent->created_at)) : 'NILL' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted">No workflow activity recorded yet.</td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                    </div>
                </div>

                <div class="row">
                     <div class="col-sm-12">
                     <div class="table-responsive">
                         <table class="table table-bordered table-hover tablegrid">
                            <thead>
                                <tr role="row">
                                    <th colspan="3">Order Comments</th>
                                </tr>
                                <tr role="row">
                                    <th>S.No.</th>
                                    <th>Comment</th>
                                    <th>By</th>
                                </tr>
                            </thead>
                            <tbody>
                            @foreach($ordercomments as $ordercomment)
                                <tr id="row-{{ $ordercomment->id }}">
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $ordercomment->comment }}</td>
                                    <td>{{ $ordercomment->user_id == null ? 'Admin':'Customer' }}</td>
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



<div class="modal fade" id="BookingModalCenter" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="exampleModalLongTitle">Booking Status</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
            {{ html()->model($order)->form('PATCH')->route('admin.'.$module.'.updateBooking', ['id'=>$order->id])->id('orderForm')->open() }}
            <label class="col-form-label" for="order_status">Order Status</label>
            {!! html()->select('order_status',$orderStatuses,$order->order_status_id)->class('form-control') !!}
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            <button type="submit" class="btn btn-primary">Save</button>
            {{ html()->form()->close() }}
        </div>
      </div>
    </div>
  </div>

  <div class="modal fade" id="PaymentModalCenter" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="exampleModalLongTitle">Payment Status</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
            {{ html()->model($order)->form('PATCH')->route('admin.'.$module.'.updatePayment', ['id'=>$order->id])->id('PaymentForm')->open() }}
            <label class="col-form-label" for="payment_status">Payment Status</label>
            {!! html()->select('payment_status',paymentstatuses(),$order->payment_status)->class('form-control') !!}
            <label class="col-form-label" for="payment_amount">Payment Amount</label>
            {!! html()->number('payment_amount',$order->payment_amount)->class('form-control')->attributes(['step'=>'any']) !!}
          </div>
          <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
              <button type="submit" class="btn btn-primary">Save</button>
              {{ html()->form()->close() }}
          </div>
      </div>
    </div>
  </div>

  <div class="modal fade" id="ShippingModalCenter" tabindex="-1" role="dialog" aria-labelledby="shippingModalCenterTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Shipping Status</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        </div>
        <div class="modal-body">
            {{ html()->model($order)->form('PATCH')->route('admin.'.$module.'.updateShipping', ['id'=>$order->id])->id('shippingForm')->open() }}
            <label class="col-form-label" for="shipping_status">Shipping Status</label>
            {!! html()->select('shipping_status',$shippingStatuses,$order->shipping_status_id)->class('form-control') !!}

          </div>
          <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
              <button type="submit" class="btn btn-primary">Save</button>
              {{ html()->form()->close() }}
          </div>
      </div>
    </div>
  </div>

  <div class="modal fade" id="WorkflowModalCenter" tabindex="-1" role="dialog" aria-labelledby="workflowModalCenterTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Manage Order Workflow</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        </div>
        <div class="modal-body">
          <form action="{{ route('admin.'.$module.'.updateWorkflow', ['id'=>$order->id]) }}" method="POST" id="WorkflowForm">
            @csrf
            @method('PATCH')
            <div class="row">
              <div class="col-md-6">
                <label class="col-form-label" for="workflow_order_status">Order Status</label>
                {!! html()->select('order_status',$orderStatuses,$order->order_status_id)->id('workflow_order_status')->class('form-control') !!}
                <small class="text-muted">Use this after order checking: Pending → Accepted → Processing → Completed.</small>
              </div>
              <div class="col-md-6">
                <label class="col-form-label" for="accepted_by_admin_id">Order Manager</label>
                {!! html()->select('accepted_by_admin_id',$admins,$order->accepted_by_admin_id)->placeholder('Unassigned')->id('accepted_by_admin_id')->class('form-control') !!}
                <small class="text-muted">Person responsible for checking customer, payment, stock, and delivery date.</small>
              </div>
              <div class="col-md-6">
                <label class="col-form-label" for="packing_admin_id">Packing Person</label>
                {!! html()->select('packing_admin_id',$admins,$order->packing_admin_id)->placeholder('Unassigned')->id('packing_admin_id')->class('form-control') !!}
              </div>
              <div class="col-md-6">
                <label class="col-form-label" for="packing_status">Packing Status</label>
                {!! html()->select('packing_status',$packingStatuses,$order->packing_status ?? 'not_started')->id('packing_status')->class('form-control') !!}
              </div>
              <div class="col-md-6">
                <label class="col-form-label" for="delivery_admin_id">Delivery Person</label>
                {!! html()->select('delivery_admin_id',$admins,$order->delivery_admin_id)->placeholder('Unassigned')->id('delivery_admin_id')->class('form-control') !!}
              </div>
              <div class="col-md-6">
                <label class="col-form-label" for="workflow_shipping_status">Delivery / Shipping Status</label>
                {!! html()->select('shipping_status',$shippingStatuses,$order->shipping_status_id)->placeholder('Pending')->id('workflow_shipping_status')->class('form-control') !!}
                <small class="text-muted">Use Dispatched, Out for Delivery, and Delivered during delivery.</small>
              </div>
              <div class="col-md-12">
                <label class="col-form-label" for="workflow_note">Internal Note</label>
                <textarea name="workflow_note" id="workflow_note" rows="3" class="form-control" placeholder="Example: Packed by Ramesh and handed to delivery staff at 4 PM."></textarea>
              </div>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
          <button type="submit" form="WorkflowForm" class="btn btn-primary">Save Workflow</button>
        </div>
      </div>
    </div>
  </div>

  <div class="modal fade" id="DeliveryPreferenceModalCenter" tabindex="-1" role="dialog" aria-labelledby="deliveryPreferenceModalCenterTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Delivery Date & Time Slot</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        </div>
        <div class="modal-body">
          <form action="{{ route('admin.'.$module.'.updateDeliveryPreference', ['id'=>$order->id]) }}" method="POST" id="DeliveryPreferenceForm">
            @csrf
            @method('PATCH')
            <label class="col-form-label" for="serve_date">Delivery Date</label>
            <input type="date" name="serve_date" id="serve_date" class="form-control" value="{{ $order->serve_date ? date('Y-m-d',strtotime($order->serve_date)) : date('Y-m-d') }}">

            <label class="col-form-label" for="serve_time_slot">Delivery Time Slot</label>
            <input type="text" name="serve_time_slot" id="serve_time_slot" class="form-control" list="deliveryTimeSlotOptions" value="{{ $deliveryTimeSlotLabel ?: '6 AM - 9 AM' }}" placeholder="Example: 6 AM - 9 AM">
            <datalist id="deliveryTimeSlotOptions">
              <option value="6 AM - 9 AM">
              <option value="9 AM - 12 PM">
              <option value="12 PM - 3 PM">
              <option value="3 PM - 6 PM">
              <option value="After 6 PM">
              <option value="Early morning - confirm on call">
            </datalist>
            <small class="text-muted">You can select a suggestion or type a custom slot.</small>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
          <button type="submit" form="DeliveryPreferenceForm" class="btn btn-primary">Save</button>
        </div>
      </div>
    </div>
  </div>
@stop


@push('script')
<script>
$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
});

$("#orderForm").submit(function () {
    event.preventDefault();
    $.ajax({
        url: $(this).attr('action'),
        method: "POST",
        data: new FormData(this),
        cache:false,
        contentType: false,
        processData: false,
        success: function (response) {
            if(response.success == true){
                $("#BookingModalCenter").modal('hide');
                Message.add(response.message, {type: 'success'});
                $('#BookingHtml').html(response.html);
                location.reload(true);
            }else{
                Message.add(response.message, {type: 'error'});
            }
        }
    });
});

$("#shippingForm").submit(function () {
    event.preventDefault();
    $.ajax({
        url: $(this).attr('action'),
        method: "POST",
        data: new FormData(this),
        cache:false,
        contentType: false,
        processData: false,
        success: function (response) {
            if(response.success == true){
                $("#ShippingModalCenter").modal('hide');
                $('#ShippingHtml').html(response.html);
                Message.add(response.message, {type: 'success'});
                location.reload(true);
            }
        }
    });
});

$("#PaymentForm").submit(function () {
    event.preventDefault();
    $.ajax({
        url: $(this).attr('action'),
        method: "POST",
        data: new FormData(this),
        cache:false,
        contentType: false,
        processData: false,
        success: function (response) {
            if(response.success == true){
                $("#PaymentModalCenter").modal('hide');
                Message.add(response.message, {type: 'success'});
                $('#PaymentHtml').html(response.html);
                location.reload(true);
            }
        }
    });
});

$("#WorkflowForm").submit(function (event) {
    event.preventDefault();
    $.ajax({
        url: $(this).attr('action'),
        method: "POST",
        data: new FormData(this),
        cache:false,
        contentType: false,
        processData: false,
        success: function (response) {
            if(response.success == true){
                $("#WorkflowModalCenter").modal('hide');
                Message.add(response.message, {type: 'success'});
                location.reload(true);
            }else{
                Message.add(response.message, {type: 'error'});
            }
        },
        error: function (response) {
            let message = 'Unable to update order workflow.';
            if(response.responseJSON && response.responseJSON.message){
                message = response.responseJSON.message;
            }
            Message.add(message, {type: 'error'});
        }
    });
});

$("#DeliveryPreferenceForm").submit(function (event) {
    event.preventDefault();
    $.ajax({
        url: $(this).attr('action'),
        method: "POST",
        data: new FormData(this),
        cache:false,
        contentType: false,
        processData: false,
        success: function (response) {
            if(response.success == true){
                $("#DeliveryPreferenceModalCenter").modal('hide');
                $('#DeliveryDateHtml').html(response.date_html);
                $('#DeliverySlotHtml').html(response.slot_html);
                if($('#DeliveryLineItemTitle').length){
                    $('#DeliveryLineItemTitle').html(response.lineitem_html + ' : ');
                }
                Message.add(response.message, {type: 'success'});
                location.reload(true);
            }else{
                Message.add(response.message, {type: 'error'});
            }
        },
        error: function (response) {
            let message = 'Unable to update delivery date and time slot.';
            if(response.responseJSON && response.responseJSON.message){
                message = response.responseJSON.message;
            }
            Message.add(message, {type: 'error'});
        }
    });
});
</script>
@endpush
