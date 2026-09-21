<?php
namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\API\BaseController as BaseController;

use App\Http\Requests\StoreCartRequest;
use Auth,Validator,DB;
use App\Traits\GetCartTrait;
use App\Traits\GoogleDistanceTrait;
use App\Traits\ShippingChargeTrait;
use App\Support\PriceVisibility;
use App\Support\SellingOption;

class CartController extends BaseController
{
    use GetCartTrait,GoogleDistanceTrait,ShippingChargeTrait;

    private function cartOwnerScope(?string $cartSession, ?int $userId): \Closure
    {
        return function($query) use($cartSession,$userId){
            if($userId){
                $query->where('user_id',$userId);
            }else{
                $query->where('cart_session',$cartSession);
            }
        };
    }

    public function getcart(Request $request){
        $user_id = null;
        $carts = [];
        if(auth('sanctum')->check()){
            $user = auth('sanctum')->user();
            $user_id = $user->id;
        }
        
        $cart_session = $request->cart_session ?? null;
        if($cart_session OR $user_id){
            $carts = DB::table('carts')->where($this->cartOwnerScope($cart_session,$user_id))->get();
        }
        
        
        $products = [];
        $subTotal = 0;
        $vat = config('VAT_AMOUNT');
        foreach($carts as $value) :
            $product = DB::table('products')->where('id',$value->product_id)->where('status', 1)->first();
            if(!$product){
                continue;
            }
            if(! PriceVisibility::productCanPurchase($product)){
                continue;
            }
            #$size = DB::table('sizes')->where('id',$value->size_id)->first(); 
            $weight = DB::table('product_weights')->where(['id'=>$value->weight_id,'product_id'=>$product->id])->where('status', 1)->first();
            if(!$weight){
                continue;
            }
            SellingOption::hydrateInventory($weight);
            $availableWeights = SellingOption::hydrateInventoryCollection(DB::table('product_weights')
                ->select(
                    'id','name','sell_price','list_price','stock','qty','quantity_value','quantity_unit',
                    'unit_id','inventory_pool_id','is_default',
                    'pricing_mode','unit_sell_price','unit_list_price','allow_custom_quantity',
                    'minimum_custom_quantity','maximum_custom_quantity','custom_quantity_step'
                )
                ->where('product_id',$product->id)
                ->where('status', 1)
                ->orderByRaw('CASE WHEN stock = 1 AND qty < COALESCE(quantity_value, 1) THEN 1 ELSE 0 END')
                ->orderByRaw('CAST(sell_price AS DECIMAL(10,2)) ASC')
                ->orderBy('id')
                ->get())
                ->map(fn ($option) => SellingOption::publicData($option))
                ;
            $availableWeights = SellingOption::sortOptions($availableWeights);
            $image = null;
            $productImage = DB::table('product_images')->where('product_id',$value->product_id)->where('status', 1)->orderBy('priority')->first();
            if($productImage){ 
                $image = $productImage->name;
            }
            $customQuantity = $value->custom_quantity ?? null;
            $sellPrice = SellingOption::price($weight, 'sell', $customQuantity);
            $listPrice = SellingOption::price($weight, 'list', $customQuantity);
            $products[] = [
                'cart_id'=>$value->id,'user_id'=>$value->user_id,'product_id'=>$product->id,
                'product_slug'=>$product->slug,'weight_id'=>$weight->id,
                'weight'=>SellingOption::label($weight, $customQuantity),'product_title'=>$product->title,
                'image'=>$image,'sell_price'=>$sellPrice,'list_price'=>$listPrice,
                'quantity'=>$value->quantity,'custom_quantity'=>$customQuantity,
                'is_custom_quantity'=>$customQuantity !== null,'available_weights'=>$availableWeights,
            ];
            $subTotal += ($sellPrice * $value->quantity);
        endforeach;
        $coupon_row = null;
        $cart_coupon = DB::table('cart_line_items')->where(['cart_session'=>$cart_session,'value_name'=>'coupon'])->first(); 
        if($cart_coupon){
            $coupon_row = DB::table('coupons')->where('id',$cart_coupon->value_id)->first(); 
        }
        
        $shipping = null;
        $distance = null;
        $distanceSource = null;
        $shippingResult = null;
        if($request->filled('address_id')){
            $distance_response = $this->getDistance($request->address_id);
            if($distance_response['status'] =='success'){
                $distance = $distance_response['distance'];
                $distanceSource = $distance_response['source'] ?? null;
                $shippingResult = $this->calculateShippingCharge($distance, (float) $subTotal);
                $shipping = $shippingResult['available'] ? $shippingResult : null;
            }else{
                $shippingResult = $this->shippingUnavailable($distance_response['message'] ?? 'Unable to calculate delivery charges for the selected address.', null);
                $distanceSource = $distance_response['source'] ?? null;
            }
        }
        
        $totals['sub_total'] = ['title' => 'Sub Total', 'amount' => $subTotal];

        if($coupon_row){
            $discount_price = $coupon_row->discount;
            if ($coupon_row->is_percentage_discount) {
                $coupon_discount = ($subTotal * $discount_price) / 100;
            }else{
                $coupon_discount = $discount_price;
            }
            $coupon_discount = $coupon_discount * - 1;
            $totals['coupon'] = ['title' => "Coupon (".$coupon_row->coupon_code.")", 'amount' => $coupon_discount];
        }

        if(@$vat){
            $totals['gst'] = ['title' => 'GST', 'amount' => floor(($subTotal * $vat)/100)];
        }

        if($shipping){
            $totals['shipping'] = ['title' => $shipping['title'], 'amount' => $shipping['amount']];
        }
        $totalAmount = 0;
        foreach($totals as $total){
            $totalAmount += $total['amount'];
        }
        $totals['total'] = ['title' => 'Total', 'amount' => $totalAmount];
        
        return response()->json([
            'success' => true,
            'data'=> $products,
            'cart_count' => count($products),
            'totals'=> $totals,
            'distance'=>$distance,
            'distance_source'=>$distanceSource,
            'shipping_available' => $shippingResult['available'] ?? null,
            'shipping_message' => $shippingResult['message'] ?? null,
        ], 200);
    }
    
