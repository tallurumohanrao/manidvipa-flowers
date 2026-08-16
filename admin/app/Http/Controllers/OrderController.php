<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\StoreOrderRequest;
use App\Cart\CartData;
use Auth,DB,Str,Mail;

class OrderController extends Controller
{
    public function store(StoreOrderRequest $request,CartData $cartData){
        $user = Auth::user();
        $created_at = date('Y-m-d H:i:s');
        $cart = $cartData->getCart();
        $totals = $cartData->getTotals();
        $cartBillingAddress = getParam('billingAddress');
        unset($cartBillingAddress['id']);
        unset($cartBillingAddress['user_id']);
		$cartShippingAddress = getParam('shippingAddress');
        unset($cartShippingAddress['id']);
        unset($cartShippingAddress['user_id']);

        $order_encrypt_key = Str::random(40);
        $create['order_encrypt_key'] = $order_encrypt_key;
        $create['name'] = $cartBillingAddress['full_name'];
        $create['email'] = $cartBillingAddress['email'];
        if(isset($totals['gst'])){
            $create['gst'] = $totals['gst']['amount'];
        }
        $create['amount'] = $totals['total']['amount'];
        $create['sub_total'] = $totals['subtotal']['amount'];
        $create['user_id'] = $user->id ?? null;
        $create['order_status_id'] = 1;
        $create['created_at'] = $created_at;
        $orderId = DB::table('orders')->insertGetId($create);

        $order_payment['payment_method'] = $request->paymentMethod == 'cod' ? 'Cash On Delivery' : null;
        $order_payment['payment_amount'] = null;
        $order_payment['payment_status'] = 'Pending';
        $order_payment['order_id'] = $orderId;
        $order_payment['created_at'] = $created_at;
        DB::table('order_payments')->insert($order_payment);

        foreach($cart as $product) :
            $orderProduct['product_id'] = $product->id;
            $orderProduct['order_id'] = $orderId;
            $orderProduct['product_title'] = $product->title . ' - '.$product->size_name .', '.$product->frame;
            $orderProduct['sell_price'] = $product->size_sell_price;
            $orderProduct['amount'] = $product->quantity * $product->size_sell_price;
            $orderProduct['quantity'] = $product->quantity;
            $orderProduct['sku'] = $product->sku;
            $orderProduct['created_at'] = $created_at;
            DB::table('order_products')->insert($orderProduct);
        endforeach;

        $k = 0;
        foreach($totals as $row){
			DB::table('order_lineitems')->insert(['order_id' => $orderId, 'title' => $row['title'], 'amount' => $row['amount'], 'weight' => $k+1]);
            $k++;
		}

        if(isset($totals['shipping'])){
            $cartShipping = $totals['shipping'];
            $shipping['shipping_type'] = $cartShipping['title'];
            $shipping['amount'] = $cartShipping['amount'];
            $shipping['order_id'] = $orderId;
            $shipping['shipping_status_id'] = 1;
            $shipping['created_at'] = $created_at;
            DB::table('order_shippings')->insert($shipping);
        }

        if(isset($cartBillingAddress)){
            $cartBillingAddress['order_id'] = $orderId;
            $cartBillingAddress['created_at'] = $created_at;
            DB::table('order_billing_addresses')->insert($cartBillingAddress);
        }
        if($request->same_as_billing == 1){
            $cartShippingAddress = $cartBillingAddress;
        }else{
            $cartShippingAddress['order_id'] = $orderId;
            $cartShippingAddress['created_at'] = $created_at;
        }
        if(isset($cartShippingAddress)){
            DB::table('order_shipping_addresses')->insert($cartShippingAddress);
        }

        if(hasParam('orderNotes')){
            $orderNotes['order_id'] = $orderId;
            $orderNotes['user_id'] = $user->id ?? null;
            $orderNotes['comment'] = getParam('orderNotes');
            $orderNotes['created_at'] = $created_at;
            DB::table('order_comments')->insert($orderNotes);
        }

        if(isset($totals['coupon'])){
            $cartCoupon = $totals['coupon'];
            $coupon_insert['amount'] = $cartCoupon['amount'];
            $coupon_insert['coupon_id'] = $cartCoupon['id'];
            $coupon_insert['order_id'] = $orderId;
            $coupon_insert['created_at'] = $created_at;
            DB::table('coupon_orders')->insert($coupon_insert);
        }
        session(['order_id' => $orderId]);
        $this->clear();

        if($request->paymentMethod == 'cod'){
            DB::table('orders')->where('id',$orderId)->update(['order_status_id'=>4]);
            return response()->json(['status'=>'success','redirect'=>route('orders.success',['order_encrypt_key'=>$order_encrypt_key])]);
        }else{
            return response()->json(['status'=>'success','redirect'=>route('razorpay.create.payment')]);
        }
    }

