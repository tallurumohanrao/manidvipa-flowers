<?php
namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\API\BaseController as BaseController;

// use App\Order\CartData;
// use App\Traits\SmsTrait;

use Auth,Validator,DB,Str;
use Illuminate\Support\Facades\Schema;
use App\Traits\GoogleDistanceTrait;
use App\Traits\ShippingChargeTrait;
use App\Support\PriceVisibility;
use App\Support\SellingOption;

class OrderController extends BaseController
{
    use GoogleDistanceTrait,ShippingChargeTrait;

    public function storeWhatsAppOrder(Request $request)
    {
        $user_id = null;
        $user = null;
        if(auth('sanctum')->check()){
            $user = auth('sanctum')->user();
            $user_id = $user->id;
        }

        $cart_session = $request->cart_session ?? null;
        if(empty($cart_session) && empty($user_id)){
            return response()->json(['success'=>false,'message'=>'Cart session missing. Please refresh and try again.'], 422);
        }

        $carts = DB::table('carts')->where(function($cwq) use($cart_session,$user_id){
            if($user_id){
                $cwq->where('user_id',$user_id);
            }
            if($cart_session){
                $cwq->orWhere('cart_session',$cart_session);
            }
        })->get();

        if($carts == null OR $carts->count() < 1){
            return response()->json(['success'=>false,'message'=>'Cart empty.'], 200);
        }

        $now = date('Y-m-d H:i:s');
        $serveDate = $request->filled('serve_date')
            ? date('Y-m-d', strtotime($request->serve_date))
            : date('Y-m-d', strtotime('+1 day'));
        $serveTimeSlotLabel = $request->serve_time_slot_label ?: $request->serve_time_slot;
        $order_encrypt_key = Str::random(40);
        $statusId = DB::table('order_statuses')->whereRaw('LOWER(name) = ?', ['pending'])->value('id')
            ?: DB::table('order_statuses')->where('id', '<>', 1)->orderBy('id')->value('id')
            ?: DB::table('order_statuses')->orderBy('id')->value('id')
            ?: 1;
        $pendingShippingStatusId = $this->statusIdByName('shipping_statuses', 'Pending', 1);
        $distance = null;
        $shippingResult = null;
        if($request->filled('address_id')){
            $distanceResponse = $this->getDistance($request->address_id);
            if($distanceResponse['status'] === 'success'){
                $distance = $distanceResponse['distance'];
            }
        }

        DB::beginTransaction();
        try {
            $create = [
                'order_encrypt_key' => $order_encrypt_key,
                'user_id' => $user_id,
                'name' => $request->name ?: ($user->name ?? 'WhatsApp Customer'),
                'email' => $request->email ?: ($user->email ?? ''),
                'contact_number' => $request->contact_number ?: ($user->contact_number ?? ''),
                'source' => 'whatsapp',
                'serve_date' => $serveDate,
                'order_status_id' => $statusId,
                'created_at' => $now,
            ];
            if (Schema::hasColumn('orders', 'price_locked_at')) {
                $create['price_locked_at'] = $now;
            }

            $orderId = DB::table('orders')->insertGetId($create);
            if(!$orderId){
                DB::rollBack();
                return response()->json(['success'=>false,'message'=>'Server error.'], 500);
            }

            $subTotal = 0;
            $orderProducts = [];
            foreach($carts as $value) :
                $product = DB::table('products')->where('id',$value->product_id)->first();
                if(!$product){
                    continue;
                }
                if(! PriceVisibility::productCanPurchase($product)){
                    $control = PriceVisibility::forProduct($product, 'cart');
                    DB::rollBack();
                    return response()->json(['success'=>false,'message'=>$product->title.': '.($control['message'] ?: 'not available for online ordering.')], 422);
                }

                $weight = DB::table('product_weights')->where(['product_id'=>$value->product_id,'id'=>$value->weight_id])->lockForUpdate()->first();
                if(!$weight){
                    continue;
                }
                SellingOption::hydrateInventory($weight);

                $quantity = max(1, (int) $value->quantity);
                $customQuantity = $value->custom_quantity ?? null;
                if($customError = SellingOption::customQuantityError($weight, $customQuantity)){
                    DB::rollBack();
                    return response()->json(['success'=>false,'message'=>$product->title.': '.$customError],422);
                }
                $stockQuantity = SellingOption::requiredStock($weight, $quantity, $customQuantity);
                $stockError = $this->reserveProductWeight($weight, $stockQuantity, $product->title, $now);
                if($stockError){
                    DB::rollBack();
                    return response()->json([
                        'success'=>false,
                        'message'=>$stockError
                    ], 200);
                }

                $sellPrice = SellingOption::price($weight, 'sell', $customQuantity);
                $listPrice = SellingOption::price($weight, 'list', $customQuantity);
                $costPrice = SellingOption::price($weight, 'cost', $customQuantity);
                $optionLabel = SellingOption::label($weight, $customQuantity);
                $amount = $quantity * $sellPrice;
                DB::table('order_products')->insert([
                    'order_id'=>$orderId,
                    'product_id'=>$product->id,
                    'weight_id'=>$weight->id,
                    'product_title'=>$product->title,
                    'weight'=>$optionLabel,
                    'sku'=>$product->sku,
                    'amount'=> $amount,
                    'sell_price'=> $sellPrice,
                    'list_price'=> $listPrice,
                    'cost_price'=> $costPrice,
                    'quantity'=>$quantity,
                    'stock_quantity'=>$stockQuantity,
                    'created_at' => $now,
                ]);

                $orderProducts[] = [
                    'title' => $product->title,
                    'weight' => $optionLabel,
                    'quantity' => $quantity,
                    'sell_price' => $sellPrice,
                    'amount' => (float) $amount,
                ];
                $subTotal += $amount;
            endforeach;

            if(count($orderProducts) < 1){
                DB::rollBack();
                return response()->json(['success'=>false,'message'=>'Cart products are not available.'], 200);
            }

            $cart_coupon = DB::table('cart_line_items')->where(['cart_session'=>$cart_session,'value_name'=>'coupon'])->first();
            $coupon_discount = 0;
            $couponTitle = null;
            if(@$cart_coupon){
                $coupon_row = DB::table('coupons')->where('id',$cart_coupon->value_id)->first();
                if($coupon_row){
                    $discount_price = $coupon_row->discount;
                    if ($coupon_row->is_percentage_discount) {
                        $coupon_discount = ($subTotal * $discount_price) / 100;
                    }else{
                        $coupon_discount = $discount_price;
                    }
                    $coupon_discount = $coupon_discount * - 1;
                    $couponTitle = "Coupon (".$coupon_row->coupon_code.")";
                }
            }

            if($distance !== null){
                $shippingResult = $this->calculateShippingCharge($distance, (float) $subTotal);
            }

            $vat = config('VAT_AMOUNT');
            $vat_amount = @$vat ? floor(($subTotal * $vat)/100) : 0;
            $shipping_amount = $shippingResult && $shippingResult['available'] ? $shippingResult['amount'] : 0;
            $total = $subTotal + $shipping_amount + $coupon_discount + $vat_amount;

            DB::table('orders')->where('id',$orderId)->update(['sub_total'=>$subTotal,'amount'=>$total,'updated_at'=>$now]);
            DB::table('order_lineitems')->insert(['order_id' => $orderId, 'title' => 'Sub Total', 'amount' => $subTotal, 'weight' => 1]);
            if($couponTitle){
                DB::table('order_lineitems')->insert(['order_id' => $orderId, 'title' =>$couponTitle, 'amount' => $coupon_discount, 'weight' => 2]);
            }
            DB::table('order_lineitems')->insert([
                'order_id' => $orderId,
                'title' => $shippingResult && $shippingResult['available'] ? $shippingResult['title'] : 'Delivery Charges',
                'amount' => $shipping_amount,
                'weight' => 3,
            ]);
            if(@$vat){
                DB::table('order_lineitems')->insert(['order_id' => $orderId, 'title' => 'GST', 'amount' =>$vat_amount , 'weight' => 4]);
            }
            if(!empty($serveTimeSlotLabel)){
                DB::table('order_lineitems')->insert(['order_id' => $orderId, 'title' => 'Delivery Time Slot: '.trim($serveTimeSlotLabel), 'amount' => 0, 'weight' => 5]);
            }
            DB::table('order_lineitems')->insert(['order_id' => $orderId, 'title' => 'WhatsApp Verification', 'amount' => 0, 'weight' => 8]);
            DB::table('order_lineitems')->insert(['order_id' => $orderId, 'title' => 'Total', 'amount' => $total, 'weight' => 9]);

            DB::table('order_shippings')->insert([
                'name' => $shippingResult && $shippingResult['available'] ? $shippingResult['price_title'] : 'Confirm on WhatsApp',
                'shipping_type' => null,
                'amount' => $shipping_amount,
                'order_id' => $orderId,
                'shipping_status_id' => $pendingShippingStatusId,
                'created_at' => $now,
            ]);

            DB::table('order_payments')->insert([
                'transaction_id' => null,
                'order_id' => $orderId,
                'payment_amount' => null,
                'payment_method' => 'WhatsApp Order',
                'payment_status' => 'Pending',
                'created_at' => $now,
            ]);

            $this->clear($cart_session,$user_id);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);
            return response()->json(['success'=>false,'message'=>'Unable to save WhatsApp order. Please try again.'], 500);
        }