    public function addtocart(Request $request)
    {
        $user_id = null;
        if(auth('sanctum')->check()){
            $user = auth('sanctum')->user();
            $user_id = $user->id;
        }
        
        $input = $request->all();
        $validator = Validator::make($input, [
            'product_id' => 'required|integer',
            'quantity' => 'required|integer|min:1|max:9999',
            'weight_id' => 'required|integer',
            'cart_session' => 'nullable|string|max:50',
            'custom_quantity' => 'nullable|numeric|gt:0|max:1000000',
        ],['product_id.required'=>'Product is required.','quantity.required'=>'Quantity is required.','weight_id.required'=>'Weight is required.']);
   
        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors());     
        }
        $cart_session = $request->cart_session ?? null;
        if (! $user_id && ! $cart_session) {
            return response()->json(['success' => false, 'message' => 'Cart session is required.'], 422);
        }
        
        $message = null;
        $data = [
            'user_id' => $user_id,
            'cart_session' => $cart_session,
            'product_id' => (int) $request->product_id,
            'weight_id' => (int) $request->weight_id,
            'quantity' => (int) $request->quantity,
            'custom_quantity' => $request->filled('custom_quantity') ? (float) $request->custom_quantity : null,
        ];
        
        $weight = null;
        if($data['product_id']){
            $product = DB::table('products')->where('id', $data['product_id'])->where('status', 1)->first();
            $weight = DB::table('product_weights')
                ->where(['id'=>$data['weight_id'],'product_id'=>$data['product_id']])
                ->where('status', 1)
                ->first();
            if(! $product || empty($weight)){
                return response()->json(['success'=>false,'message'=>'Product not available.'],422);exit;
            }
            SellingOption::hydrateInventory($weight);
            if(! PriceVisibility::productCanPurchase($product)){
                $control = PriceVisibility::forProduct($product, 'cart');
                return response()->json(['success'=>false,'message'=>$control['message'] ?: 'This product is not available for online ordering.'],422);
            }
            if($customError = SellingOption::customQuantityError($weight, $data['custom_quantity'])){
                return response()->json(['success'=>false,'message'=>$customError],422);
            }
        }
        //'session'=>$cart_session
        $criteria = ['product_id'=>$request->product_id,'weight_id'=>$request->weight_id];
        $cart_row = DB::table('carts')
            ->when($user_id, fn ($query) => $query->where('user_id', $user_id))
            ->when(! $user_id, fn ($query) => $query->where('cart_session', $cart_session))
            ->where($criteria)
            ->when($data['custom_quantity'] === null, fn ($query) => $query->whereNull('custom_quantity'))
            ->when($data['custom_quantity'] !== null, fn ($query) => $query->where('custom_quantity', $data['custom_quantity']))
            ->first();
        if($cart_row){
            $incomingQuantity = max(1, (int) $data['quantity']);
            $data['quantity'] = max(0, (int) $cart_row->quantity) + $incomingQuantity;

            if($weight && ! SellingOption::hasAvailableStock($weight, $data['quantity'], $data['custom_quantity'])){
                $message = $this->stockMessage($weight);
                return response()->json(['success'=>false,'message'=>$message],200);exit;
            }

            if(DB::table('carts')->where('id',$cart_row->id)->update($data)){
                return response()->json(['success'=>true,'message'=>"Cart updated successfully."],200); 
            }else{
                return response()->json(['success'=>true,'message'=>"You haven't changed anything in the cart."],200); 
            }
        }else{
            if($weight && ! SellingOption::hasAvailableStock($weight, $data['quantity'], $data['custom_quantity'])){
                $message = $this->stockMessage($weight);
                return response()->json(['success'=>false,'message'=>$message],200);exit;
            }

            if(DB::table('carts')->insertGetId($data)){
                return response()->json(['success'=>true,'message'=>"Product added to cart successfully."],200);
            }else{
                return response()->json(['success'=>false,'message'=>"Server error."],500); 
            }
        }
        #$cart = Cart::updateOrCreate(['product_id' => @$data['product_id'],'user_id'=>$user_id],$data);
        
    }
    
    public function update(Request $request){
        $errors = null;
        $validator = Validator::make($request->all(), [
            'products' => ['required','array'],
            'products.*.cart_id' => ['required','integer'],
            'products.*.quantity' => ['nullable','integer','min:0','max:9999'],
            'products.*.weight_id' => ['nullable','integer'],
            'products.*.custom_quantity' => ['nullable','numeric','gt:0','max:1000000'],
            'cart_session' => ['nullable','string','max:50'],
        ]);
        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors());
        }
        $user_id = null;
        if(auth('sanctum')->check()){
            $user = auth('sanctum')->user();
            $user_id = $user->id;
        }
        $cart_session = $request->cart_session ?? null;
        if(empty($cart_session) && empty($user_id)){
            return response()->json(['success'=>false,'message'=>'Cart session missing. Please refresh and try again.'],422);
        }

        $ownerScope = $this->cartOwnerScope($cart_session,$user_id);

        $mergedCartIdsToSkip = [];
        foreach(($request->products ?: []) as $product) :
            if(empty($product['cart_id'])){
                continue;
            }
            if(isset($mergedCartIdsToSkip[$product['cart_id']])){
                continue;
            }

            if($cart = DB::table('carts')->where('id',$product['cart_id'])->where($ownerScope)->first()){
                if($cart->product_id){
                    $cartProduct = DB::table('products')->where('id', $cart->product_id)->where('status', 1)->first();
                    if(! $cartProduct || ! PriceVisibility::productCanPurchase($cartProduct)){
                        $errors[$cart->id][] = ($product['product_title'] ?? 'Product').' is not available for online ordering.';
                        continue;
                    }
                    $quantity = isset($product['quantity']) ? (int) $product['quantity'] : 1;
                    if($quantity < 1){
                        DB::table('carts')->where('id',$cart->id)->delete();
                        continue;
                    }

                    $targetWeightId = !empty($product['weight_id']) ? $product['weight_id'] : $cart->weight_id;
                    $weight = DB::table('product_weights')->where(['id'=>$targetWeightId,'product_id'=>$cart->product_id])->where('status', 1)->first();
                    if(!$weight){
                        $product_title = $product['product_title'] ?? 'Product';
                        $errors[$cart->id][] = $product_title.' selected weight is not available.';
                        continue;
                    }
                    SellingOption::hydrateInventory($weight);

                    $quantity = max(1, $quantity);
                    $weightChanged = (int) $targetWeightId !== (int) $cart->weight_id;
                    $customQuantity = array_key_exists('custom_quantity', $product)
                        ? ($product['custom_quantity'] !== null && $product['custom_quantity'] !== '' ? (float) $product['custom_quantity'] : null)
                        : ($weightChanged ? null : ($cart->custom_quantity ?? null));
                    if($customError = SellingOption::customQuantityError($weight, $customQuantity)){
                        $errors[$cart->id][] = $customError;
                        continue;
                    }
                    $duplicateCart = null;
                    $stockCheckQuantity = $quantity;
                    if($weightChanged || (float)($cart->custom_quantity ?? 0) !== (float)($customQuantity ?? 0)){
                        $duplicateCart = DB::table('carts')
                            ->where($ownerScope)
                            ->where('product_id',$cart->product_id)
                            ->where('weight_id',$targetWeightId)
                            ->when($customQuantity === null, fn ($query) => $query->whereNull('custom_quantity'))
                            ->when($customQuantity !== null, fn ($query) => $query->where('custom_quantity',$customQuantity))
                            ->where('id','<>',$cart->id)
                            ->first();
                        if($duplicateCart){
                            $stockCheckQuantity = max(0, (int) $duplicateCart->quantity) + $quantity;
                        }
                    }

                    if(! SellingOption::hasAvailableStock($weight, $stockCheckQuantity, $customQuantity)){
                        $product_title = $product['product_title'] ?? 'Product';
                        $errors[$cart->id][] = $product_title.': '.$this->stockMessage($weight);
                        continue;
                    }

                    if($duplicateCart){
                        DB::table('carts')->where('id',$duplicateCart->id)->update(['quantity'=>$stockCheckQuantity,'custom_quantity'=>$customQuantity]);
                        DB::table('carts')->where('id',$cart->id)->delete();
                        $mergedCartIdsToSkip[$duplicateCart->id] = true;
                    }else{
                        DB::table('carts')->where('id',$cart->id)->update(['quantity'=>$quantity,'weight_id'=>$targetWeightId,'custom_quantity'=>$customQuantity]);
                    }
                }
            }
        endforeach;
        if($errors){
            return response()->json(['success'=>false,'message'=>$errors],200);
        }else{
           $message = "Cart updated successfully.";
        }
        return response()->json(['success'=>true,'message'=>$message],200); 
    }
    
    public function updatebkp(Request $request)
    {
        $cart = DB::table('carts')->where(['id'=>$request->cart_id,'user_id'=>$request->user_id])->first();
        if(empty($cart)){
            $response = [
                'success' => false,
                'message' => 'Cart item not found.'
            ];
            return response()->json($response, 422);
        }
        if($cart->product_id){
            $weight = DB::table('weights')->where(['id'=>$cart->weight_id,'product_id'=>$cart->product_id])->first();
            if(empty($weight)){
                return response()->json(['success'=>false,'message'=>'Product weight is not available.'],422);exit;
            }
            if($weight->stock && ($weight->qty < $request->quantity)){
                $message = 'Stock not available. Available stock is ('.$weight->qty .').';
                return response()->json(['success'=>false,'message'=>$message]);exit;
            }
        }
        if(DB::table('carts')->where(['id'=>$request->cart_id])->update($request->only('user_id','quantity')) == true)
            return response()->json(['success'=>true,'message'=>"Cart updated successfully."]);
        else
            return response()->json(['success'=>false,'message'=>"No changes done to your cart."]);
    }

    private function stockMessage(object $weight): string
    {
        $available = SellingOption::availableStock($weight);
        $quantity = SellingOption::formatNumber($available);
        $unit = SellingOption::inventoryUnitLabel($weight, $available);

        return 'Out of stock. Available stock: '.$quantity.' '.$unit.'.';
    }
    
    public function destroy(Request $request)
    {
        $user_id = null;
        if(auth('sanctum')->check()){
            $user = auth('sanctum')->user();
            $user_id = $user->id;
        }
        
        $input = $request->all();
        $validator = Validator::make($input, [
            'cart_id' => 'required|integer',
            'cart_session' => 'nullable|string|max:50'
        ]);
   
        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors());     
        }
        $cart_session = $request->cart_session ?? null;
        if(empty($cart_session) && empty($user_id)){
            return response()->json(['success'=>false,'message'=>'Cart session missing. Please refresh and try again.'],422);
        }
        if($cart_session OR $user_id){
            $result = DB::table('carts')
                ->where('id',$request->cart_id)
                ->where($this->cartOwnerScope($cart_session,$user_id))
                ->delete();
            if($result){
                return response()->json(['success'=>true,'message'=>"Product deleted from cart."],200);
            }else{
                return response()->json(['success'=>false,'message'=>"Unable to delete the record."],200);
            }
        }else{
            return response()->json(['success'=>false,'message'=>"Server Error"],500);
        }
    }

    public function paymentmethods()
    {
        $data = DB::table('payment_methods')->select('id','name','title','is_default')->where('status',1)->get();
        return response()->json(['success'=>true,'data'=>$data],200);
    }
    public function addCoupon(Request $request)
    {
        $coupon_input = $request->coupon_code;
        if(empty($coupon_input)){
            return response()->json(['success'=>false,'message'=>'Please enter a coupon code.']);
        }
        
        $user_id = null;
        $status = 1;
        if(auth('sanctum')->check()){
            $user = auth('sanctum')->user();
            $user_id = $user->id;
        }
        $cart_session = $request->cart_session ?? null;
        if(empty($cart_session)){
            return response()->json(['success'=>false,'message'=>'Cart session missed.']);
        }
        $coupon = DB::table('coupons')->where('coupon_code',$coupon_input)->first();
        if(empty($coupon)){
            $status = 0;
            return response()->json(['status'=>false,'message'=>'Invalid coupon.']);
        }
    
            //user
        if($user_id){
            if((int)$coupon->usage_limit_per_user){
                // if(!$user){
                //     $status = 0;
                //     return response()->json(['success'=>false,'message'=>'Please login to avail coupon.']);
                // }
    
                $usagecount = DB::table('coupon_orders')->join("orders", "orders.id", "coupon_orders.order_id")->where("orders.user_id", $user->id)->where("coupon_orders.coupon_id", $coupon->id)->count();
                if($usagecount >= (int)$coupon->usage_limit_per_user){
                    $status = 0;
                    return response()->json(['success'=>false,'message'=>'Coupon Usage Limit Exceeded.']);
                }
            }
        }

        //order
        if($coupon->usage_limit){
            $count = DB::table('coupon_orders')->where('coupon_id',$coupon->id)->count();
            if($count >= $coupon->usage_limit){
                $status = 0;
                return response()->json(['success'=>false,'message'=>'Coupon Usage Limit Exceeded']);
            }
        }

        //min amount
        $subTotal = 0;
        if($coupon->minimum_purchage_amount){
            $subTotal = $this->getSubTotal($cart_session, $user_id);
            
            if($subTotal < $coupon->minimum_purchage_amount ){
                $status = 0;
                $amount = currency($coupon->minimum_purchage_amount);
                return response()->json(['success'=>false,'message'=>"Order amount should be minimum $amount to avail coupon.",'Sub Total'=>$subTotal]);
            }
        }
        //start date
        if($coupon->start_date){
            if(strtotime($coupon->start_date) > strtotime(date("Y-m-d"))){
                $status = 0;
                return response()->json(['success'=>false,'message'=>'Coupon Expired.']);
            }
        }

        //end date
        if($coupon->end_date){
            if(strtotime($coupon->end_date) < strtotime(date("Y-m-d"))){
                $status = 0;
                return response()->json(['success'=>false,'message'=>'Coupon Expired.']);
            }
        }
        if($status){
            $result = DB::table('cart_line_items')->insert(['cart_session'=>$cart_session,'value_id'=>$coupon->id,'value_name'=>'coupon','value_title'=>$coupon->title]);
            #setParam('coupon', ['title' => $coupon->coupon_code, 'id' => $coupon->id, 'value' => $value]);
            if($result){
                return response()->json(['success'=>true,'message'=>'Coupon added successfully.']);
            }else{
                return response()->json(['success'=>false,'message'=>'Sorry!...Coupon not available.']);
            }
        }

        if(!$status){
            return response()->json(['success'=>false,'message'=>'Remove coupon from cart.']);
        }
        
    }
    public function getAddressById(Request $request)
    {
        $user_id = null;
        if(auth('sanctum')->check()){
            $user = auth('sanctum')->user();
            $user_id = $user->id;
        }
        $validator = Validator::make($request->all(), [
            'address_id' => 'required'
        ]);
        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors());       
        }
        $data = DB::table('addresses')->where(['id'=>$request->address_id,'user_id'=>$user_id])->first();
        return response()->json(['success'=>true,'data'=>$data],200);
    }
    public function editAddressById(Request $request)
    {
        $user_id = null;
        if(auth('sanctum')->check()){
            $user = auth('sanctum')->user();
            $user_id = $user->id;
        }
        $validator = Validator::make($request->all(), [
            'address_id' => 'required'
        ]);
        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors());       
        }
        if(DB::table('addresses')->where(['id'=>$request->address_id,'user_id'=>$user_id])->doesntExist()){
            return response()->json(['success'=>false,'message'=> 'Address not existed.'],200);
        }
        $update = $request->except('address_id');
        $update['updated_at'] = date('Y-m-d H:i:s');
        DB::table('addresses')->where(['id'=>$request->address_id,'user_id'=>$user_id])->update($update);
        return response()->json(['success'=>true,'data'=>['address_id'=>$request->address_id],'message'=>'Address updated successfully.'],200);
    }
    public function storeAddress(Request $request)
    {
        $user_id = null;
        if(auth('sanctum')->check()){
            $user = auth('sanctum')->user();
            $user_id = $user->id;
        }
        
        $validator = Validator::make($request->all(), [
            'full_name' => 'required',
            'email' => 'required|email',
            'phone_number' => 'required|size:10',
            'address_line1' => 'required',
            'address_line2' => 'required',
            'city' => 'required',
            'pincode' => 'required',
            'state' => 'required',
        ]);
   
        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors());       
        }
        $insert = $request->only('full_name', 'email', 'phone_number', 'address_line1', 'address_line2', 'landmark', 'city', 'state', 'pincode', 'address_type', 'is_default');
        $insert['user_id'] = $user_id;
        $insert['created_at'] = date('Y-m-d H:i:s');
        if($request->is_default == 1){
            if($user_id){
                DB::table('addresses')->where('user_id',$user_id)->update(['is_default' => NULL]);
            }
        }
        $address_id = DB::table('addresses')->insertGetId($insert);
        return response()->json(['success'=>true,'data'=>['address_id'=>$address_id],'message'=> 'Address stored successfully.'],200);
    }
    public function getCartCoupon(Request $request){
        $validator = Validator::make($request->all(), [
            'cart_session' => 'required',
        ]);
   
        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors());       
        }
        $user_id = null;
        $coupon_discount = null;
        if(auth('sanctum')->check()){
            $user = auth('sanctum')->user();
            $user_id = $user->id;
        }
        $coupon = DB::table('cart_line_items as cli')->select('cli.id','c.title','c.coupon_code','c.discount','c.is_percentage_discount')->where('cli.cart_session',$request->cart_session)->join('coupons as c','c.id','=','cli.value_id')->orderByDesc('cli.id')->first();
        
        if($coupon){
            $coupon_discount = $coupon->discount;
            if ($coupon->is_percentage_discount) {
                $subTotal = $this->getSubTotal($request->cart_session,$user_id);
                $coupon_discount = ($subTotal * $coupon->discount) / 100;
            }
            $data['cart_coupon_id'] = $coupon->id;
            $data['title'] = $coupon->title;
            $data['coupon_code'] = $coupon->coupon_code;
            $data['discount'] = $coupon_discount;
            return response()->json(['success'=>true,'data'=>$data],200); 
        }else{
            return response()->json(['success'=>false,'data'=>null,'message'=>'Coupon not found.'],200); 
        }
    }
    public function removeCoupon(Request $request){
        $validator = Validator::make($request->all(), [
            'cart_session' => 'required',
        ]);
   
        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors());       
        }
        DB::table('cart_line_items')->where('cart_session',$request->cart_session)->delete();
        return response()->json(['success'=>true,'message'=>'Coupon removed successfully.'],200); 
    }
    public function getCoupons(){
        $data = DB::table('coupons')->get();
        return response()->json(['success'=>true,'data'=>$data]);
    }
}
