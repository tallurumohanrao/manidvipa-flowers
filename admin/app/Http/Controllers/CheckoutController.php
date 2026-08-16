<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Cart\CartData;
use App\Http\Requests\StoreAddressRequest;
use Auth,DB;

class CheckoutController extends Controller
{
    public function index(CartData $cartData){  
        if($cartData->getCart()->count()<=0){
            return redirect()->route('home')->with('warning','Your cart is currently empty.');
        }
        $user = Auth::user();
        if($user){
            $default_address = DB::table('addresses')->where(['user_id'=>$user->id,'is_default'=>1])->first();
            if($default_address){
                $address = (array)$default_address;
                $input = array_diff_key($address, array_flip(['user_id','address_type','is_default','created_at','updated_at']));
                if(!hasParam('billingAddress')){
                    setParam('billingAddress', $input);
                }
                if(!hasParam('shippingAddress')){
                    setParam('shippingAddress', $input);
                }
            }
        }
        $shippingMethods = null;
        if(DB::table('shipping_prices')->count() == 1){
            $sp = DB::table('shipping_prices')->where('shipping_amount',0)->first();
            setParam('shippingMethod', ['title' => $sp->title, 'id' => $sp->id, 'value' => $sp->shipping_amount]);
        }
        if(!hasParam('shippingMethod')){
            $shippingMethods = DB::table('shipping_prices')->where('status',1)->get();
        }
        return view('checkout.index',compact('user','shippingMethods'));
    }

    public function getAddress($type){
        $user_id = Auth::id();
        $addresses = DB::table('addresses')->where('user_id',$user_id)->get();
        return response()->json(['status'=>'success','html'=>view('checkout.change-address',compact('type','addresses'))->render()]);
    }

    /*public function destroyAddress($type,$id)
    {
        $userId = Auth::id();
        $address =  DB::table('addresses')->where(['id'=>$id,'user_id'=>$userId])->first();
        if($address->user_id != $userId){
            return response()->json(['success'=>false, 'message' => 'You are not autherized to delete the record.']);
        }
        if(DB::table('addresses')->where(['id'=>$id,'user_id'=>$userId])->delete()){
            if(hasParam($type)){
                if(getParam($type.'.id') == $id){
                    removeParam($type);
                }
            } 
            return response()->json(['success'=>true, 'message' => 'Deleted successfully.','has_'.$type => hasParam($type)]);
        }else{
            return response()->json(['success'=>false, 'message' => 'An unexpected error has occurred.']);
        }
    }*/

    public function selectAddress(Request $request,$type){
        $id = $request->address;
        $address = DB::table('addresses')->where('id',$id)->first();
        $input['id'] = $address->id;
        $input['full_name'] = $address->full_name;
        $input['email'] = $address->email;
        $input['company_name'] = $address->company_name;
        $input['phone_number'] = $address->phone_number;
        $input['address_line1'] = $address->address_line1;
        $input['address_line2'] = $address->address_line2;
        $input['landmark'] = $address->landmark;
        $input['city'] = $address->city;
        $input['state'] = $address->state;
        $input['country'] = $address->country;
        $input['pincode'] = $address->pincode;
        setParam($type, $input);
        $address = getAddress($type);
        $messsage = $type =='billingAddress'?'Billing address updated successfully.':'Shipping address updated successfully.';
        if(hasParam($type)){
            return response()->json(['status'=>'success','message'=>$messsage,'type'=>$type,'address'=>$address]);
        }
        return response()->json(['status'=>'error','message'=>'Unexpected error occured!.','type'=>$type,'address'=>null]);
        #return redirect()->route('checkout.index')->with('success', 'Address changed successfully!');
    }
    public function storeAddress(storeAddressRequest $request,$type){
        $user_id = Auth::id();
        // if(empty($user_id)){
        //     return response()->json(['status'=>'error','message'=>'Unauthenticated.']);
        // }
        $input = $request->address;
        $id = $input['id'];
        if($user_id){
            $input['user_id'] = $user_id;
            if($id){
                $input['id'] = $id;
                DB::table('addresses')->where('id',$id)->update($input);
            }else{
                $input['id'] = DB::table('addresses')->insertGetId($input);
            }
        }
        setParam($type, $input);
        $address = getAddress($type);
        $messsage = $type =='billingAddress'?'Billing address added successfully.':'Shipping address added successfully.';
        if(hasParam($type)){
            return response()->json(['status'=>'success','message'=>$messsage,'type'=>$type,'address'=>$address]);
        }
        return response()->json(['status'=>'error','message'=>'Unexpected error occured!.','type'=>$type,'address'=>null]);
    }
    public function storeShippingmethod(Request $request,CartData $cartData)
    {
        if($id = $request->shippingMethod)
        {
            $sp = DB::table('shipping_prices')->where('id',$id)->first();
            setParam('shippingMethod', ['title' => $sp->title, 'id' => $sp->id, 'value' => $sp->shipping_amount]);
            if(hasParam('shippingMethod')){
                return response()->json(['status'=>'success','message'=>'Shipping method added successfully.']);
            }else{
                return response()->json(['status'=>'error','message'=>'Unexpected error occured!..']);
            }
        }
    }