        $phone = preg_replace('/\D+/', '', (string) (DB::table('settings')->where('key', 'SITE_WHATSAPP')->value('value') ?: config('settings.SITE_WHATSAPP') ?: '917337525445'));
        if(strlen($phone) === 10){
            $phone = '91'.$phone;
        }

        $itemLines = [];
        foreach($orderProducts as $index => $item) :
            $itemLines[] = ($index + 1).'. '.$item['title'].' - '.$item['weight'].' x '.$item['quantity'].' = ₹'.number_format($item['amount'], 2, '.', '');
        endforeach;

        $messageLines = [
            'Manidvipa Flowers Order Request',
            '',
            'Official Order ID: MF-'.$orderId,
            'Delivery Date: '.date('d M Y', strtotime($serveDate)),
            'Delivery Time: '.($serveTimeSlotLabel ?: 'To be confirmed'),
            '',
            'Items:',
            implode("\n", $itemLines),
            '',
            'Official Total: ₹'.number_format($total, 2, '.', ''),
            '',
            'Important: This order is already saved in Manidvipa system. Edited WhatsApp prices/totals are not valid. Please verify using Order ID MF-'.$orderId.'.',
        ];
        $message = implode("\n", $messageLines);
        $whatsappUrl = 'https://wa.me/'.$phone.'?text='.urlencode($message);

