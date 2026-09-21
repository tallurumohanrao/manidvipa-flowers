<?php
namespace App\Traits;

use App\Support\SellingOption;

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
        foreach(($carts ?? []) as $value) :
            $product = \DB::table('products')->where('id',$value->product_id)->where('status', 1)->first();
            if(! $product || ! \App\Support\PriceVisibility::productCanPurchase($product)){
                continue;
            }
            $weight = \DB::table('product_weights')->where(['id'=>$value->weight_id,'product_id'=>$product->id])->where('status', 1)->first();
            if(! $weight){
                continue;
            }
            $subTotal += SellingOption::price($weight, 'sell', $value->custom_quantity ?? null) * $value->quantity;
        endforeach;
        return $subTotal;
    }
}
