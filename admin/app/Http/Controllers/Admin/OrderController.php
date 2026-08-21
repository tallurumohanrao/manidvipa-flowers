<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Gate,DB,View;


class OrderController extends Controller
{

    public function __construct()
    {
        $this->module = 'orders';
        View::share ( 'module', $this->module );
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        abort_if(Gate::denies($this->module.'_view'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        $perPage = $request->per_page ?: config('ADMIN_PER_PAGE');
        $query = DB::table('orders')->selectRaw('orders.*,order_statuses.name as order_status,order_payments.payment_status,shipping_statuses.name as shipping_status');
        $query->join('order_statuses', 'orders.order_status_id', '=', 'order_statuses.id');
        $query->leftJoin('order_payments', 'order_payments.order_id', '=', 'orders.id');
        $query->leftJoin('order_shippings', 'order_shippings.order_id', '=', 'orders.id');
        $query->leftJoin('shipping_statuses', 'order_shippings.shipping_status_id', '=', 'shipping_statuses.id');
        if ($request->filled('orderId')) {
            $query->where('id', $request->orderId );
        }
        if ($request->filled('orderStatus')) {
            $query->where('order_status_id' , $request->orderStatus);
        }

        if ($request->filled('orderStatus')) {
            $query->where('order_status_id' , $request->orderStatus);
        }

        if ($request->filled('shippingStatus')) {
            $query->where('shipping_status_id' , $request->shippingStatus);
        }

        if ($request->filled('paymentStatus')) {
            $query->where('order_payments.payment_status' , $request->paymentStatus);
        }

        if ($request->filled('deliveryDate')) {
            $query->whereDate('serve_date' , $request->deliveryDate);
        }

        $data = $query->orderByDesc('id')->paginate($perPage)->withQueryString();

        $shippingStatuses = DB::table('shipping_statuses')->get()->pluck('name','id');
        $orderStatuses = DB::table('order_statuses')->get()->pluck('name','id');
        return view('admin.'.$this->module.'.index', compact('data','orderStatuses','shippingStatuses'));
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Admin\Order  $order
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        abort_if(Gate::denies($this->module.'_view'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        $query = DB::table('orders')->selectRaw('orders.*,order_statuses.name as order_status,order_payments.payment_status,order_payments.payment_method,order_payments.transaction_id,order_payments.payment_amount,order_payments.updated_at as order_payment_updated_at,shipping_statuses.name as shipping_status,order_shippings.amount as shipping_amount,order_shippings.tracking_no as shipping_tracking_no,order_shippings.tracking_link as order_tracking_link,order_shippings.shipping_type,order_shippings.updated_at as order_shipping_updated_at');
        $query->join('order_statuses', 'orders.order_status_id', '=', 'order_statuses.id');
        $query->join('order_payments', 'order_payments.order_id', '=', 'orders.id');
        $query->join('order_shippings', 'order_shippings.order_id', '=', 'orders.id');
        $query->join('shipping_statuses', 'order_shippings.shipping_status_id', '=', 'shipping_statuses.id');
        $order = $query->where('order_status_id','<>',1)->where('orders.id',$id)->first();
        abort_if(!$order,404);
        $products = DB::table('order_products')->selectRaw('order_products.*,products.id as order_product_id')->leftJoin('products', 'order_products.product_id', '=', 'products.id')->where('order_id',$id)->get();

        $shippingStatuses = DB::table('shipping_statuses')->get()->pluck('name','id');
        $orderStatuses = DB::table('order_statuses')->get()->pluck('name','id');        

        $billingaddress = DB::table('order_billing_addresses')->where('order_id',$order->id)->first();
        $shippingaddress = DB::table('order_shipping_addresses')->where('order_id',$order->id)->first();
        $orderlineitems = DB::table('order_lineitems')->where('order_id',$order->id)->orderBy('weight')->get();
        $deliveryTimeSlotLabel = '';
        foreach($orderlineitems as $orderlineitem){
            if(stripos($orderlineitem->title, 'Delivery Time Slot:') === 0){
                $deliveryTimeSlotLabel = trim(substr($orderlineitem->title, strlen('Delivery Time Slot:')));
                break;
            }
        }
        $ordercomments = DB::table('order_comments')->where('order_id',$order->id)->orderByDesc('id')->get();
        return view('admin.'.$this->module.'.show', compact('order','products','shippingStatuses','orderStatuses','billingaddress','shippingaddress','orderlineitems','ordercomments','deliveryTimeSlotLabel'));
    }

    public function destroy($id)
    {
        abort_if(Gate::denies($this->module.'_delete'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        if(DB::table('orders')->where('id',$id)->delete() == 1)
        return response()->json(['success'=>true, 'message' => 'Deleted successfully.']);
        else
        return response()->json(['success'=>false, 'message' => 'An unexpected error has occurred.']);
    }

    public function massDestroy(Request $request)
    {
        abort_if(Gate::denies($this->module.'_delete'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        $ids = explode(',',$request->ids);
        foreach($ids as $id) :
            $result = DB::table('orders')->where('id',$id)->delete();
        endforeach;

        if($result == 1)
        return response()->json(['success'=>true, 'message' => 'Deleted successfully.']);
        else
        return response()->json(['success'=>false, 'message' => 'An unexpected error has occurred.']);
    }


    public function updateShipping(Request $request,$id)
    {
        abort_if(Gate::denies($this->module.'_edit'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        $now = date('Y-m-d H:i:s');
        if(DB::table('order_shippings')->where('order_id',$id)->update(['shipping_status_id' => $request->shipping_status,'updated_at'=>$now])){
            $shipping_status = DB::table('shipping_statuses')->select('name')->where('id',$request->shipping_status)->first();
            return response()->json(['success'=>true, 'message' => 'Shipping status successfully updated.','html' => $shipping_status->name]);
        }
        return response()->json(['success'=>false, 'message' => 'No changes made in the request.']);

        // $order = $this->model::find($id);
        // $shipping = $order->orderShippingOne($request->shipping_id);
        // $shipping->update($request->except(['_method','_token','shipping_id']));
        // $order = $this->model::find($id);
        // return response()->json(['success'=>true, 'message' => 'Shipping details successfully updated.','html' => '']);
    }

    public function updatePayment(Request $request,$id)
    {
        abort_if(Gate::denies($this->module.'_edit'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        $now = date('Y-m-d H:i:s');
        $input = $request->except('_method','_token');
        $input['updated_at'] = $now;
        if(DB::table('order_payments')->where('order_id',$id)->update($input)){
            return response()->json(['success'=>true, 'message' => 'Payment status successfully updated.','html' => $request->payment_status]);
        }
        return response()->json(['success'=>false, 'message' => 'No changes made in the request.']);
    }

    public function updateDeliveryPreference(Request $request,$id)
    {
        abort_if(Gate::denies($this->module.'_edit'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');

        $request->validate([
            'serve_date' => 'required|date',
            'serve_time_slot' => 'required|string|max:120',
        ]);

        $order = DB::table('orders')->where('id',$id)->first();
        if(!$order){
            return response()->json(['success'=>false, 'message' => 'Order not found.'], 404);
        }

        $now = date('Y-m-d H:i:s');
        $serveDate = date('Y-m-d', strtotime($request->serve_date));
        $serveTimeSlot = trim($request->serve_time_slot);
        $lineItemTitle = 'Delivery Time Slot: '.$serveTimeSlot;

        DB::table('orders')->where('id',$id)->update([
            'serve_date' => $serveDate,
            'updated_at' => $now,
        ]);

        $existingLineItem = DB::table('order_lineitems')
            ->where('order_id',$id)
            ->where('title','like','Delivery Time Slot:%')
            ->first();

        if($existingLineItem){
            DB::table('order_lineitems')->where('id',$existingLineItem->id)->update([
                'title' => $lineItemTitle,
                'amount' => 0,
                'weight' => 5,
                'updated_at' => $now,
            ]);
        }else{
            DB::table('order_lineitems')->insert([
                'order_id' => $id,
                'title' => $lineItemTitle,
                'amount' => 0,
                'weight' => 5,
                'created_at' => $now,
            ]);
        }

        return response()->json([
            'success'=>true,
            'message' => 'Delivery date and time slot updated successfully.',
            'date_html' => date('d/m/Y',strtotime($serveDate)),
            'slot_html' => e($serveTimeSlot),
            'lineitem_html' => e($lineItemTitle),
        ]);
    }

    public function updateBooking(Request $request,$id)
    {
        abort_if(Gate::denies($this->module.'_edit'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        $now = date('Y-m-d H:i:s');
        if(DB::table('orders')->where('id',$id)->update(['order_status_id' => $request->order_status,'updated_at'=>date('Y-m-d H:i:s')])){
            $order_status = DB::table('order_statuses')->select('name')->where('id',$request->order_status)->first();
            
            DB::table('order_shippings')->where('order_id',$id)->update(['shipping_status_id'=>4]);
            $products = DB::table('order_products')->where('order_id',$id)->get();
            foreach($products as $product) :
                $weight = DB::table('product_weights')->where('id',$product->weight_id)->first();
                if($weight AND ($weight->stock == 1)){
                    DB::table('product_weights')->where('id',$product->weight_id)->update(['qty'=> DB::raw('qty+'.$product->quantity)]);
                }
            endforeach;
            
            return response()->json(['success'=>true, 'message' => 'Order status successfully updated.','html' => $order_status->name]);
        }
        return response()->json(['success'=>false, 'message' => 'No changes made in the request.']);
    }
}