        return response()->json([
            'success' => true,
            'message' => 'Order saved. Opening WhatsApp.',
            'data' => [
                'order_id' => $orderId,
                'order_reference' => 'MF-'.$orderId,
                'order_encrypt_key' => $order_encrypt_key,
                'sub_total' => $subTotal,
                'total' => $total,
                'whatsapp_message' => $message,
                'whatsapp_url' => $whatsappUrl,
            ],
        ], 200);
    }

    public function store(Request $request)
    {
        $user_id = null;
        $user = null;
        if(auth('sanctum')->check()){
            $user = auth('sanctum')->user();
            $user_id = $user->id;
        }
        $address_id = $request->address_id;
        // if(empty($user_id)){
        //     $response1 = [ 'success' => false, 'redirect'=>true, 'message' => 'Please login to proceed cart.' ];
        //     return response()->json($response1, 422);
        // }
        if(empty($address_id)){
            return response()->json([ 'success' => false,'message' => 'Please fill delivery address.' ], 422);
        }
        #$carts = DB::table('carts')->where('user_id',$user_id)->get();
        $cart_session = $request->cart_session ?? null;
        $carts = DB::table('carts')->where(function($cwq) use($cart_session,$user_id){
            if($user_id){
                $cwq->where('user_id',$user_id);
            }
            if($cart_session){
                $cwq->orWhere('cart_session',$cart_session);
            }
        })->get();
        
        if($carts == null OR $carts->count() < 1){
            return response()->json(['success'=>false,'message'=>'Cart empty.'], 200);
        }

        $vat = config('VAT_AMOUNT');
        $shipping = null;
        $distance = null;
        $distanceSource = null;
        $shippingResult = null;
        $shipping_amount = 0;
        if($request->filled('address_id')){
            $distance_response = $this->getDistance($address_id);
            if($distance_response['status'] =='success'){
                $distance = $distance_response['distance'];
                $distanceSource = $distance_response['source'] ?? null;
            }else{
                return response()->json([
                    'success'=>false,
                    'message'=>$distance_response['message'] ?? 'Unable to calculate delivery charges for the selected address. Please check the address or contact support.',
                    'distance_source'=>$distance_response['source'] ?? null,
                ], 422);
            }
        }

        // $user = DB::table('users')->select('id','name','email','phone','status')->where('id',$user_id)->first();
        // if($user == null OR empty($user)){
        //     return response()->json(['success'=>false,'message'=>'Customer does not existed with requested input.'], 422);
        // }
        $order_encrypt_key = Str::random(40);

        DB::beginTransaction();
        try {
            $now = date('Y-m-d H:i:s');
            $create['order_encrypt_key'] = $order_encrypt_key;
            $create['user_id'] = $user_id;
            $create['name'] = $request->name ?? ($user->name ?? '');
            $create['email'] = $request->email ?? ($user->email ?? '');
            $create['contact_number'] = $request->contact_number ?? ($user->contact_number ?? '');
            $create['source'] = 'website';
            $create['serve_date'] = date('Y-m-d',strtotime($request->serve_date));
            $create['order_status_id'] = $this->statusIdByName('order_statuses', 'Checkout', 1);
            $create['created_at'] = $now;
            if (Schema::hasColumn('orders', 'price_locked_at')) {
                $create['price_locked_at'] = $now;
            }
            $orderId = DB::table('orders')->insertGetId($create);
            if(!$orderId){
                DB::rollBack();
                return response()->json(['success'=>false,'message'=>'Server error.'], 500);
            }

            $orderProducts = [];
            $subTotal = 0;
            foreach($carts as $value) :
                $product = DB::table('products')->where('id',$value->product_id)->first();
                if(!$product){
                    continue;
                }
                if(! PriceVisibility::productCanPurchase($product)){
                    $control = PriceVisibility::forProduct($product, 'cart');
                    DB::rollBack();
                    return response()->json(['success'=>false,'message'=>$product->title.': '.($control['message'] ?: 'not available for online ordering.')], 422);
                }
                $weight = DB::table('product_weights')->where(['product_id'=>$value->product_id,'id'=>$value->weight_id])->lockForUpdate()->first();
                if(!$weight){
                    continue;
                }
                SellingOption::hydrateInventory($weight);

                $quantity = max(1, (int) $value->quantity);
                $customQuantity = $value->custom_quantity ?? null;
                if($customError = SellingOption::customQuantityError($weight, $customQuantity)){
                    DB::rollBack();
                    return response()->json(['success'=>false,'message'=>$product->title.': '.$customError],422);
                }
                $stockQuantity = SellingOption::requiredStock($weight, $quantity, $customQuantity);
                $stockError = $this->reserveProductWeight($weight, $stockQuantity, $product->title, $now);
                if($stockError){
                    DB::rollBack();
                    return response()->json(['success'=>false,'message'=>$stockError], 200);
                }

                $sellPrice = SellingOption::price($weight, 'sell', $customQuantity);
                $listPrice = SellingOption::price($weight, 'list', $customQuantity);
                $costPrice = SellingOption::price($weight, 'cost', $customQuantity);
                $amount = $quantity * $sellPrice;
                DB::table('order_products')->insert(['order_id'=>$orderId,'product_id'=>$product->id,'weight_id'=>$weight->id,'product_title'=>$product->title,'weight'=>SellingOption::label($weight,$customQuantity),'sku'=>$product->sku,'amount'=>$amount,'sell_price'=>$sellPrice,'list_price'=>$listPrice,'cost_price'=>$costPrice,'quantity'=>$quantity,'stock_quantity'=>$stockQuantity,'created_at'=>$now]);
                $subTotal += $amount;
                $orderProducts[] = $product->id;
            endforeach;

            if(count($orderProducts) < 1){
                DB::rollBack();
                return response()->json(['success'=>false,'message'=>'Cart products are not available.'], 200);
            }

            $cart_coupon = DB::table('cart_line_items')->where(['cart_session'=>$cart_session,'value_name'=>'coupon'])->first();
            $coupon_discount = 0;
            $coupon_row = null;
            if(@$cart_coupon){
                $coupon_row = DB::table('coupons')->where('id',$cart_coupon->value_id)->first();
                if($coupon_row){
                    $discount_price = $coupon_row->discount;
                    if ($coupon_row->is_percentage_discount) {
                        $coupon_discount = ($subTotal * $discount_price) / 100;
                    }else{
                        $coupon_discount = $discount_price;
                    }
                    $coupon_discount = $coupon_discount * - 1;
                }
            }

            $shippingResult = $this->calculateShippingCharge($distance === null ? null : (float) $distance, (float) $subTotal);
            if(!$shippingResult['available']){
                DB::rollBack();
                return response()->json([
                    'success'=>false,
                    'message'=>$shippingResult['message'],
                    'distance'=>$distance,
                    'distance_source'=>$distanceSource,
                ], 422);
            }
            $shipping_amount = $shippingResult['amount'];

            $vat_amount = @$vat ? floor(($subTotal * $vat)/100) : 0;
            $total = $subTotal + $shipping_amount + $coupon_discount + $vat_amount;
            DB::table('orders')->where('id',$orderId)->update(['sub_total'=>$subTotal,'amount'=>$total,'updated_at'=>$now]);
            DB::table('order_lineitems')->insert(['order_id' => $orderId, 'title' => 'Sub Total', 'amount' => $subTotal, 'weight' => 1]);
            if($coupon_row){
                DB::table('order_lineitems')->insert(['order_id' => $orderId, 'title' =>"Coupon (".$coupon_row->coupon_code.")", 'amount' => $coupon_discount, 'weight' => 2]);
            }
            DB::table('order_lineitems')->insert(['order_id' => $orderId, 'title' => $shippingResult['title'], 'amount' => $shipping_amount, 'weight' => 3]);
            if(@$vat){
                DB::table('order_lineitems')->insert(['order_id' => $orderId, 'title' => 'GST', 'amount' =>$vat_amount , 'weight' => 4]);
            }
            $serve_time_slot_label = $request->serve_time_slot_label ?: $request->serve_time_slot;
            if(!empty($serve_time_slot_label)){
                DB::table('order_lineitems')->insert(['order_id' => $orderId, 'title' => 'Delivery Time Slot: '.trim($serve_time_slot_label), 'amount' => 0, 'weight' => 5]);
            }
            DB::table('order_lineitems')->insert(['order_id' => $orderId, 'title' => 'Total', 'amount' => $total, 'weight' => 9]);
            $shippingStore['name'] = $shippingResult['price_title'];
            $shippingStore['shipping_type'] =  null;
            $shippingStore['amount'] = $shipping_amount;
            $shippingStore['order_id'] = $orderId;
            $shippingStore['shipping_status_id'] = $this->statusIdByName('shipping_statuses', 'Pending', 1);
            $shippingStore['created_at'] = $now;
            DB::table('order_shippings')->insert($shippingStore);

            $address = DB::table('addresses')->where(['user_id'=>$user_id,'id'=>$address_id])->first();
            if($address){
                $shipping_address['order_id'] = $orderId;
                $shipping_address['full_name'] = $address->full_name;
                $shipping_address['email'] = $address->email;
                $shipping_address['phone_number'] = $address->phone_number;
                $shipping_address['address_line1'] = $address->address_line1;
                $shipping_address['address_line2'] = $address->address_line2;
                $shipping_address['landmark'] = $address->landmark;
                $shipping_address['city'] = $address->city;
                $shipping_address['state'] = $address->state;
                $shipping_address['pincode'] = $address->pincode;
                $shipping_address['address_type'] = $address->address_type;
                $shipping_address['created_at'] = $now;
                DB::table('order_shipping_addresses')->insert($shipping_address);
            }

            $this->clear($cart_session,$user_id);
            if($request->payment_method == 'cod'){
                DB::table('orders')->where('id',$orderId)->update(['order_status_id'=>$this->statusIdByName('order_statuses', 'Pending', 2),'updated_at'=>$now]);
                 $payInfo = [
                       'transaction_id' => null,
                       'order_id' => $orderId,
                       'payment_amount' => null,
                       'payment_method' => 'Cash On Delivery',
                       'payment_status' => 'Pending',
                       'created_at' => $now
                    ];

                DB::table('order_payments')->insert($payInfo);
                DB::commit();
                return response()->json(['success' => true,'data' => ['order_id'=>$orderId,'order_encrypt_key'=>$order_encrypt_key,'payment_method'=>$request->payment_method],'message'=>'Order placed successfully.'], 200);
            }

            DB::commit();
            return response()->json(['success' => true,'data' => ['order_id'=>$orderId,'order_encrypt_key'=>$order_encrypt_key,'payment_method'=>$request->payment_method],'message'=>'Payment pending...'], 200);
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);
            return response()->json(['success'=>false,'message'=>'Unable to place the order, please try again.'], 500);
        }
    }
    
    public function updatePayment(Request $request){
        $validator = Validator::make($request->all(), [
            'order_id' => 'required'
        ]);
        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors());       
        }
        $orderId = $request->order_id;
        $user_id = $request->user_id;
        DB::table('orders')->where('id',$orderId)->update(['order_status_id'=>$this->statusIdByName('order_statuses', 'Pending', 2),'admin_sms_sent'=>1,'updated_at'=>date('Y-m-d H:i:s')]);
             $payInfo = [
                   'transaction_id' => $request->transaction_id,
                   'order_id' => $orderId,
                   'payment_amount' => $request->payment_amount,
                   'payment_method' => $request->payment_method,
                   'payment_status' => $request->payment_status,
                   'created_at' => date('Y-m-d H:i:s')
                ];

        $paymentId = DB::table('order_payments')->insertGetId($payInfo);
        if($paymentId){
            return response()->json(['success' => true,'message'=>'Payment details updated successfully.'], 200);
        }else{
            return response()->json(['success' => false,'message'=>'Server error.'], 500);
        }
    }

    private function statusIdByName(string $table, string $name, int $fallback): int
    {
        $id = DB::table($table)->whereRaw('LOWER(name) = ?', [strtolower($name)])->value('id');

        return $id ? (int) $id : $fallback;
    }

    private function reserveProductWeight($weight, float $quantity, string $productTitle, string $now): ?string
    {
        $pool = null;
        if ((int) ($weight->inventory_pool_id ?? 0) > 0) {
            $pool = DB::table('product_inventory_pools')
                ->where('id', $weight->inventory_pool_id)
                ->lockForUpdate()
                ->first();
            if (! $pool || ! (int) $pool->status) {
                return $productTitle.' is currently unavailable.';
            }
            $weight->inventory_qty = (float) $pool->qty;
            $weight->inventory_track_stock = (int) $pool->track_stock;
            $weight->inventory_status = (int) $pool->status;
        }

        if(! SellingOption::tracksStock($weight)){
            return null;
        }

        $availableQuantity = SellingOption::availableStock($weight);
        if($availableQuantity < $quantity){
            $unit = SellingOption::inventoryUnitLabel($weight, $availableQuantity);
            return $productTitle.' stock not available. Available stock: '.SellingOption::formatNumber($availableQuantity).' '.$unit.'.';
        }

        $query = $pool
            ? DB::table('product_inventory_pools')->where('id', $pool->id)
            : DB::table('product_weights')->where('id',$weight->id);
        $query->update([
            'qty'=> DB::raw('qty-'.sprintf('%.3F',$quantity)),
            'updated_at'=>$now,
        ]);

        return null;
    }

    public function clear($cart_session,$user_id){
        if($cart_session OR $user_id){
            DB::table('carts')->where(function($cwq) use($cart_session,$user_id){
                if($user_id){
                    $cwq->where('user_id',$user_id);
                }
                if($cart_session){
                    $cwq->orWhere('cart_session',$cart_session);
                }
            })->delete();
        }
        DB::table('cart_line_items')->where('cart_session',$cart_session)->delete();
    }
    
    public function orderSummary($order_encrypt_key)
    {
        $user_id = null;
        if(auth('sanctum')->check()){
            $user = auth('sanctum')->user();
            $user_id = $user->id;
        }
        
        if(!$order_encrypt_key){
            return response()->json(['status'=>false,'message'=>'Page not found.'], 404);
        }
        
        $order = DB::table('orders as o')->select('o.id','o.order_encrypt_key','o.user_id','o.name','o.email','o.amount','o.order_status_id','o.serve_date','o.created_at','o.updated_at','o.sub_total','o.price_locked_at','os.name as order_status_name')->leftJoin('order_statuses as os', 'os.id', '=', 'o.order_status_id')->where('o.order_encrypt_key',$order_encrypt_key)->first();
        if(!$order){
            return response()->json(['status'=>false,'message'=>'Page not found.'], 404);
        }
        
        $productsResults = DB::table('order_products as op')->select('op.product_id','op.product_title','op.sku','op.sell_price','op.amount','op.quantity','op.weight')->where('op.order_id',$order->id)->get();
        $products = null;
        foreach($productsResults as $productsResult) :
            $image = DB::table('product_images')->where('product_id',$productsResult->product_id)->where('status',1)->first();
            $image_name = null;
            if($image){
                $image_name = $image->name;
            }
            $products[] = ['product_id'=>$productsResult->product_id,'product_title'=>$productsResult->product_title,'sku'=>$productsResult->sku,'sell_price'=>$productsResult->sell_price,'amount'=>$productsResult->amount,'quantity'=>$productsResult->quantity,'weight'=>$productsResult->weight,'image_url'=> $image_name];
        endforeach;
        $payment = DB::table('order_payments')->where('order_id',$order->id)->first();
        $shipping = DB::table('order_shippings')->where('order_id',$order->id)->first();
        $shipping_address = DB::table('order_shipping_addresses')->where('order_id',$order->id)->first();
        $orderlineitems = DB::table('order_lineitems')->select('order_id','title','amount')->where('order_id',$order->id)->orderBy('weight')->get();
        return response()->json(['status'=>true,'order'=>$order,'products'=>$products,'payment'=>$payment,'shipping'=>$shipping,'shipping_address'=>$shipping_address,'orderlineitems'=>$orderlineitems], 200);
    }
}
