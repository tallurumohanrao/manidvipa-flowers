<?php

namespace App\Cart;

use Auth,DB;

class CartData {

    public function getCart(){
        if(Auth::check()){
            return DB::table('carts')->selectRaw('carts.id as cart_id,carts.quantity,carts.frame,products.id,products.title,products.sku,products.slug,products.vat_enable,products.vat_price,product_sizes.id as size_id,product_sizes.name as size_name,product_sizes.sell_price as size_sell_price')->join('products', 'products.id', '=', 'carts.product_id')->join('product_sizes', 'product_sizes.id', '=', 'carts.size_id')->where('user_id',Auth::id())->get();
        }else{
            return DB::table('carts')->selectRaw('carts.id as cart_id,carts.quantity,carts.frame,products.id,products.title,products.sku,products.slug,products.vat_enable,products.vat_price,product_sizes.id as size_id,product_sizes.name as size_name,product_sizes.sell_price as size_sell_price')->join('products', 'products.id', '=', 'carts.product_id')->join('product_sizes', 'product_sizes.id', '=', 'carts.size_id')->where('carts.session',session('session_cart'))->get();
        }
    }

    // public function getCartItems(){
    //     $cart = static::getCart();
    //     foreach($cart as $item) :
    //         $products[] = $item->product;
    //     endforeach;
    //     return $products;
    // }

    public function getSubTotal(){
        $cart = static::getCart();
        $subTotal = 0;
        foreach($cart as $item) :
            $size = DB::table('product_sizes')->where(['id'=>$item->size_id,'product_id'=>$item->id])->first();
            if($size){
                $subTotal += ( $size->sell_price * $item->quantity );
            }
        endforeach;
        return $subTotal;
    }

    public function getCouponAmount(){
        if(hasParam('coupon')){
            $coupon = getParam('coupon');
            if($coupon){
                return $coupon['value'];
            }
        }
        return 0;
    }

    public function getTotal(){
        $total = 0;
        $subTotal = static::getSubTotal();
        $total = $subTotal + $this->getShippingAmount()+ $this->getVat() + $this->getCouponAmount();
        return $total;
    }

    public function getTotals(){
        $subTotal = static::getSubTotal();
        $coupon = $this->getCouponAmount();
        $vat = $this->getVat();
        $shipping = $this->getShippingAmount();

        $totals['subTotal'] = ['title' => 'Sub-Total', 'amount' => $subTotal];
        if($coupon){
            $couponAmount = $coupon['value'];
            $totals['coupon'] = ['id'=>$coupon['id'], 'title' => "Coupon: ".$coupon['title'], 'amount' => $couponAmount];
        }

        if($vat){
            $totals['vat'] = ['title' => $cartVat['title'], 'amount' => $cartVat['value']];
        }

        if($shippingMethod){
            $totals['shipping'] = ['id'=>$shippingMethod['id'], 'title' => $shippingMethod['title'], 'amount' => $shippingMethod['value']];
        }
    }

    public function getVat(){
        $cart = static::getCart();
        $vat = 0;
        foreach($cart as $product) :
            if($product->vat_enable == 1) :
                $price = $product->size_sell_price * $product->quantity;
                $vat += ($price * $product->vat_price)/100;
            endif;
        endforeach;
        return $vat;
    }

    public function getShippingAmount(){
        #$shipping = DB::table('shipping_prices')->where('min_order_amount','<=',$subTotal)->where('max_order_amount','>=',$subTotal)->whereStatus(1)->first();
        if(hasParam('shippingMethod')){
            $shippingMethod = getParam('shippingMethod');
            if($shippingMethod){
                return $shippingMethod['value'];
            }
        }
        return 0;
    }
}

