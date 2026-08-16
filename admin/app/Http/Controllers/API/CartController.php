<?php
namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\API\BaseController as BaseController;

use App\Http\Requests\StoreCartRequest;
use Auth,Validator,DB;
use App\Traits\GetCartTrait;
use App\Traits\GoogleDistanceTrait;

class CartController extends BaseController
{
    use GetCartTrait,GoogleDistanceTrait;
    public function getcart(Request $request){
        $user_id = null;
        $carts = [];
        if(auth('sanctum')->check()){
            $user = auth('sanctum')->user();
            $user_id = $user->id;
        }
        
        $cart_session = $request->cart_session ?? null;
        if($cart_session OR $user_id){
            $carts = DB::table('carts')->where(function($cwq) use($cart_session,$user_id){
                if($user_id){
                    $cwq->where('user_id',$user_id);
                }
                if($cart_session){
                    $cwq->orWhere('cart_session',$cart_session);
                }
            })->get();
        }
        
        
        $products = [];
        $subTotal = 0;
        $vat = config('VAT_AMOUNT');
        foreach($carts as $value) :
            $product = DB::table('products')->where('id',$value->product_id)->first(); 
            if(!$product){
                continue;
            }
            #$size = DB::table('sizes')->where('id',$value->size_id)->first(); 
            $weight = DB::table('product_weights')->where(['id'=>$value->weight_id,'product_id'=>$product->id])->first(); 
            if(!$weight){
                continue;
            }
            $image = null;
            $productImage = DB::table('product_images')->where('product_id',$value->product_id)->orderBy('priority')->first(); 
            if($productImage){ 
                $image = $productImage->name;
            }
            $products[] = ['cart_id'=>$value->id,'user_id'=>$value->user_id,'product_id'=>$product->id,'product_slug'=>$product->slug,'weight_id'=>$weight->id,'weight'=>$weight->name,'product_title'=>$product->title,'image'=>$image,'sell_price'=>$weight->sell_price,'list_price'=> $weight->list_price,'quantity'=>$value->quantity];
            $subTotal += ( $weight->sell_price * $value->quantity );
        endforeach;
        $coupon_row = null;
        $cart_coupon = DB::table('cart_line_items')->where(['cart_session'=>$cart_session,'value_name'=>'coupon'])->first(); 
        if($cart_coupon){
            $coupon_row = DB::table('coupons')->where('id',$cart_coupon->value_id)->first(); 
        }
        
        $shipping = null;
        $distance = null;
        if($request->filled('address_id')){
            $distance_response = $this->getDistance($request->address_id);
            if($distance_response['status'] =='success'){
                $distance = $distance_response['distance'];
                $shipping = DB::table('shipping_prices')->select('title','shipping_amount')->where('from_km','<=',$distance)->where('to_km','>=',$distance)->whereStatus(1)->first();
            }
        }
        
        $totals['sub_total'] = ['title' => 'Sub Total', 'amount' => $subTotal];

        if($coupon_row){
            $discount_price = $coupon_row->discount;
            if ($coupon_row->is_percentage_discount) {
                $coupon_discount = ($subTotal * $discount_price) / 100;
            }else{
                $coupon_discount = $discount_price;
            }
            $coupon_discount = $coupon_discount * - 1;
            $totals['coupon'] = ['title' => "Coupon (".$coupon_row->coupon_code.")", 'amount' => $coupon_discount];
        }

        if(@$vat){
            $totals['gst'] = ['title' => 'GST', 'amount' => floor(($subTotal * $vat)/100)];
        }

        if(@$shipping){
            $totals['shipping'] = ['title' => @$shipping->title, 'amount' => @$shipping->shipping_amount];
        }
        $totalAmount = 0;
        foreach($totals as $total){
            $totalAmount += $total['amount'];
        }
        $totals['total'] = ['title' => 'Total', 'amount' => $totalAmount];
        
        return response()->json([
            'success' => true,
            'data'=> $products,
            'cart_count' => count($products),
            'totals'=> $totals,
            'distance'=>$distance
        ], 200);
    }
    
