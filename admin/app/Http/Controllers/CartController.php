<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Cart\CartData;
use App\Http\Requests\StoreCartRequest;
use Auth,DB;

class CartController extends Controller
{
    public function __construct(){
        #$this->middleware('auth');
    }
    
    public function miniCart(CartData $cartData){
        return $cartData->getCart()->count();
        //return view('cart.mini');
    }
    public function index(CartData $cartData){
        if($cartData->getCart()->count()<=0){
            return redirect()->route('home')->with('warning','Your cart is currently empty.');
        }
        return view('cart.index');
    }
    public function products(){
        return view('cart.products');
    }

    public function store(StoreCartRequest $request)
    {
        $id = Auth::id();
        $session_cart = session('session_cart');
        $request->request->add(['user_id' => $id ]);
        $data = $request->except('_token');
        $data['session'] = $session_cart;
        if(empty($data['quantity'])){
            $data['quantity'] = 1;
        }
        $where = ['session'=>$session_cart,'product_id'=>$request->product_id,'size_id'=>$request->size_id];
        if(DB::table('carts')->where($where)->exists()){
            $data['updated_at'] = date('Y-m-d H:i:s');
            $id = DB::table('carts')->where($where)->update(['quantity'=> DB::raw('quantity +'.$data['quantity'])]);
        }else{
            $data['created_at'] = date('Y-m-d H:i:s');
            $id = DB::table('carts')->insertGetId($data);
        }
        if($id){
            return response()->json(['success'=>true,'message'=>"Product added to cart successfully."]);
        }else{
            return response()->json(['success'=>false,'message'=>"An unexpected error has occurred."]);
        }
    }

    public function cartbulkupdate(Request $request)
    {
        $qtys = $request->qty;
        foreach($qtys as $cartId => $qty){
            DB::table('carts')->where('id',$cartId)->update(['quantity'=>$qty]);
        }
        return response()->json(['success'=>true,'message'=>"Cart updated successfully."]);
    }

    public function update(Request $request,Cart $cart)
    {
        if($cart->update($request->all()) === true)
        return response()->json(['success'=>true,'message'=>"Cart updated successfully."]);
    }

    public function destroy($id)
    {
        if(DB::table('carts')->where('id',$id)->delete() == 1)
        return response()->json(['success'=>true, 'message' => 'Deleted successfully.']);
        else
        return response()->json(['success'=>false, 'message' => 'An unexpected error has occurred.']);
    }

}
