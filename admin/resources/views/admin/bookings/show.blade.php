@extends('admin.layouts.app')
@section('content')
<section class="content">
	<div class="container-fluid">
		<ul class="list-inline mb-3 text-right">
            <li class="list-inline-item float-left">
                <h4 class="heading text-capitalize">Booking ID #{{ $booking->id }}</h4>
            </li>
            <li class="list-inline-item">
                <div class="btn-group">
                    <a href="{{ route('admin.'.$module.'.index',['booking_type'=>request('booking_type')]) }}" class="btn btn-danger">Cancel</a>
				</div>
			</li>
		</ul>
		<hr>
		<div class="card shadow mb-4">
            <div class="card-body" style="overflow-x: scroll;">
                <div class="row">
                    <div class="card-columns">

                        <div class="card">
                            <div class="card-body" >
                                <div class="table-responsive">
                                    <table class="table table-bordered  table-hover tablegrid">
                                        <tbody>
                                            <tr>
                                                <th colspan="2" style="text-align:center">Booking Details</th>
                                            </tr>
                                            <tr>
                                                <th>Booking Status</th>
                                                <td class="text-capitalize">
                                                    <span id="BookingHtml">@if($booking->booking_status_id) {{ bookingStatuses()[$booking->booking_status_id] }} @endif</span></br>
                                                    <button type="button" class="btn-primary" data-toggle="modal" data-target="#BookingModalCenter">Change</button>
                                                </td>
                                            </tr>
                                            <tr>
                                                <th>Total Amount</th>
                                                <td>{{ IND_money_format($booking->amount) }}</td>
                                            </tr>
                                            <tr>
                                                <th>Theater Name</th>
                                                <td>{{ $booking->theater_name }}</td>
                                            </tr>
                                            <tr>
                                                <th>Theater Amount</th>
                                                <td>{{ IND_money_format($booking->theater_price) }}</td>
                                            </tr>
                                            <tr>
                                                <th>Booking Date</th>
                                                <td>{{ $booking->booking_date }}</td>
                                            </tr>
                                            <tr>
                                                <th>Slot Timings</th>
                                                <td>{{ $booking->slot_timings }}</td>
                                            </tr>
                                            <tr>
                                                <th>No. of Persons</th>
                                                <td>{{ $booking->no_of_persons }}</td>
                                            </tr>
                                            <tr>
                                                <th>Opt-in for Food</th>
                                                <td>{{ $booking->food == 1 ? 'Yes' : 'No' }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered  table-hover tablegrid">
                                        <tbody>
                                            <tr>
                                                <th colspan="2" style="text-align:center">Customer Details</th>
                                            </tr>
                                            <tr>
                                                <th>Name</th>
                                                <td>{{ $booking->name }}</td>
                                            </tr>
                                                <th>Email</th>
                                                <td>{{ $booking->email }}</td>
                                            </tr>
                                                <th>Whatsapp Number</th>
                                                <td>{{ $booking->whatsapp_number }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>


                        <div class="card">
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered  table-hover tablegrid">
                                        <tbody>
                                            <tr>
                                                <th colspan="2" style="text-align:center">Payment Details</th>
                                            </tr>
                                            <tr>
                                                <th>Payment Status</th>
                                                <td class="text-capitalize">{{ $booking->payment_status }}</td>
                                            </tr>
                                            <tr>
                                                <th>Transaction No.</th>
                                                <td>{{ $booking->transaction_id }}</td>
                                            </tr>
                                            <tr>
                                                <th>Payment Amount</th>
                                                <td>{{ IND_money_format($booking->payment_amount) }}</td>
                                            </tr>
                                            <tr>
                                                <th>Payment Method</th>
                                                <td class="text-capitalize">{{ $booking->payment_method }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                    <div class="row">
                        <div class="col-md-12">
                        <div class="table-responsive">
                            <table class="table table-bordered  table-hover tablegrid">
                                <thead>
                                    <tr role="row">
                                        <th>S.No.</th>
                                        <th>Product Name</th>
                                        <th>Price</th>
                                    </tr>
                                </thead>
                                <tbody>
                                @php $pre = 0; @endphp
                                @if($booking->decoration_name)
                                @php $pre = 1; @endphp
                                <tr>
                                    <td>1</td>
                                    <td>
                                        <b>{{ $booking->decoration_name }}</b>
                                        <span style="font-size: 14px">(Decoration)</span><br>
                                        <b>{{ $booking->name_one_label }} : </b><span style="font-size: 14px">({{ $booking->name_one }})</span><br>
                                        <b>{{ $booking->name_two_label }} : </b><span style="font-size: 14px">({{ $booking->name_two }})</span>
                                    </td>
                                    <td>{{ IND_money_format($booking->decoration_price) }}</td>
                                </tr>
                                @endif
                                @if($booking->cake_name)
                                @php $pre = 2; @endphp
                                <tr>
                                    <td>2</td>
                                    <td>
                                        <b>{{ $booking->cake_name }}</b>
                                        <span style="font-size: 14px">(Cake)</span>
                                    </td>
                                    <td>{{ IND_money_format($booking->cake_price) }}</td>
                                </tr>
                                @endif
                                @foreach($addons as $product)
                                    <tr id="row-{{ $product->id }}">
                                        <td>{{ $loop->iteration + $pre }}</td>
                                        <td><b>{{ $product->addon_name }}</b><span style="font-size: 14px">(Addon)</span></td>
                                        <td>{{ IND_money_format($product->price) }}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
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
                {{ html()->model($booking)->form('PATCH')->route('admin.'.$module.'.updateBooking', $booking)->class('')->id('BookingForm')->open() }}
                
                <label for="booking_status_id" class="col-form-label">Booking Status</label>
                {!! html()->select('booking_status_id',bookingStatuses(),$booking->booking_status_id)->placeholder('-- Booking Status --')->class('form-control') !!}

          </div>
          <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
              {!! html()->button('Save','submit')->name('FormButton')->value('Save')->class('btn btn-primary') !!}
                {{ html()->form()->close() }}
          </div>
      </div>
    </div>
  </div>
@stop

@section('script')
<script>
$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
});

$("#BookingForm").submit(function () {
    var booking_status_id = $("#booking_status_id").val();
    if(booking_status_id == ''){
        alert('Please select booking status');
    }
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
            }
        }
    });
});

$(".ShippingForm").submit(function () {
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
                //$("#exampleModalCenter").modal('hide');
                Message.add(response.message, {type: 'success'});
                //$('#ShippingHtml').html(response.html);
                location.reload();
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
            }
        }
    });
});
</script>
@stop
