<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Razorpay\Api\Api;
use DB;

class RazorpayController extends Controller
{
    public function index()
    {
        $orderId = session('order_id');
        $order = DB::table('orders')->where('id',$orderId)->first();
        return view('razorpay.index',compact('order'));
    }

    public function handlePayment(Request $request)
    {
        $orderId = session('order_id');
        DB::table('orders')->where('id',$orderId)->update(['order_status_id'=>2]);
        $input = $request->all();
        $api = new Api(config('RAZORPAY_KEY'), config('RAZORPAY_SECRET'));
        $payment = $api->payment->fetch($request->razorpay_payment_id);
        if(count($input)  && !empty($input['razorpay_payment_id'])) {
            try {
                $payment->capture(array('amount'=>$payment['amount']));
                $payment = $api->payment->fetch($request->razorpay_payment_id);

                $update = [
                   'transaction_id' => $request->razorpay_payment_id,
                   'order_id' => $orderId,
                   'payment_amount' => $payment->amount / 100,
                   'payment_method' => $payment->method,
                   'payment_status' => ucwords($payment->status)];
                   $result = DB::table('order_payments')->where('order_id',$orderId)->update($update);
                   DB::table('orders')->where('id',$orderId)->update(['order_status_id'=>4]);
            } catch (\Exception $e) {
                return  $e->getMessage();
                //\Session::put('error',$e->getMessage());
                //return redirect()->back();
            }
        }

        //\Session::put('success', 'Payment successful');
        if($result){
            return response()->json(['status'=>'success']);
        }else{
            return response()->json(['status'=>'error']);
        }
    }

    public function success(){
        $order = DB::table('orders')->where('id',session('order_id'))->first();
        session()->forget(['order_id','cart','session_cart']);
        return view('razorpay.success',compact('order'));
    }
}
