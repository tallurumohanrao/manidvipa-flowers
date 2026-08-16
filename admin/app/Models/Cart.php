<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use DB;

class Cart extends Model
{
    use HasFactory;

    protected $fillable = ['theater_name', 'city_name', 'decoration_name', 'cake_name', 'theater_id', 'decoration_id', 'cake_id', 'slot_id', 'theater_price', 'decoration_price', 'cake_price', 'booking_date', 'slot_timings', 'no_of_persons', 'food', 'name_one', 'name_two'];

    public function getTotalAmountAttribute(){
        $cart = DB::table("carts")->where('id',session('cart_id'))->first();
        $addonSum = DB::table("cart_addons")->where('cart_id',session('cart_id'))->select(DB::raw("SUM(cart_addons.price) as total_price"))->first();
        $theater = DB::table("theaters")->where('id',$cart->theater_id)->first();
        $gp = 0;
        if($cart->no_of_persons > $theater->price1_max_people){
            $gp = $cart->no_of_persons - $theater->price1_max_people;
        }
        $basePrice = $this->theater_price + $this->decoration_price + $this->cake_price + $addonSum->total_price;
        if($gp){
            return $basePrice + ($gp * $theater->price2);
        }else{
            return $basePrice;
        }
    }

}
