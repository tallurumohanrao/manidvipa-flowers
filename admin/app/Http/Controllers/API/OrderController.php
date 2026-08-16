<?php
namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\API\BaseController as BaseController;

// use App\Order\CartData;
// use App\Traits\SmsTrait;

use Auth,Validator,DB,Str;
use App\Traits\GoogleDistanceTrait;

class OrderController extends BaseController
{
    use GoogleDistanceTrait;
    public function store(Request $request)
    {
        $user_id = null;
        if(auth('sanctum')->check()){
            $user = auth('sanctum')->user();
            $user_id = $user->id;
        }
        $address_id = $request->address_id;
        // if(empty($user_id)){
        //     $response1 = [ 'success' => false, 'redirect'=>true, 'message' => 'Please login to proceed cart.' ];
        //     return response()->json($response1, 422);
        // }
        if(empty($address_id)){
            return response()->json([ 'success' => false,'message' => 'Please fill delivery address.' ], 422);
        }
        #$carts = DB::table('carts')->where('user_id',$user_id)->get();
        $cart_session = $request->cart_session ?? null;
        $carts = DB::table('carts')->where(function($cwq) use($cart_session,$user_id){
            if($user_id){
                $cwq->where('user_id',$user_id);
            }
            if($cart_session){
                $cwq->orWhere('cart_session',$cart_session);
            }
        })->get();
        
        if($carts == null OR $carts->count() < 1){
            return response()->json(['success'=>false,'message'=>'Cart empty.'], 200);
        }
        
        $vat = config('VAT_AMOUNT');
        $vat_amount = null;
        
        // $user = DB::table('users')->select('id','name','email','phone','status')->where('id',$user_id)->first();
        // if($user == null OR empty($user)){
        //     return response()->json(['success'=>false,'message'=>'Customer does not existed with requested input.'], 422);
        // }
        $order_encrypt_key = Str::random(40);
        $create['order_encrypt_key'] = $order_encrypt_key;
        $create['user_id'] = $user_id;
        $create['name'] = $request->name ?? $user->name;
        $create['email'] = $request->email ?? $user->email;
        $create['contact_number'] = $request->contact_number ?? $user->contact_number;
        $create['serve_date'] = date('Y-m-d',strtotime($request->serve_date));
        $create['order_status_id'] = 1;
        $create['created_at'] = date('Y-m-d H:i:s');
        $orderId = DB::table('orders')->insertGetId($create);
        if(!$orderId){
            return response()->json(['success'=>false,'message'=>'Server error.'], 500);
        }
        $products = null;
        $subTotal = 0;
        foreach($carts as $value) :
            $product = DB::table('products')->where('id',$value->product_id)->first(); 
            if(!$product){
                continue;
            }
            $weight = DB::table('product_weights')->where(['product_id'=>$value->product_id,'id'=>$value->weight_id])->first(); 
            if(!$weight){
                continue;
            }            
            DB::table('order_products')->insert(['order_id'=>$orderId,'product_id'=>$product->id,'weight_id'=>$weight->id,'product_title'=>$product->title,'weight'=>$weight->name,'sku'=>$product->sku,'amount'=> $value->quantity * $weight->sell_price,'sell_price'=> $weight->sell_price,'list_price'=> $weight->list_price,'cost_price'=> $weight->cost_price,'quantity'=>$value->quantity,'created_at' => date('Y-m-d H:i:s')]);
            $subTotal += ( $weight->sell_price * $value->quantity );
        endforeach;
        
        $cart_coupon = DB::table('cart_line_items')->where(['cart_session'=>$cart_session,'value_name'=>'coupon'])->first(); 
        $coupon_discount = 0;
        if(@$cart_coupon){
            $coupon_row = DB::table('coupons')->where('id',$cart_coupon->value_id)->first(); 
            $discount_price = $coupon_row->discount;
            if ($coupon_row->is_percentage_discount) {
                $coupon_discount = ($subTotal * $discount_price) / 100;
            }else{
                $coupon_discount = $discount_price;
            }
            $coupon_discount = $coupon_discount * - 1;
        }
        
        //$shipping = DB::table('shipping_prices')->where('min_order_amount','<=',$subTotal)->where('max_order_amount','>=',$subTotal)->whereStatus(1)->first();
        $shipping = null;
        $distance = null;
        $shipping_amount = 0;
        if($request->filled('address_id')){
            $distance_response = $this->getDistance($address_id);
            if($distance_response['status'] =='success'){
                $distance = $distance_response['distance'];
                $shipping = DB::table('shipping_prices')->select('title','shipping_amount')->where('from_km','<=',$distance)->where('to_km','>=',$distance)->whereStatus(1)->first();
            }
        }
        if(@$shipping){
            $shipping_amount = $shipping->shipping_amount;
        }
        
        if(@$vat){
            $vat_amount = floor(($subTotal * $vat)/100);
        }
        $total = $subTotal + $shipping_amount + $coupon_discount + $vat_amount;
        DB::table('orders')->where('id',$orderId)->update(['sub_total'=>$subTotal,'amount'=>$total]);
        DB::table('order_lineitems')->insert(['order_id' => $orderId, 'title' => 'Sub Total', 'amount' => $subTotal, 'weight' => 1]);
        if(@$cart_coupon){
            DB::table('order_lineitems')->insert(['order_id' => $orderId, 'title' =>"Coupon (".$coupon_row->coupon_code.")", 'amount' => $coupon_discount, 'weight' => 2]);
        }
        if(@$shipping_amount){
            DB::table('order_lineitems')->insert(['order_id' => $orderId, 'title' => 'Delivery Charges', 'amount' => $shipping_amount, 'weight' => 3]);
        }
        if(@$vat){
            DB::table('order_lineitems')->insert(['order_id' => $orderId, 'title' => 'GST', 'amount' =>$vat_amount , 'weight' => 4]);
        }
        $serve_time_slot_label = $request->serve_time_slot_label ?: $request->serve_time_slot;
        if(!empty($serve_time_slot_label)){
            DB::table('order_lineitems')->insert(['order_id' => $orderId, 'title' => 'Delivery Time Slot: '.trim($serve_time_slot_label), 'amount' => 0, 'weight' => 5]);
        }
        DB::table('order_lineitems')->insert(['order_id' => $orderId, 'title' => 'Total', 'amount' => $total, 'weight' => 9]);
        $shippingStore['name'] = @$shipping?->title;
        $shippingStore['shipping_type'] =  null;
        $shippingStore['amount'] = $shipping_amount;
        $shippingStore['order_id'] = $orderId;
        $shippingStore['shipping_status_id'] = 1;
        $shippingStore['created_at'] = date('Y-m-d H:i:s');
        DB::table('order_shippings')->insert($shippingStore);

        $address = DB::table('addresses')->where(['user_id'=>$user_id,'id'=>$address_id])->first();
        if($address){
            $shipping_address['order_id'] = $orderId;
            $shipping_address['full_name'] = $address->full_name;
            $shipping_address['email'] = $address->email;
            $shipping_address['phone_number'] = $address->phone_number;
            $shipping_address['address_line1'] = $address->address_line1;
            $shipping_address['address_line2'] = $address->address_line2;
            $shipping_address['landmark'] = $address->landmark;
            $shipping_address['city'] = $address->city;
            $shipping_address['state'] = $address->state;
            $shipping_address['pincode'] = $address->pincode;
            $shipping_address['address_type'] = $address->address_type;
            $shipping_address['created_at'] = date('Y-m-d H:i:s');
            DB::table('order_shipping_addresses')->insert($shipping_address);
        }
        
        $this->clear($cart_session,$user_id);
        if($request->payment_method == 'cod'){
            DB::table('orders')->where('id',$orderId)->update(['order_status_id'=>2,'updated_at'=>date('Y-m-d H:i:s')]);
             $payInfo = [
                   'transaction_id' => null,
                   'order_id' => $orderId,
                   'payment_amount' => null,
                   'payment_method' => 'Cash On Delivery',
                   'payment_status' => 'Pending',
                   'created_at' => date('Y-m-d H:i:s')
                ];

            $result = DB::table('order_payments')->insert($payInfo);
        
            if($orderId){
                return response()->json(['success' => true,'data' => ['order_id'=>$orderId,'order_encrypt_key'=>$order_encrypt_key,'payment_method'=>$request->payment_method],'message'=>'Order placed successfully.'], 200);
            }else{
                return response()->json(['success' => false,'message'=>'Unable to place the order, please contact administrator.'], 422);
            }
        }else{
            return response()->json(['success' => true,'data' => ['order_id'=>$orderId,'order_encrypt_key'=>$order_encrypt_key,'payment_method'=>$request->payment_method],'message'=>'Payment pending...'], 200);
        }
    }
    