    protected function clear(){
        $userId = Auth::id();
        DB::table('carts')->where(function($q) use($userId){
            $q->where('session',session('session_cart'));
            if($userId){
                $q->orWhere('user_id',$userId);
            }
        })->delete();
        session()->forget(['cart','session_cart']);
    }

    public function success($key){
        #$order = DB::table('orders')->where('order_encrypt_key',$key)->first();
        // $query = DB::table('orders')->selectRaw('orders.*,order_statuses.name as order_status,order_payments.payment_status,order_payments.payment_method,order_payments.transaction_id,order_payments.payment_amount,order_payments.updated_at as order_payment_updated_at,shipping_statuses.name as shipping_status,order_shippings.amount as shipping_amount,order_shippings.tracking_no as shipping_tracking_no,order_shippings.tracking_link as order_tracking_link,order_shippings.shipping_type,order_shippings.updated_at as order_shipping_updated_at');
        // $query->join('order_statuses', 'orders.order_status_id', '=', 'order_statuses.id');
        // $query->join('order_payments', 'order_payments.order_id', '=', 'orders.id');
        // $query->join('order_shippings', 'order_shippings.order_id', '=', 'orders.id');
        // $query->join('shipping_statuses', 'order_shippings.shipping_status_id', '=', 'shipping_statuses.id');
        // $order = $query->where('order_encrypt_key',$key)->first();

        // $products = DB::table('order_products')->selectRaw('order_products.*,products.id as order_product_id')->leftJoin('products', 'order_products.product_id', '=', 'products.id')->where('order_id',$order->id)->get();
        $query = DB::table('orders')->selectRaw('orders.*,order_statuses.name as order_status,order_payments.payment_status,order_payments.payment_method,order_payments.transaction_id,order_payments.payment_amount,order_payments.created_at as order_payment_created_at,shipping_statuses.name as shipping_status,order_shippings.amount as shipping_amount,order_shippings.tracking_no as shipping_tracking_no,order_shippings.tracking_link as order_tracking_link,order_shippings.shipping_type,order_shippings.updated_at as order_shipping_updated_at');
        $query->join('order_statuses', 'orders.order_status_id', '=', 'order_statuses.id');
        $query->join('order_payments', 'order_payments.order_id', '=', 'orders.id');
        $query->join('order_shippings', 'order_shippings.order_id', '=', 'orders.id');
        $query->join('shipping_statuses', 'order_shippings.shipping_status_id', '=', 'shipping_statuses.id');
        $order = $query->where('orders.order_encrypt_key',$key)->first();

        $products = DB::table('order_products')->selectRaw('order_products.*,products.id as order_product_id')->leftJoin('products', 'order_products.product_id', '=', 'products.id')->where('order_id',$order->id)->get();
        $orderlineitems = DB::table('order_lineitems')->where('order_id',$order->id)->orderBy('weight')->get();
        $billingaddress = DB::table('order_billing_addresses')->where('order_id',$order->id)->first();
        $shippingaddress = DB::table('order_shipping_addresses')->where('order_id',$order->id)->first();
        if (app()->environment(['production'])) {
            $customerBodyHtml = (string)view('emails.orders.summary',compact('order','products','billingaddress','shippingaddress'));
            Mail::html($customerBodyHtml, function($message) use($order){
                $message->to($order->email, $order->name)->subject('Thank you for your order '.config('SITE_NAME'));
                $message->from(config('SITE_EMAIL'), config('SITE_NAME'));
            });

            Mail::html($customerBodyHtml, function($message) use($order){
                $message->to(config('SITE_EMAIL'), config('SITE_NAME'))->subject(config('SITE_NAME').' New order #'.$order->id);
                $message->from($order->email, $order->name);
            });
        }
        return view('orders.success',compact('order','products','orderlineitems'));
    }
}