    public function addCoupon(Request $request,CartData $cartData)
    {
        if($request->coupon)
        {
            $status = 1;
            $user=Auth::user();
            $coupon = DB::table('coupons')->where('coupon_code',$request->coupon)->first();
            if(empty($coupon)){
                $status = 0;
                return response()->json(['status'=>'error','message'=>'Invalid coupon.']);
            }

            //user
            if((int)$coupon->usage_limit_per_user){
                if(!$user){
                    $status = 0;
                    return response()->json(['status'=>'error','message'=>'Please login to avail coupon.']);
                }

                $usagecount = DB::table('coupon_orders')->join("orders", "orders.id", "coupon_orders.order_id")->where("orders.user_id", $user->id)->where("coupon_orders.coupon_id", $coupon->id)->count();
                if($usagecount >= (int)$coupon->usage_limit_per_user){
                    $status = 0;
                    return response()->json(['status'=>'error','message'=>'Coupon Usage Limit Expired.']);
                }

            }

            //order
            if($coupon->usage_limit){
                $count = DB::table('coupon_orders')->where('coupon_id',$coupon->id)->count();
                if($count >= $coupon->usage_limit){
                    $status = 0;
                    return response()->json(['status'=>'error','message'=>'Coupon Usage Limit Expired']);
                }
            }

            //min amount
            if($coupon->minimum_purchage_amount){
                if($cartData->getSubTotal() < $coupon->minimum_purchage_amount ){
                    $status = 0;
                    $amount = currency($coupon->minimum_purchage_amount);
                    return response()->json(['status'=>'error','message'=>"Order amount should be minimum $amount to avail coupon."]);
                }
            }
            //start date
            if($coupon->start_date){
                if(strtotime($coupon->start_date) > strtotime(date("Y-m-d"))){
                    $status = 0;
                    return response()->json(['status'=>'error','message'=>'Coupon Expired.']);
                }
            }

            //end date
            if($coupon->end_date){
                if(strtotime($coupon->end_date) < strtotime(date("Y-m-d"))){
                    $status = 0;
                    return response()->json(['status'=>'error','message'=>'Coupon Expired.']);
                }
            }
            if($status){
                $value = $coupon->discount;
                if ($coupon->is_percentage_discount) {
                    $value = ($cartData->getSubTotal() * $coupon->discount) / 100;
                }
                $value = $value * - 1;
                setParam('coupon', ['title' => $coupon->coupon_code, 'id' => $coupon->id, 'value' => $value]);
                if(hasParam('coupon')){
                    return response()->json(['status'=>'success','message'=>'Coupon added successfully.']);
                }else{
                    return response()->json(['status'=>'error','message'=>'Unexpected error occured!..']);
                }

            }

            if(!$status && hasParam('coupon')){
                removeParam('coupon');
            }
        }else{
            return response()->json(['status'=>'error','message'=>'Please enter a coupon code.']);
        }
    }

    public function removeCoupon(){
        removeParam('coupon');
        return redirect('/checkout');
    }
}
