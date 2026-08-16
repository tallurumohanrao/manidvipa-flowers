<?php
namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\API\BaseController as BaseController;

use App\Models\Product;
use App\Models\Size;
use App\Models\Cart;
use App\Models\Coupon;
use App\Order\CartData;


use App\Http\Requests\StoreCartRequest;
use Auth,Validator,DB;

class CartController extends BaseController
{
    public function getcart(Request $request){
        $user_id = $request->user_id;
        
        if(empty($user_id)){
            $response1 = [
                'success' => false,
                'redirect'=>true,
                'message' => 'Please login to proceed cart.'
            ];
            return response()->json($response1, 422);
        }
        
        // $session_cart = session('session_cart');
        // $carts = DB::table('carts')->where(function($cwq) use($session_cart,$user_id){
        //     $cwq->where('user_id',$user_id);
        //     $cwq->orWhere('session',$session_cart);
        // })->get();
        
        $carts = DB::table('carts')->where('user_id',$user_id)->get();
        $products = null;
        $subTotal = 0;
        foreach($carts as $value) :
            $product = DB::table('products')->where('id',$value->product_id)->first(); 
            #$size = DB::table('sizes')->where('id',$value->size_id)->first(); 
            $weight = DB::table('product_weights')->where('id',$value->weight_id)->first(); 
            $image = null;
            $productImage = DB::table('product_images')->where('product_id',$value->product_id)->orderBy('priority')->first(); 
            if($productImage){ 
                $image = $productImage->name;
            }
            $products[] = ['cart_id'=>$value->id,'user_id'=>$value->user_id,'product_id'=>$product->id,'weight'=>$weight->name,'product_title'=>$product->title,'image'=>$image,'sell_price'=>currency($weight->sell_price),'list_price'=>currency($weight->list_price),'quantity'=>$value->quantity];
            $subTotal += ( $weight->sell_price * $value->quantity );
        endforeach;
        
        $shipping = DB::table('shipping_prices')->where('min_order_amount','<=',$subTotal)->where('max_order_amount','>=',$subTotal)->whereStatus(1)->first();
        $shipping_amount = $shipping->shipping_amount ?? 0;
        
        $total = $subTotal + $shipping_amount;
        $response = [
                'success' => true,
                'data'=> $products,
                'shipping_charges'=> currency($shipping_amount),
                'subTotal'=> currency($subTotal),
                'total'=> currency($total)
            ];
        return response()->json($response, 200);
    }
    
    public function addtocart(Request $request)
    {
        $user_id = null;
        if(auth('sanctum')->check()){
            $user = auth('sanctum')->user();
            $user_id = $user->id;
        } else {
             return response()->json(['success' => false,'message' => 'Please login to access myaccount.'], 200);
        }
        
        $input = $request->all();
   
        $validator = Validator::make($input, [
            'product_id' => 'required',
            'quantity' => 'required',
            'weight_id' => 'required'
        ]);
   
        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors());     
        }

        if(session('session_cart') == null){
            session()->put('session_cart',uniqid());
        }
        $session_cart = session('session_cart');
        // $user_id = $request->user_id;
        // if(empty($user_id)){
        //     $response = [
        //         'success' => false,
        //         'redirect'=>true,
        //         'message' => 'Please login to proceed cart.'
        //     ];
        //     return response()->json($response, 422);
        // }
        
        $message = null;
        
        $request->request->add(['user_id' => $user_id ]);

        $data = $request->all();
        if(empty($data['quantity'])){
            $data['quantity'] = 1;
        }
        
        if($data['product_id']){
            // $size = DB::table('sizes')->where(['id'=>$data['size_id'],'product_id'=>$data['product_id']])->first();
            // if(empty($size)){
            //     return response()->json(['success'=>false,'message'=>'Product size is not available.'],422);exit;
            // }
            // if($size->stock && ($size->qty < $data['quantity'])){
            //     $message = 'Requested quantity exceeded the available quantity ('.$size->qty .').';
            //     return response()->json(['success'=>false,'message'=>$message],422);exit;
            // }
            $weight = DB::table('product_weights')->where(['id'=>$data['weight_id'],'product_id'=>$data['product_id']])->first();
            if(empty($weight)){
                return response()->json(['success'=>false,'message'=>'Product not available.'],422);exit;
            }
            if($weight->stock && ($weight->qty < $data['quantity'])){
                $message = 'Out of stock. Available stock ('.$weight->qty .').';
                return response()->json(['success'=>false,'message'=>$message],422);exit;
            }
        }
        
        $criteria = ['session'=>$session_cart,'product_id'=>$request->product_id,'weight_id'=>$request->weight_id];
        if(DB::table('carts')->where($criteria)->exists()){
            if(DB::table('carts')->where($criteria)->update($data)){
               return response()->json(['success'=>true,'message'=>"Cart updated successfully."],200); 
            }else{
                return response()->json(['success'=>false,'message'=>"No modifications applied."],304); 
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
    
    public function update(Request $request)
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
        $input = $request->all();
   
        $validator = Validator::make($input, [
            'user_id' => 'required',
            'cart_id' => 'required'
        ]);
   
        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors());     
        }
        
        if(DB::table('carts')->where(['id'=>$request->cart_id,'user_id'=>$request->user_id])->delete() == true)
            return response()->json(['success'=>true,'message'=>"Product deleted from cart."]);
        else
            return response()->json(['success'=>false,'message'=>"Unable to delete the record."]);
    }

    public function paymentmethods()
    {
        $data = DB::table('payment_methods')->where('status',1)->get();
        return response()->json(['success'=>true,'data'=>$data],200);
    }
}