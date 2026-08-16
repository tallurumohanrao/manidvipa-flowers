<?php

namespace App\Cart;

use Illuminate\Support\Facades\Facade;

class CartDataFacade extends Facade{

    public static function getFacadeAccessor() {

        return 'cartdata';
    }
}