    public function addtocart(Request $request)
    {
        $user_id = null;
        if(auth('sanctum')->check()){
            $user = auth('sanctum')->user();
            $user_id = $user->id;
        }
        
        $input = $request->all();
        $validator = Validator::make($input, [
            'product_id' => 'required',
            'quantity' => 'required',
            'weight_id' => 'required'
        ],['product_id.required'=>'Product is required.','quantity.required'=>'Quantity is required.','weight_id.required'=>'Weight is required.']);
   
        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors());     
        }
        $cart_session = $request->cart_session ?? null;
        
        $message = null;
        $request->request->add(['user_id' => $user_id,'cart_session'=>$cart_session]);

        $data = $request->all();
        if(empty($data['quantity'])){
            $data['quantity'] = 1;
        }
        
        if($data['product_id']){
            $weight = DB::table('product_weights')->where(['id'=>$data['weight_id'],'product_id'=>$data['product_id']])->first();
            if(empty($weight)){
                return response()->json(['success'=>false,'message'=>'Product not available.'],422);exit;
            }
            if($weight->stock && ($weight->qty < $data['quantity'])){
                $message = 'Out of stock. Available stock ('.$weight->qty .').';
                return response()->json(['success'=>false,'message'=>$message],200);exit;
            }
        }
        //'session'=>$cart_session
        $criteria = ['product_id'=>$request->product_id,'weight_id'=>$request->weight_id];
        $cart_row = DB::table('carts')->where(function($cwq) use($cart_session,$user_id){
                                if($user_id){
                                    $cwq->where('user_id',$user_id);
                                }
                                if($cart_session){
                                    $cwq->orWhere('cart_session',$cart_session);
                                }
                            })->where($criteria)->first();
        if($cart_row){
            if(DB::table('carts')->where('id',$cart_row->id)->update($data)){
                return response()->json(['success'=>true,'message'=>"Cart updated successfully."],200); 
            }else{
                return response()->json(['success'=>true,'message'=>"You haven't changed anything in the cart."],200); 
            }
        }else{
            if(DB::table('carts')->insertGetId($data)){
                return response()->json(['success'=>true,'message'=>"Product added to cart successfully."],200);
            }else{
                return response()->json(['success'=>false,'message'=>"Server error."],500); 
            }
        }
        #$cart = Cart::updateOrCreate(['product_id' => @$data['product_id'],'user_id'=>$user_id],$data);
        
    }
    
    public function update(Request $request){
        $errors = null;
        $user_id = null;
        if(auth('sanctum')->check()){
            $user = auth('sanctum')->user();
            $user_id = $user->id;
        }
        $cart_session = $request->cart_session ?? null;
        foreach($request->products as $product) :
            if($cart = DB::table('carts')->where('id',$product['cart_id'])->where(function($cwq) use($cart_session,$user_id){
                                if($user_id){
                                    $cwq->where('user_id',$user_id);
                                }
                                if($cart_session){
                                    $cwq->orWhere('cart_session',$cart_session);
                                }
                            })->first()){
                if($cart->product_id){
                    $weight = DB::table('product_weights')->where(['id'=>$cart->weight_id,'product_id'=>$cart->product_id])->first();
                    if($weight->stock && ($weight->qty < $product['quantity'])){
                        $product_title = $product['product_title'] ?? 'Product';
                        $errors[$cart->id][] = $product_title.' with weight '.$weight->name.' stock not available. Available stock is ('.$weight->qty .').';
                    }else{
                        if(@$product['quantity'] < 1){
                            DB::table('carts')->where('id',$cart->id)->delete();
                        }else{
                            DB::table('carts')->where('id',$cart->id)->update(['quantity'=>$product['quantity']]);
                        }
                    }
                }
            }
        endforeach;
        if($errors){
            $message = $errors;
        }else{
           $message = "Cart updated successfully.";
        }
        return response()->json(['success'=>true,'message'=>$message],200); 
    }
    
    public function updatebkp(Request $request)
    {
        $cart = DB::table('carts')->where(['id'=>$request->cart_id,'user_id'=>$request->user_id])->first();
        if(empty($cart)){
            $response = [
                'success' => false,
                'message' => 'Cart item not found.'
            ];
            return response()->json($response, 422);
        }
        if($cart->product_id){
            $weight = DB::table('weights')->where(['id'=>$cart->weight_id,'product_id'=>$cart->product_id])->first();
            if(empty($weight)){
                return response()->json(['success'=>false,'message'=>'Product weight is not available.'],422);exit;
            }
            if($weight->stock && ($weight->qty < $request->quantity)){
                $message = 'Stock not available. Available stock is ('.$weight->qty .').';
                return response()->json(['success'=>false,'message'=>$message]);exit;
            }
        }
        if(DB::table('carts')->where(['id'=>$request->cart_id])->update($request->only('user_id','quantity')) == true)
            return response()->json(['success'=>true,'message'=>"Cart updated successfully."]);
        else
            return response()->json(['success'=>false,'message'=>"No changes done to your cart."]);
    }
    
    public function destroy(Request $request)
    {
        $user_id = null;
        if(auth('sanctum')->check()){
            $user = auth('sanctum')->user();
            $user_id = $user->id;
        }
        
        $input = $request->all();
        $validator = Validator::make($input, [
            'cart_id' => 'required',
            'cart_session' => 'required'
        ]);
   
        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors());     
        }
        $cart_session = $request->cart_session ?? null;
        if($cart_session OR $user_id){
            $result = DB::table('carts')->where('id',$request->cart_id)->where(function($cwq) use($cart_session,$user_id){
                                if($user_id){
                                    $cwq->where('user_id',$user_id);
                                }
                                if($cart_session){
                                    $cwq->orWhere('cart_session',$cart_session);
                                }
                            })->delete();
            if($result){
                return response()->json(['success'=>true,'message'=>"Product deleted from cart."],200);
            }else{
                return response()->json(['success'=>false,'message'=>"Unable to delete the record."],200);
            }
        }else{
            return response()->json(['success'=>false,'message'=>"Server Error"],500);
        }
    }

    public function paymentmethods()
    {
        $data = DB::table('payment_methods')->select('id','name','title','is_default')->where('status',1)->get();
        return response()->json(['success'=>true,'data'=>$data],200);
    }
    public function addCoupon(Request $request)
    {
        $coupon_input = $request->coupon_code;
        if(empty($coupon_input)){
            return response()->json(['success'=>false,'message'=>'Please enter a coupon code.']);
        }
        
        $user_id = null;
        $status = 1;
        if(auth('sanctum')->check()){
            $user = auth('sanctum')->user();
            $user_id = $user->id;
        }
        $cart_session = $request->cart_session ?? null;
        if(empty($cart_session)){
            return response()->json(['success'=>false,'message'=>'Cart session missed.']);
        }
        $coupon = DB::table('coupons')->where('coupon_code',$coupon_input)->first();
        if(empty($coupon)){
            $status = 0;
            return response()->json(['status'=>false,'message'=>'Invalid coupon.']);
        }
    
            //user
        if($user_id){
            if((int)$coupon->usage_limit_per_user){
                // if(!$user){
                //     $status = 0;
                //     return response()->json(['success'=>false,'message'=>'Please login to avail coupon.']);
                // }
    
                $usagecount = DB::table('coupon_orders')->join("orders", "orders.id", "coupon_orders.order_id")->where("orders.user_id", $user->id)->where("coupon_orders.coupon_id", $coupon->id)->count();
                if($usagecount >= (int)$coupon->usage_limit_per_user){
                    $status = 0;
                    return response()->json(['success'=>false,'message'=>'Coupon Usage Limit Exceeded.']);
                }
            }
        }

        //order
        if($coupon->usage_limit){
            $count = DB::table('coupon_orders')->where('coupon_id',$coupon->id)->count();
            if($count >= $coupon->usage_limit){
                $status = 0;
                return response()->json(['success'=>false,'message'=>'Coupon Usage Limit Exceeded']);
            }
        }

        //min amount
        $subTotal = 0;
        if($coupon->minimum_purchage_amount){
            #$carts = DB::table('carts')->where('cart_session',$cart_session)->get();
            $carts = DB::table('carts')->where(function($cwq) use($cart_session,$user_id){
                if($user_id){
                    $cwq->where('user_id',$user_id);
                }
                if($cart_session){
                    $cwq->orWhere('cart_session',$cart_session);
                }
            })->get();
            foreach($carts as $value) :
                $product = DB::table('products')->where('id',$value->product_id)->first();
                $weight = DB::table('product_weights')->where(['id'=>$value->weight_id,'product_id'=>$product->id])->first(); 
                $subTotal += ( $weight->sell_price * $value->quantity );
            endforeach;
            
            if($subTotal < $coupon->minimum_purchage_amount ){
                $status = 0;
                $amount = currency($coupon->minimum_purchage_amount);
                return response()->json(['success'=>false,'message'=>"Order amount should be minimum $amount to avail coupon.",'Sub Total'=>$subTotal]);
            }
        }
        //start date
        if($coupon->start_date){
            if(strtotime($coupon->start_date) > strtotime(date("Y-m-d"))){
                $status = 0;
                return response()->json(['success'=>false,'message'=>'Coupon Expired.']);
            }
        }

        //end date
        if($coupon->end_date){
            if(strtotime($coupon->end_date) < strtotime(date("Y-m-d"))){
                $status = 0;
                return response()->json(['success'=>false,'message'=>'Coupon Expired.']);
            }
        }
        if($status){
            $result = DB::table('cart_line_items')->insert(['cart_session'=>$cart_session,'value_id'=>$coupon->id,'value_name'=>'coupon','value_title'=>$coupon->title]);
            #setParam('coupon', ['title' => $coupon->coupon_code, 'id' => $coupon->id, 'value' => $value]);
            if($result){
                return response()->json(['success'=>true,'message'=>'Coupon added successfully.']);
            }else{
                return response()->json(['success'=>false,'message'=>'Sorry!...Coupon not available.']);
            }
        }

        if(!$status){
            return response()->json(['success'=>false,'message'=>'Remove coupon from cart.']);
        }
        
    }
    public function getAddressById(Request $request)
    {
        $user_id = null;
        if(auth('sanctum')->check()){
            $user = auth('sanctum')->user();
            $user_id = $user->id;
        }
        $validator = Validator::make($request->all(), [
            'address_id' => 'required'
        ]);
        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors());       
        }
        $data = DB::table('addresses')->where(['id'=>$request->address_id,'user_id'=>$user_id])->first();
        return response()->json(['success'=>true,'data'=>$data],200);
    }
    public function editAddressById(Request $request)
    {
        $user_id = null;
        if(auth('sanctum')->check()){
            $user = auth('sanctum')->user();
            $user_id = $user->id;
        }
        $validator = Validator::make($request->all(), [
            'address_id' => 'required'
        ]);
        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors());       
        }
        if(DB::table('addresses')->where(['id'=>$request->address_id,'user_id'=>$user_id])->doesntExist()){
            return response()->json(['success'=>false,'message'=> 'Address not existed.'],200);
        }
        $update = $request->except('address_id');
        $update['updated_at'] = date('Y-m-d H:i:s');
        DB::table('addresses')->where(['id'=>$request->address_id,'user_id'=>$user_id])->update($update);
        return response()->json(['success'=>true,'data'=>['address_id'=>$request->address_id],'message'=>'Address updated successfully.'],200);
    }
    public function storeAddress(Request $request)
    {
        $user_id = null;
        if(auth('sanctum')->check()){
            $user = auth('sanctum')->user();
            $user_id = $user->id;
        }
        
        $validator = Validator::make($request->all(), [
            'full_name' => 'required',
            'email' => 'required|email',
            'phone_number' => 'required|size:10',
            'address_line1' => 'required',
            'address_line2' => 'required',
            'city' => 'required',
            'pincode' => 'required',
            'state' => 'required',
        ]);
   
        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors());       
        }
        $insert = $request->only('full_name', 'email', 'phone_number', 'address_line1', 'address_line2', 'landmark', 'city', 'state', 'pincode', 'address_type', 'is_default');
        $insert['user_id'] = $user_id;
        $insert['created_at'] = date('Y-m-d H:i:s');
        if($request->is_default == 1){
            if($user_id){
                DB::table('addresses')->where('user_id',$user_id)->update(['is_default' => NULL]);
            }
        }
        $address_id = DB::table('addresses')->insertGetId($insert);
        return response()->json(['success'=>true,'data'=>['address_id'=>$address_id],'message'=> 'Address stored successfully.'],200);
    }
    public function getCartCoupon(Request $request){
        $validator = Validator::make($request->all(), [
            'cart_session' => 'required',
        ]);
   
        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors());       
        }
        $user_id = null;
        $coupon_discount = null;
        if(auth('sanctum')->check()){
            $user = auth('sanctum')->user();
            $user_id = $user->id;
        }
        $coupon = DB::table('cart_line_items as cli')->select('cli.id','c.title','c.coupon_code','c.discount','c.is_percentage_discount')->where('cli.cart_session',$request->cart_session)->join('coupons as c','c.id','=','cli.value_id')->orderByDesc('cli.id')->first();
        
        if($coupon){
            $coupon_discount = $coupon->discount;
            if ($coupon->is_percentage_discount) {
                $subTotal = $this->getSubTotal($request->cart_session,$user_id);
                $coupon_discount = ($subTotal * $coupon->discount) / 100;
            }
            $data['cart_coupon_id'] = $coupon->id;
            $data['title'] = $coupon->title;
            $data['coupon_code'] = $coupon->coupon_code;
            $data['discount'] = $coupon_discount;
            return response()->json(['success'=>true,'data'=>$data],200); 
        }else{
            return response()->json(['success'=>false,'data'=>null,'message'=>'Coupon not found.'],200); 
        }
    }
    public function removeCoupon(Request $request){
        $validator = Validator::make($request->all(), [
            'cart_session' => 'required',
        ]);
   
        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors());       
        }
        DB::table('cart_line_items')->where('cart_session',$request->cart_session)->delete();
        return response()->json(['success'=>true,'message'=>'Coupon removed successfully.'],200); 
    }
    public function getCoupons(){
        $data = DB::table('coupons')->get();
        return response()->json(['success'=>true,'data'=>$data]);
    }
}
