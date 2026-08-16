<table border="0" style="width:100%">
    <tbody>
        <tr>
            <td>
                <table border="0" style="border-bottom:2px solid gray;width:100%">
                    <tbody>
                        <tr>
                            <td style="width:50%"><img src="{{ asset('storage/website/sri-sri-parinaya1708327550.jpg') }}" alt="{{ config('SITE_NAME') }}"></td>
                            <td style="width:50%">
                                <table align="right" style="font-size:1.09em;width:100%">
                                    <tbody>
                                        <tr>
                                            <td>
                                                <table>
                                                    <tbody>
                                                        <tr>
                                                            <td style="font-weight:bold">Order No. <span
                                                                    style="font-weight:normal;color:gray"> #{{ $order->id }}</span>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td style="font-weight:bold">Order Date: <span style="font-weight:normal;color:gray">
                                                                @if($order->created_at) 
                                                                {!! date('d/m/Y',strtotime($order->created_at)) !!}
                                                                @else NILL @endif
                                                                </span>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td style="font-weight:bold">Order Status: <span
                                                                    style="font-weight:normal;color:gray">{{ $order->order_status }}</span>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td style="font-weight:bold">Customer Email: <span style="font-weight:normal;color:gray"><a href="mailto:{{ $order->email }}" target="_blank">{{ $order->email }}</a></span>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            {{-- <td style="font-weight:bold">Contact Number: <span style="font-weight:normal;color:gray">{{ $order->contact_number }}</span> --}}
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </td>
                        </tr>
                    </tbody>
                </table>


                <table border="0" style="width:100%">
                    <tbody>
                        <tr>
                            <td width="50%">
                                <table border="0" style="border:1px solid #eeeeee;padding:10px;width:100%">
                                    <tbody>
                                        <tr>
                                            <td align="left" width="400"><b>From</b></td>
                                        </tr>
                                        <tr style="font-size:1em;color:#747474">
                                            <td>{!! config('SITE_NAME') !!}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </td>
                            <td width="50%">
                                <table border="0" style="border:1px solid #eeeeee;padding:10px;width:100%">
                                    <thead align="left" style="font-size:1.1em;font-weight:bold">
                                        <tr>
                                            <th width="400">Billing</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr style="font-size:1em;color:#747474">
                                            <td>{!! getOrderAddress($billingaddress) !!}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </td>
                        </tr>
                        <tr>
                            <td width="50%">
                                <table border="0" style="border:1px solid #eeeeee;padding:10px;width:100%">
                                    <thead align="left" style="font-size:1.1em;font-weight:bold">
                                        <tr>
                                            <th width="400">Payment</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr style="font-size:1em;color:#747474">
                                            <td>Payment Type: {{ $order->payment_method }}<br>
                                                Payment Ref: {{ $order->transaction_id }}<br>
                                                Payment Status: {{ $order->payment_status }}<br>
                                                Payment Amount: {!! currency($order->payment_amount / 100) !!}<br>
                                                Payment Date: 
                                                @if($order->order_payment_created_at) 
                                                {!! date('d/m/Y h:i A',strtotime($order->order_payment_created_at)) !!}
                                                @else NILL @endif
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </td>
                            <td width="50%">
                                <table border="0" style="border:1px solid #eeeeee;padding:10px;width:100%">
                                    <thead align="left" style="font-size:1.1em;font-weight:bold">
                                        <tr>
                                            <th width="400">Delivery Details</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr style="font-size:1em;color:#747474">
                                            <td>{!! getOrderAddress($shippingaddress) !!}<br>
                                                Delivery Date: 
                                                @if($order->serve_date) 
                                                {!! date('d/m/Y',strtotime($order->serve_date)) !!}. 
                                                @else NILL @endif
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <table border="0" cellspacing="0" cellpadding="0" style="width:100%">
                    <thead>
                        <tr style="color:white;font-weight:400;text-transform:uppercase">
                            {{-- <th
                                style="background:#ae834a;font-size:90%;color:#ffffff;padding:5px 10px;border-right:4px solid #ae834a;text-align:left">
                                Image</th> --}}
                            <th
                                style="background:#ae834a;font-size:90%;color:#ffffff;padding:5px 10px;border-right:4px solid #ae834a;text-align:left">
                                Product Info</th>
                            <th
                                style="background:#ae834a;font-size:90%;color:#ffffff;padding:5px 10px;border-right:4px solid #ae834a;text-align:center">
                                Product Code</th>
                            <th
                                style="background:#ae834a;font-size:90%;color:#ffffff;padding:5px 10px;border-right:4px solid #ae834a;text-align:center">
                                Qty</th>
                            <th
                                style="background:#ae834a;font-size:90%;color:#ffffff;padding:5px 10px;border-right:4px solid #ae834a;text-align:right">
                                Unit Price</th>
                            <th
                                style="background:#ae834a;font-size:90%;color:#ffffff;padding:5px 10px;border-right:4px solid #ae834a;text-align:right">
                                Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($products as $product)
                        <tr>
                            {{-- <td
                                style="font-size:100%;color:#000000;padding:5px 10px;border-right:4px solid #f4f4f4;border-bottom:4px solid #f4f4f4">
                                <img src="{{ asset('storage/products/100X100/'. @$product->name ) }}">
                            </td> --}}
                            <td
                                style="font-size:100%;color:#000000;padding:5px 10px;border-right:4px solid #f4f4f4;border-bottom:4px solid #f4f4f4">
                                <h3 style="font-size:100%;color:#000;margin:0px">{{ $product->product_title }}</h3>

                                <ul style="list-style:none;margin:0px;padding:0px 0px 0px 10px">
                                    <li></li>
                                    <li></li>
                                </ul>
                            </td>
                            <td
                                style="font-size:100%;color:#000000;padding:5px 10px;border-right:4px solid #f4f4f4;border-bottom:4px solid #f4f4f4;text-align:center">{{ $product->sku }}</td>
                            <td
                                style="font-size:100%;color:#000000;padding:5px 10px;border-right:4px solid #f4f4f4;border-bottom:4px solid #f4f4f4;text-align:center">{{ $product->quantity }}</td>
                            <td
                                style="font-size:100%;color:#000000;padding:5px 10px;border-right:4px solid #f4f4f4;border-bottom:4px solid #f4f4f4;text-align:right">
                                {!! currency($product->sell_price) !!}</td>
                            <td
                                style="font-size:100%;color:#000000;padding:5px 10px;border-right:4px solid #f4f4f4;border-bottom:4px solid #f4f4f4;text-align:right">
                                {!! currency($product->amount) !!}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>

                <table style="width:100%;background:#f4f4f4;padding:10px 0">


                    <tbody>

                        <tr>
                            <td style="width:40%;text-align:right"></td>
                            <td style="width:60%;text-align:right">
                                <table style="width:100%;padding:0">
                                    <tbody>
                                        <tr style="width:100%">
                                            <td>Sub-Total :</td>
                                            <td>{!! currency($order->sub_total) !!}</td>
                                        </tr>
                                        <tr style="width:100%">
                                            <td>TAX :</td>
                                            <td>{!! currency($order->gst) !!}</td>
                                        </tr>
                                        <tr style="width:100%">
                                            <td>Shipping Amount @if($order->shipping_type) ({{ $order->shipping_type }}) @endif : </td>
                                            <td>{!! currency($order->shipping_amount) !!}</td>
                                        </tr>
                                        <tr style="width:100%">
                                            <td><b>Total</b>:</td>
                                            <td><b>{!! currency($order->amount) !!}</b></td>
                                        </tr>

                                    </tbody>
                                </table>
                            </td>
                        </tr>

                    </tbody>
                </table>


                <table border="0" style="font-size:1em;margin-top:5px;padding:10px;">
                    <tbody>
                        <tr>
                            <td><span style="font-size:12pt;background-color:#ffffff">Thank you for shopping at
                                {{ config('SITE_NAME') }}. If you have any questions regarding your order or would like to
                                    <span style="background-color:#ffff00"><strong>cancel your order, please contact us
                                            on {{ config('SITE_PHONE') }} or email us at <a href="mailto:{{ config('SITE_EMAIL') }}" target="_blank">{{ config('SITE_EMAIL') }}</a>.</strong></span></span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </td>
        </tr>
    </tbody>
</table>