    public function updatePayment(Request $request){
        $validator = Validator::make($request->all(), [
            'order_id' => 'required'
        ]);
        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors());       
        }
        $orderId = $request->order_id;
        $user_id = $request->user_id;
        DB::table('orders')->where('id',$orderId)->update(['order_status_id'=>2,'admin_sms_sent'=>1,'updated_at'=>date('Y-m-d H:i:s')]);
             $payInfo = [
                   'transaction_id' => $request->transaction_id,
                   'order_id' => $orderId,
                   'payment_amount' => $request->payment_amount,
                   'payment_method' => $request->payment_method,
                   'payment_status' => $request->payment_status,
                   'created_at' => date('Y-m-d H:i:s')
                ];

        $paymentId = DB::table('order_payments')->insertGetId($payInfo);
        if($paymentId){
            return response()->json(['success' => true,'message'=>'Payment details updated successfully.'], 200);
        }else{
            return response()->json(['success' => false,'message'=>'Server error.'], 500);
        }
    }

    public function clear($cart_session,$user_id){
        if($cart_session OR $user_id){
            DB::table('carts')->where(function($cwq) use($cart_session,$user_id){
                if($user_id){
                    $cwq->where('user_id',$user_id);
                }
                if($cart_session){
                    $cwq->orWhere('cart_session',$cart_session);
                }
            })->delete();
        }
        DB::table('cart_line_items')->where('cart_session',$cart_session)->delete();
    }
    
    public function orderSummary($order_encrypt_key)
    {
        $user_id = null;
        if(auth('sanctum')->check()){
            $user = auth('sanctum')->user();
            $user_id = $user->id;
        }
        
        if(!$order_encrypt_key){
            return response()->json(['status'=>false,'message'=>'Page not found.'], 404);
        }
        
        $order = DB::table('orders as o')->select('o.id','o.order_encrypt_key','o.user_id','o.name','o.email','o.amount','o.order_status_id','o.serve_date','o.created_at','o.updated_at','o.sub_total','os.name as order_status_name')->leftJoin('order_statuses as os', 'os.id', '=', 'o.order_status_id')->where('o.order_encrypt_key',$order_encrypt_key)->first();
        if(!$order){
            return response()->json(['status'=>false,'message'=>'Page not found.'], 404);
        }
        
        $productsResults = DB::table('order_products as op')->select('op.product_id','op.product_title','op.sku','op.sell_price','op.amount','op.quantity','op.weight')->where('op.order_id',$order->id)->get();
        $products = null;
        foreach($productsResults as $productsResult) :
            $image = DB::table('product_images')->where('product_id',$productsResult->product_id)->where('status',1)->first();
            $image_name = null;
            if($image){
                $image_name = $image->name;
            }
            $products[] = ['product_id'=>$productsResult->product_id,'product_title'=>$productsResult->product_title,'sku'=>$productsResult->sku,'sell_price'=>$productsResult->sell_price,'amount'=>$productsResult->amount,'quantity'=>$productsResult->quantity,'weight'=>$productsResult->weight,'image_url'=> $image_name];
        endforeach;
        $payment = DB::table('order_payments')->where('order_id',$order->id)->first();
        $shipping = DB::table('order_shippings')->where('order_id',$order->id)->first();
        $shipping_address = DB::table('order_shipping_addresses')->where('order_id',$order->id)->first();
        $orderlineitems = DB::table('order_lineitems')->select('order_id','title','amount')->where('order_id',$order->id)->orderBy('weight')->get();
        return response()->json(['status'=>true,'order'=>$order,'products'=>$products,'payment'=>$payment,'shipping'=>$shipping,'shipping_address'=>$shipping_address,'orderlineitems'=>$orderlineitems], 200);
    }
}
