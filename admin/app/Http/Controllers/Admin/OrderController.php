<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
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
        $query = DB::table('orders')->selectRaw('orders.*,order_statuses.name as order_status,order_payments.payment_status,shipping_statuses.name as shipping_status,accepted_admin.name as accepted_by_name,packing_admin.name as packing_admin_name,delivery_admin.name as delivery_admin_name');
        $query->join('order_statuses', 'orders.order_status_id', '=', 'order_statuses.id');
        $query->leftJoin('order_payments', 'order_payments.order_id', '=', 'orders.id');
        $query->leftJoin('order_shippings', 'order_shippings.order_id', '=', 'orders.id');
        $query->leftJoin('shipping_statuses', 'order_shippings.shipping_status_id', '=', 'shipping_statuses.id');
        $query->leftJoin('admins as accepted_admin', 'accepted_admin.id', '=', 'orders.accepted_by_admin_id');
        $query->leftJoin('admins as packing_admin', 'packing_admin.id', '=', 'orders.packing_admin_id');
        $query->leftJoin('admins as delivery_admin', 'delivery_admin.id', '=', 'orders.delivery_admin_id');
        if ($request->filled('orderId')) {
            $query->where('orders.id', $request->orderId );
        }

        if ($request->filled('orderStatus')) {
            $query->where('orders.order_status_id' , $request->orderStatus);
        }

        if ($request->filled('shippingStatus')) {
            $query->where('order_shippings.shipping_status_id' , $request->shippingStatus);
        }

        if ($request->filled('paymentStatus')) {
            $query->where('order_payments.payment_status' , $request->paymentStatus);
        }

        if ($request->filled('deliveryDate')) {
            $query->whereDate('orders.serve_date' , $request->deliveryDate);
        }

        if ($request->filled('packingStatus')) {
            $query->where('orders.packing_status', $request->packingStatus);
        }

        if ($request->filled('packingAdmin')) {
            $query->where('orders.packing_admin_id', $request->packingAdmin);
        }

        if ($request->filled('deliveryAdmin')) {
            $query->where('orders.delivery_admin_id', $request->deliveryAdmin);
        }

        if ($request->filled('workflowQueue')) {
            $this->applyWorkflowQueueFilter($query, $request->workflowQueue);
        }

        $data = $query->orderByDesc('id')->paginate($perPage)->withQueryString();

        $shippingStatuses = DB::table('shipping_statuses')->get()->pluck('name','id');
        $orderStatuses = DB::table('order_statuses')->get()->pluck('name','id');
        $packingStatuses = $this->packingStatuses();
        $admins = $this->activeAdminOptions();
        $workflowQueues = $this->workflowQueues();
        return view('admin.'.$this->module.'.index', compact('data','orderStatuses','shippingStatuses','packingStatuses','admins','workflowQueues'));
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
        $query = DB::table('orders')->selectRaw('orders.*,order_statuses.name as order_status,order_payments.payment_status,order_payments.payment_method,order_payments.transaction_id,order_payments.payment_amount,order_payments.updated_at as order_payment_updated_at,shipping_statuses.name as shipping_status,order_shippings.shipping_status_id,order_shippings.amount as shipping_amount,order_shippings.tracking_no as shipping_tracking_no,order_shippings.tracking_link as order_tracking_link,order_shippings.shipping_type,order_shippings.updated_at as order_shipping_updated_at,accepted_admin.name as accepted_by_name,packing_admin.name as packing_admin_name,delivery_admin.name as delivery_admin_name');
        $query->join('order_statuses', 'orders.order_status_id', '=', 'order_statuses.id');
        $query->leftJoin('order_payments', 'order_payments.order_id', '=', 'orders.id');
        $query->leftJoin('order_shippings', 'order_shippings.order_id', '=', 'orders.id');
        $query->leftJoin('shipping_statuses', 'order_shippings.shipping_status_id', '=', 'shipping_statuses.id');
        $query->leftJoin('admins as accepted_admin', 'accepted_admin.id', '=', 'orders.accepted_by_admin_id');
        $query->leftJoin('admins as packing_admin', 'packing_admin.id', '=', 'orders.packing_admin_id');
        $query->leftJoin('admins as delivery_admin', 'delivery_admin.id', '=', 'orders.delivery_admin_id');
        $order = $query->where('orders.id',$id)->first();
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
        $packingStatuses = $this->packingStatuses();
        $admins = $this->activeAdminOptions();
        $workflowEvents = DB::table('order_workflow_events')
            ->leftJoin('admins', 'admins.id', '=', 'order_workflow_events.admin_id')
            ->select('order_workflow_events.*', 'admins.name as admin_name')
            ->where('order_workflow_events.order_id',$order->id)
            ->orderByDesc('order_workflow_events.id')
            ->get();
        return view('admin.'.$this->module.'.show', compact('order','products','shippingStatuses','orderStatuses','packingStatuses','admins','billingaddress','shippingaddress','orderlineitems','ordercomments','deliveryTimeSlotLabel','workflowEvents'));
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
        $validator = Validator::make($request->all(), [
            'shipping_status' => 'required|integer|exists:shipping_statuses,id',
        ]);
        if($validator->fails()){
            return $this->validationErrorResponse($validator);
        }
        if(! DB::table('orders')->where('id',$id)->exists()){
            return response()->json(['success'=>false, 'message' => 'Order not found.'], 404);
        }
        $shipping = DB::table('order_shippings')->where('order_id',$id)->first();
        if(! $shipping){
            return response()->json(['success'=>false, 'message' => 'Shipping record not found for this order.'], 404);
        }
        $now = date('Y-m-d H:i:s');
        $shipping_status = DB::table('shipping_statuses')->select('id','name')->where('id',$request->shipping_status)->first();
        DB::table('order_shippings')->where('order_id',$id)->update(['shipping_status_id' => $request->shipping_status,'updated_at'=>$now]);
        $this->applyShippingTimestamp((int) $request->shipping_status, $id, $now);
        if((int) $shipping->shipping_status_id !== (int) $request->shipping_status){
            $this->logWorkflowEvent(
                $id,
                'shipping_status',
                $this->statusNameById('shipping_statuses', $shipping->shipping_status_id),
                $shipping_status->name
            );
        }

        return response()->json(['success'=>true, 'message' => 'Shipping status successfully updated.','html' => $shipping_status->name]);

        // $order = $this->model::find($id);
        // $shipping = $order->orderShippingOne($request->shipping_id);
        // $shipping->update($request->except(['_method','_token','shipping_id']));
        // $order = $this->model::find($id);
        // return response()->json(['success'=>true, 'message' => 'Shipping details successfully updated.','html' => '']);
    }

    public function updatePayment(Request $request,$id)
    {
        abort_if(Gate::denies($this->module.'_edit'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        $validator = Validator::make($request->all(), [
            'payment_status' => 'required|string|in:'.implode(',', array_keys(paymentstatuses())),
            'payment_amount' => 'nullable|numeric|min:0',
        ]);
        if($validator->fails()){
            return $this->validationErrorResponse($validator);
        }
        if(! DB::table('orders')->where('id',$id)->exists()){
            return response()->json(['success'=>false, 'message' => 'Order not found.'], 404);
        }
        $payment = DB::table('order_payments')->where('order_id',$id)->first();
        if(! $payment){
            return response()->json(['success'=>false, 'message' => 'Payment record not found for this order.'], 404);
        }
        $now = date('Y-m-d H:i:s');
        $input = $request->only('payment_status','payment_amount');
        $input['updated_at'] = $now;
        DB::table('order_payments')->where('order_id',$id)->update($input);
        if((string) $payment->payment_status !== (string) $request->payment_status){
            $this->logWorkflowEvent($id, 'payment_status', $payment->payment_status, $request->payment_status);
        }
        return response()->json(['success'=>true, 'message' => 'Payment status successfully updated.','html' => $request->payment_status]);
    }

    public function updateDeliveryPreference(Request $request,$id)
    {
        abort_if(Gate::denies($this->module.'_edit'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');

        $validator = Validator::make($request->all(), [
            'serve_date' => 'required|date',
            'serve_time_slot' => 'required|string|max:120',
        ]);
        if($validator->fails()){
            return $this->validationErrorResponse($validator);
        }

        $order = DB::table('orders')->where('id',$id)->first();
        if(!$order){
            return response()->json(['success'=>false, 'message' => 'Order not found.'], 404);
        }
        $oldDeliveryTimeSlotLabel = '';
        $oldLineItem = DB::table('order_lineitems')
            ->where('order_id',$id)
            ->where('title','like','Delivery Time Slot:%')
            ->first();
        if($oldLineItem){
            $oldDeliveryTimeSlotLabel = trim(substr($oldLineItem->title, strlen('Delivery Time Slot:')));
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

        $oldValue = ($order->serve_date ? date('d/m/Y', strtotime($order->serve_date)) : 'NILL').' '.$oldDeliveryTimeSlotLabel;
        $newValue = date('d/m/Y', strtotime($serveDate)).' '.$serveTimeSlot;
        if(trim($oldValue) !== trim($newValue)){
            $this->logWorkflowEvent($id, 'delivery_schedule', trim($oldValue), trim($newValue));
        }

        return response()->json([
            'success'=>true,
            'message' => 'Delivery date and time slot updated successfully.',
            'date_html' => date('d/m/Y',strtotime($serveDate)),
            'slot_html' => e($serveTimeSlot),
            'lineitem_html' => e($lineItemTitle),
        ]);
    }

    public function updateWorkflow(Request $request,$id)
    {
        abort_if(Gate::denies($this->module.'_edit'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        $packingStatusKeys = implode(',', array_keys($this->packingStatuses()));
        $validator = Validator::make($request->all(), [
            'order_status' => 'nullable|integer|exists:order_statuses,id',
            'shipping_status' => 'nullable|integer|exists:shipping_statuses,id',
            'accepted_by_admin_id' => 'nullable|integer|exists:admins,id',
            'packing_admin_id' => 'nullable|integer|exists:admins,id',
            'delivery_admin_id' => 'nullable|integer|exists:admins,id',
            'packing_status' => 'required|string|in:'.$packingStatusKeys,
            'workflow_note' => 'nullable|string|max:1000',
        ]);
        if($validator->fails()){
            return $this->validationErrorResponse($validator);
        }

        $order = DB::table('orders')->where('id',$id)->first();
        if(!$order){
            return response()->json(['success'=>false, 'message' => 'Order not found.'], 404);
        }

        $shipping = DB::table('order_shippings')->where('order_id',$id)->first();
        if($request->filled('shipping_status') && ! $shipping){
            return response()->json(['success'=>false, 'message' => 'Shipping record not found for this order.'], 404);
        }

        $now = date('Y-m-d H:i:s');
        $cancelledOrderStatusId = $this->statusIdByName('order_statuses', 'Cancelled', 5);
        $cancelledShippingStatusId = $this->statusIdByName('shipping_statuses', 'Cancelled', 4);
        $wasCancelled = (int) $order->order_status_id === $cancelledOrderStatusId;
        $isCancelled = $request->filled('order_status') && (int) $request->order_status === $cancelledOrderStatusId;

        DB::beginTransaction();
        try {
            $orderUpdates = ['updated_at' => $now];

            if($request->filled('order_status') && (int) $order->order_status_id !== (int) $request->order_status){
                $newOrderStatus = DB::table('order_statuses')->select('id','name')->where('id',$request->order_status)->first();
                $orderUpdates['order_status_id'] = $newOrderStatus->id;
                if(strtolower($newOrderStatus->name) === 'accepted' && empty($order->accepted_by_admin_id) && ! $request->filled('accepted_by_admin_id')){
                    $orderUpdates['accepted_by_admin_id'] = auth('admin')->id();
                    $orderUpdates['accepted_at'] = $now;
                }
                if(strtolower($newOrderStatus->name) === 'accepted' && empty($order->accepted_at)){
                    $orderUpdates['accepted_at'] = $now;
                }
                $this->logWorkflowEvent($id, 'order_status', $this->statusNameById('order_statuses', $order->order_status_id), $newOrderStatus->name);
            }

            $this->applyAdminAssignmentUpdate($id, $order, $orderUpdates, $request, 'accepted_by_admin_id', 'order_manager', $now);
            $this->applyAdminAssignmentUpdate($id, $order, $orderUpdates, $request, 'packing_admin_id', 'packing_staff', $now);
            $this->applyAdminAssignmentUpdate($id, $order, $orderUpdates, $request, 'delivery_admin_id', 'delivery_staff', $now);

            if($request->has('packing_status') && (string) $order->packing_status !== (string) $request->packing_status){
                $orderUpdates['packing_status'] = $request->packing_status;
                $this->applyPackingTimestamp($request->packing_status, $order, $orderUpdates, $now);
                $this->logWorkflowEvent(
                    $id,
                    'packing_status',
                    $this->packingStatusLabel($order->packing_status),
                    $this->packingStatusLabel($request->packing_status)
                );
            }

            DB::table('orders')->where('id',$id)->update($orderUpdates);

            if($isCancelled && ! $wasCancelled){
                DB::table('order_shippings')->where('order_id',$id)->update([
                    'shipping_status_id' => $cancelledShippingStatusId,
                    'updated_at' => $now,
                ]);
                $this->releaseOrderStock($id);
                $this->logWorkflowEvent($id, 'shipping_status', $this->statusNameById('shipping_statuses', $shipping->shipping_status_id ?? null), 'Cancelled');
            }elseif($request->filled('shipping_status') && $shipping && (int) $shipping->shipping_status_id !== (int) $request->shipping_status){
                $shippingStatus = DB::table('shipping_statuses')->select('id','name')->where('id',$request->shipping_status)->first();
                DB::table('order_shippings')->where('order_id',$id)->update([
                    'shipping_status_id' => $shippingStatus->id,
                    'updated_at' => $now,
                ]);
                $this->applyShippingTimestamp($shippingStatus->id, $id, $now);
                $this->logWorkflowEvent($id, 'shipping_status', $this->statusNameById('shipping_statuses', $shipping->shipping_status_id), $shippingStatus->name);
            }

            if($request->filled('workflow_note')){
                $this->logWorkflowEvent($id, 'workflow_note', null, null, trim($request->workflow_note));
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);
            return response()->json(['success'=>false, 'message' => 'Unable to update order workflow.'], 500);
        }

        return response()->json(['success'=>true, 'message' => 'Order workflow updated successfully.']);
    }

    public function updateBooking(Request $request,$id)
    {
        abort_if(Gate::denies($this->module.'_edit'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        $validator = Validator::make($request->all(), [
            'order_status' => 'required|integer|exists:order_statuses,id',
        ]);
        if($validator->fails()){
            return $this->validationErrorResponse($validator);
        }

        $order = DB::table('orders')->where('id',$id)->first();
        if(!$order){
            return response()->json(['success'=>false, 'message' => 'Order not found.'], 404);
        }

        $order_status = DB::table('order_statuses')->select('id','name')->where('id',$request->order_status)->first();
        $cancelledOrderStatusId = $this->statusIdByName('order_statuses', 'Cancelled', 5);
        $cancelledShippingStatusId = $this->statusIdByName('shipping_statuses', 'Cancelled', 4);
        $wasCancelled = (int) $order->order_status_id === $cancelledOrderStatusId;
        $isCancelled = (int) $request->order_status === $cancelledOrderStatusId;
        $now = date('Y-m-d H:i:s');

        DB::beginTransaction();
        try {
            DB::table('orders')->where('id',$id)->update([
                'order_status_id' => $request->order_status,
                'updated_at' => $now,
            ]);
            if((int) $order->order_status_id !== (int) $request->order_status){
                $this->logWorkflowEvent($id, 'order_status', $this->statusNameById('order_statuses', $order->order_status_id), $order_status->name);
            }

            if($isCancelled && ! $wasCancelled){
                DB::table('order_shippings')->where('order_id',$id)->update([
                    'shipping_status_id' => $cancelledShippingStatusId,
                    'updated_at' => $now,
                ]);
                $this->releaseOrderStock($id);
                $this->logWorkflowEvent($id, 'shipping_status', null, 'Cancelled');
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);
            return response()->json(['success'=>false, 'message' => 'Unable to update order status.'], 500);
        }

        return response()->json(['success'=>true, 'message' => 'Order status successfully updated.','html' => $order_status->name]);
    }

    private function statusIdByName(string $table, string $name, int $fallback): int
    {
        return (int) (DB::table($table)->whereRaw('LOWER(name) = ?', [strtolower($name)])->value('id') ?: $fallback);
    }

    private function statusNameById(string $table, $id): string
    {
        if(!$id){
            return 'Not set';
        }

        return DB::table($table)->where('id',$id)->value('name') ?: 'Not set';
    }

    private function packingStatuses(): array
    {
        return [
            'not_started' => 'Not Started',
            'packing_started' => 'Packing Started',
            'packed' => 'Packed',
            'ready_for_dispatch' => 'Ready for Dispatch',
        ];
    }

    private function packingStatusLabel($status): string
    {
        $statuses = $this->packingStatuses();

        return $statuses[$status] ?? 'Not Started';
    }

    private function workflowQueues(): array
    {
        return [
            'new' => 'New Orders',
            'packing' => 'Packing',
            'ready_for_dispatch' => 'Ready for Dispatch',
            'delivery' => 'Out for Delivery',
            'completed' => 'Completed',
        ];
    }

    private function activeAdminOptions()
    {
        return DB::table('admins')
            ->where('status', 1)
            ->orderBy('name')
            ->pluck('name','id');
    }

    private function applyWorkflowQueueFilter($query, string $queue): void
    {
        $checkoutOrderStatusId = $this->statusIdByName('order_statuses', 'Checkout', 1);
        $pendingOrderStatusId = $this->statusIdByName('order_statuses', 'Pending', 2);
        $completedOrderStatusId = $this->statusIdByName('order_statuses', 'Completed', 4);
        $cancelledOrderStatusId = $this->statusIdByName('order_statuses', 'Cancelled', 5);
        $pendingShippingStatusId = $this->statusIdByName('shipping_statuses', 'Pending', 1);
        $dispatchedShippingStatusId = $this->statusIdByName('shipping_statuses', 'Dispatched', 2);
        $deliveredShippingStatusId = $this->statusIdByName('shipping_statuses', 'Delivered', 3);
        $cancelledShippingStatusId = $this->statusIdByName('shipping_statuses', 'Cancelled', 4);
        $outForDeliveryShippingStatusId = $this->statusIdByName('shipping_statuses', 'Out for Delivery', 0);

        if($queue === 'new'){
            $query->whereIn('orders.order_status_id', [$checkoutOrderStatusId, $pendingOrderStatusId]);
            return;
        }

        if($queue === 'packing'){
            $query->whereNotIn('orders.order_status_id', [$completedOrderStatusId, $cancelledOrderStatusId])
                ->whereIn('orders.packing_status', ['not_started', 'packing_started', 'packed']);
            return;
        }

        if($queue === 'ready_for_dispatch'){
            $query->where('orders.packing_status', 'ready_for_dispatch')
                ->whereNotIn('order_shippings.shipping_status_id', [$deliveredShippingStatusId, $cancelledShippingStatusId]);
            return;
        }

        if($queue === 'delivery'){
            $statuses = array_filter([$dispatchedShippingStatusId, $outForDeliveryShippingStatusId]);
            $query->whereIn('order_shippings.shipping_status_id', $statuses);
            return;
        }

        if($queue === 'completed'){
            $query->where(function ($completedQuery) use ($completedOrderStatusId, $deliveredShippingStatusId) {
                $completedQuery->where('orders.order_status_id', $completedOrderStatusId)
                    ->orWhere('order_shippings.shipping_status_id', $deliveredShippingStatusId);
            });
        }
    }

    private function applyAdminAssignmentUpdate(int $orderId, $order, array &$orderUpdates, Request $request, string $column, string $eventType, string $now): void
    {
        if(! $request->has($column)){
            return;
        }

        $newId = $request->filled($column) ? (int) $request->{$column} : null;
        $oldId = $order->{$column} ? (int) $order->{$column} : null;
        if($oldId === $newId){
            return;
        }

        $orderUpdates[$column] = $newId;
        if($column === 'accepted_by_admin_id' && $newId && empty($order->accepted_at)){
            $orderUpdates['accepted_at'] = $now;
        }
        if($column === 'delivery_admin_id' && $newId && empty($order->delivery_assigned_at)){
            $orderUpdates['delivery_assigned_at'] = $now;
        }

        $this->logWorkflowEvent($orderId, $eventType, $this->adminName($oldId), $this->adminName($newId));
    }

    private function adminName($id): string
    {
        if(!$id){
            return 'Unassigned';
        }

        return DB::table('admins')->where('id',$id)->value('name') ?: 'Unassigned';
    }

    private function applyPackingTimestamp(string $packingStatus, $order, array &$orderUpdates, string $now): void
    {
        if($packingStatus === 'packing_started' && empty($order->packing_started_at)){
            $orderUpdates['packing_started_at'] = $now;
        }
        if($packingStatus === 'packed' && empty($order->packed_at)){
            $orderUpdates['packed_at'] = $now;
        }
        if($packingStatus === 'ready_for_dispatch' && empty($order->ready_for_dispatch_at)){
            $orderUpdates['ready_for_dispatch_at'] = $now;
        }
    }

    private function applyShippingTimestamp(int $shippingStatusId, int $orderId, string $now): void
    {
        $shippingStatusName = strtolower($this->statusNameById('shipping_statuses', $shippingStatusId));
        $updates = [];

        if($shippingStatusName === 'dispatched'){
            $updates['dispatched_at'] = $now;
        }
        if($shippingStatusName === 'out for delivery'){
            $updates['out_for_delivery_at'] = $now;
        }
        if($shippingStatusName === 'delivered'){
            $updates['delivered_at'] = $now;
        }

        if($updates){
            $updates['updated_at'] = $now;
            DB::table('orders')->where('id',$orderId)->update($updates);
        }
    }

    private function logWorkflowEvent(int $orderId, string $eventType, $fromValue = null, $toValue = null, ?string $note = null): void
    {
        DB::table('order_workflow_events')->insert([
            'order_id' => $orderId,
            'admin_id' => auth('admin')->id(),
            'event_type' => $eventType,
            'from_value' => $fromValue,
            'to_value' => $toValue,
            'note' => $note,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function validationErrorResponse($validator)
    {
        return response()->json([
            'success' => false,
            'message' => $validator->errors()->first(),
            'errors' => $validator->errors(),
        ], 422);
    }

    private function releaseOrderStock(int $orderId): void
    {
        $products = DB::table('order_products')
            ->select('weight_id','quantity')
            ->where('order_id',$orderId)
            ->whereNotNull('weight_id')
            ->get();

        foreach($products as $product) :
            $quantity = max(0, (int) $product->quantity);
            if($quantity < 1){
                continue;
            }
            $weight = DB::table('product_weights')->where('id',$product->weight_id)->lockForUpdate()->first();
            if($weight && (int) $weight->stock === 1){
                DB::table('product_weights')->where('id',$product->weight_id)->update(['qty'=> DB::raw('qty+'.$quantity)]);
            }
        endforeach;
    }
}
