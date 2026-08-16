<?php
namespace App\Traits;

use Illuminate\Http\Request;
use Storage,Str;

trait GetCartTrait {
    public function getSubTotal($cart_session,$user_id) {
        if($cart_session OR $user_id){
            $carts = \DB::table('carts')->where(function($cwq) use($cart_session,$user_id){
                if($user_id){
                    $cwq->where('user_id',$user_id);
                }
                if($cart_session){
                    $cwq->orWhere('cart_session',$cart_session);
                }
            })->get();
        }
        $subTotal = 0;
        foreach($carts as $value) :
            $product = \DB::table('products')->where('id',$value->product_id)->first();
            $weight = \DB::table('product_weights')->where(['id'=>$value->weight_id,'product_id'=>$product->id])->first(); 
            $subTotal += ( $weight->sell_price * $value->quantity );
        endforeach;
        return $subTotal;
    }
}
