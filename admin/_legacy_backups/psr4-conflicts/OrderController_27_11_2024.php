<?php
namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\API\BaseController as BaseController;

// use App\Order\CartData;
// use App\Traits\SmsTrait;

use Auth,Validator,DB;

class OrderController extends BaseController
{
    #use SmsTrait;
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
        $carts = DB::table('carts')->where('user_id',$user_id)->get();
        if($carts == null OR $carts->count() < 1){
            return response()->json(['success'=>false,'message'=>'Cart empty.'], 422);
        }
        $user = DB::table('users')->select('id','name','email','phone','status')->where('id',$user_id)->first();
        if($user == null OR empty($user)){
            return response()->json(['success'=>false,'message'=>'Customer does not existed with requested input.'], 422);
        }
        $create['user_id'] = $user_id;
        $create['name'] = $user->name;
        $create['email'] = $user->email;
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
            $weight = DB::table('product_weights')->where('product_id',$value->weight_id)->first(); 
            $products[] = [
                            'order_id'=>$orderId,
                            'product_id'=>$product->id,
                            'product_title'=>$product->title,
                            'weight'=>$weight->title,
                            'sku'=>$product->sku,
                            'amount'=> $value->quantity * $weight->sell_price,
                            'sell_price'=> $weight->sell_price,
                            'list_price'=> $weight->list_price,
                            'cost_price'=> $weight->cost_price,
                            'quantity'=>$value->quantity,
                            'created_at' => date('Y-m-d H:i:s')
                        ];
            DB::table('order_products')->insert($products);
            $subTotal += ( $weight->sell_price * $value->quantity );
        endforeach;
        
        $shipping = DB::table('shipping_prices')->where('min_order_amount','<=',$subTotal)->where('max_order_amount','>=',$subTotal)->whereStatus(1)->first();
        $shipping_amount = $shipping->shipping_amount ?? 0;
        $total = $subTotal + $shipping_amount;
        DB::table('orders')->where('id',$orderId)->update(['sub_total'=>$subTotal,'amount'=>$total]);

        $shippingStore['name'] = null;
        $shippingStore['shipping_type'] = null;
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
            DB::table('shipping_addresses')->insert($shipping_address);
        }
        $this->clear($user_id);
        if($request->payment_method == 'cod'){
            DB::table('orders')->where('id',$orderId)->update(['order_status_id'=>2,'admin_sms_sent'=>1,'updated_at'=>date('Y-m-d H:i:s')]);
             $payInfo = [
                   'transaction_id' => null,
                   'order_id' => $orderId,
                   'payment_amount' => null,
                   'payment_method' => 'Cash On Delivery',
                   'payment_status' => 'pending',
                   'created_at' => date('Y-m-d H:i:s')
                ];

            $result = DB::table('order_payments')->insert($payInfo);
        
            if($orderId){
                return response()->json(['success' => true,'data' => ['order_id'=>$orderId,'payment_method'=>$request->payment_method],'message'=>'Order placed successfully.'], 200);
            }else{
                return response()->json(['success' => false,'message'=>'Unable to place the order please contact administrator.'], 422);
            }
        }else{
            return response()->json(['success' => true,'data' => ['order_id'=>$orderId,'payment_method'=>$request->payment_method],'message'=>'Payment pending...'], 200);
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

    public function clear($user_id){
        DB::table('carts')->where('user_id',$user_id)->delete();
    }
}