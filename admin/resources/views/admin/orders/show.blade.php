@extends('admin.layouts.app')
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
@endphp
<section class="content">
	<div class="container-fluid">
		<h4 class="heading text-capitalize">Order ID #{{ $order->id }}</h4>
		<hr>
		<div class="card shadow mb-4">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-12">
                        <p>Order Status : <span id="BookingHtml">{{ $order->order_status }}</span>
                            <a href="{{ route('admin.'.$module.'.index') }}" class="btn btn-danger float-right">Cancel</a></p>
                        <button type="button" class="btn-primary" data-toggle="modal" data-target="#BookingModalCenter">Change</button></p>
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
                            <button type="button" class="btn-primary" data-toggle="modal" data-target="#PaymentModalCenter">Change</button>
                        </p>
                        <address>
                            {{--<strong>Payment Status: {{ $order->orderPayment->payment_status ?? '' }}</strong>--}}
                            <p class="text-capitalize">Payment Status: <span id="PaymentHtml">{{ $order->payment_status }}</span></p>

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
                            <button type="button" class="btn-primary" data-toggle="modal" data-target="#ShippingModalCenter">Change</button>
                        </p>
                        <address>
                            <strong>Shipping Status: <span id="ShippingHtml">{{ $order->shipping_status }}</span></strong><br />

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
            {!! html()->select('shipping_status',$shippingStatuses,$order->shipping_status)->class('form-control') !!}

          </div>
          <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
              <button type="submit" class="btn btn-primary">Save</button>
              {{ html()->form()->close() }}
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
</script>
@endpush
