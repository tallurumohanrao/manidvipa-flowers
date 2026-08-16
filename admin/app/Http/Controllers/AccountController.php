<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\StoreUserAccountRequest;
use App\Http\Requests\StoreChangePasswordRequest;
use Auth,DB,Hash;

class AccountController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $user = Auth::user();
        return view('user.index', compact('user'));
    }

    public function orders()
    {
        $perpage = config('PER_PAGE');
        // $orders = DB::table('orders')->where('user_id',Auth::id())->paginate($perpage);
        $query = DB::table('orders')->selectRaw('orders.*,order_statuses.name as order_status,order_payments.payment_status,order_payments.payment_method,order_payments.transaction_id,order_payments.payment_amount,order_payments.updated_at as order_payment_updated_at,shipping_statuses.name as shipping_status,order_shippings.amount as shipping_amount,order_shippings.tracking_no as shipping_tracking_no,order_shippings.tracking_link as order_tracking_link,order_shippings.shipping_type,order_shippings.updated_at as order_shipping_updated_at');
        $query->join('order_statuses', 'orders.order_status_id', '=', 'order_statuses.id');
        $query->join('order_payments', 'order_payments.order_id', '=', 'orders.id');
        $query->join('order_shippings', 'order_shippings.order_id', '=', 'orders.id');
        $query->join('shipping_statuses', 'order_shippings.shipping_status_id', '=', 'shipping_statuses.id');
        $orders = $query->where('user_id',Auth::id())->orderByDesc('id')->paginate($perpage);
        return view('user.orders.index', compact('orders'));
    }

    public function store(Request $request)
    {
        //
    }

    public function show($id)
    {
        $query = DB::table('orders')->selectRaw('orders.*,order_statuses.name as order_status,order_payments.payment_status,order_payments.payment_method,order_payments.transaction_id,order_payments.payment_amount,order_payments.updated_at as order_payment_updated_at,shipping_statuses.name as shipping_status,order_shippings.amount as shipping_amount,order_shippings.tracking_no as shipping_tracking_no,order_shippings.tracking_link as order_tracking_link,order_shippings.shipping_type,order_shippings.updated_at as order_shipping_updated_at');
        $query->join('order_statuses', 'orders.order_status_id', '=', 'order_statuses.id');
        $query->join('order_payments', 'order_payments.order_id', '=', 'orders.id');
        $query->join('order_shippings', 'order_shippings.order_id', '=', 'orders.id');
        $query->join('shipping_statuses', 'order_shippings.shipping_status_id', '=', 'shipping_statuses.id');
        $order = $query->where(['orders.id'=>$id,'orders.user_id'=>Auth::id()])->first();

        $products = DB::table('order_products')->selectRaw('order_products.*,products.id as order_product_id')->leftJoin('products', 'order_products.product_id', '=', 'products.id')->where('order_id',$id)->get();
        $billingaddress = DB::table('order_billing_addresses')->where('order_id',$order->id)->first();
        $shippingaddress = DB::table('order_shipping_addresses')->where('order_id',$order->id)->first();
        $orderlineitems = DB::table('order_lineitems')->where('order_id',$order->id)->orderBy('weight')->get();
        $ordercomments = DB::table('order_comments')->where('order_id',$order->id)->orderByDesc('id')->get();
        return view('user.orders.show', compact('order','products','billingaddress','shippingaddress','orderlineitems','ordercomments'));
    }


    public function changePassword()
    {
        return view('user.change_password');
    }

    public function updatePassword(StoreChangePasswordRequest $request)
    {
        if($request->isMethod('POST')){
            $user = Auth::user();
            if (Hash::check($request->get('current_password'), $user->password)) {
                $user->password = Hash::make($request->get('new_password'));
                $user->save();
                return back()->with('success', 'Password changed successfully!');
            } else {
                return back()->withErrors('Current password is incorrect');
            }
        }
        return view('user.change_password');
    }

    public function update(StoreUserAccountRequest $request)
    {
        $user = Auth::user();
        $user->update($request->all());
        return redirect()->route('user.index')->with('success','Updated successfully.');
    }

    public function wishlist()
    {
        $perpage = config('PER_PAGE');
        $user_id = Auth::id();
        $query = DB::table('wishlist')->select('wishlist.id','wishlist.product_id','wishlist.created_at','products.title','products.stock','products.qty','products.slug');
        $query->join('products', 'products.id', '=', 'wishlist.product_id');
        $query->where('user_id',$user_id);
        $data = $query->paginate($perpage); #dd($data);
        return view('user.wishlist',compact('data'));
    }

    public function wishlistdelete(Request $request)
    {
       $ids = explode(',',$request->ids);
        foreach($ids as $id) :
            $result = DB::table('wishlist')->where('id',$id)->delete();
        endforeach;
        if($result == 1)
        return response()->json(['success'=>true, 'message' => 'Deleted successfully.']);
        else
        return response()->json(['success'=>false, 'message' => 'An unexpected error has occurred.']);
    }
}
