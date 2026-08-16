<style>
.table{
    width:100%;
    border: 2px solid #e2e2e2;
    border-radius:5px;
    padding:30px 30px;
    margin-bottom:10px;
}
.text-center{
    text-align:center;
}
.tbody{
    background:#ececec;
  
}
.tab-heading{
         text-align: center;
    padding: 6px;
    background: #0f4387;
    color: #fff;
    border-radius: 5px;
} 
td{
    text-align:center;
}
@media print{
    .printbtn{
        display:none;
    }
}
</style>
<p class="printbtn text-center"><button onclick="javascript:print()">Print</button></p>
<table class="table table-bordered  table-hover tablegrid">
    <tbody class="tbody">
        <tr>
            <th colspan="2" class="tab-heading">Booking Details</th>
        </tr>
        <tr>
            <th>Booking Status</th>
            <td class="text-capitalize"><span id="BookingHtml">@if($booking->booking_status_id) {{ bookingStatuses()[$booking->booking_status_id] }} @endif</span></br></td>
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
<table class="table table-bordered  table-hover tablegrid">
    <tbody class="tbody">
        <tr>
            <th colspan="2" class="tab-heading" >Customer Details</th>
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
<table class="table table-bordered  table-hover tablegrid">
    <tbody class="tbody">
        <tr>
            <th colspan="2" class="tab-heading">Payment Details</th>
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

<table class="table table-bordered  table-hover tablegrid">
<thead>
<tr role="row" class="tab-heading">
    <th >S.No.</th>
    <th>Product Name</th>
    <th>Price</th>
</tr>
</thead>
<tbody class="tbody">
<tr>
<td>1</td>
<td>
    <b>{{ $booking->decoration_name }}</b>
    <span style="font-size: 14px">(Decoration)</span>
</td>
<td>{{ IND_money_format($booking->decoration_price) }}</td>
</tr>
<tr>
<td>2</td>
<td>
    <b>{{ $booking->cake_name }}</b>
    <span style="font-size: 14px">(Cake)</span>
</td>
<td>{{ IND_money_format($booking->cake_price) }}</td>
</tr>
@foreach($addons as $product)
<tr id="row-{{ $product->id }}">
    <td>{{ $loop->iteration + 2 }}</td>
    <td><b>{{ $product->addon_name }}</b><span style="font-size: 14px">(Addon)</span></td>
    <td>{{ IND_money_format($product->price) }}</td>
</tr>
@endforeach
</tbody>
</table>