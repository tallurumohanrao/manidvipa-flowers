<?php
   
namespace App\Http\Controllers\API;
   
use Illuminate\Http\Request;
use App\Http\Controllers\API\BaseController as BaseController;
use Validator,DB,Hash;
   
class AccountController extends BaseController
{
    public function orders(Request $request)
    {
        $user_id = null;
        if(auth('sanctum')->check()){
            $user = auth('sanctum')->user();
            $user_id = $user->id;
        } else {
             return response()->json(['success' => false,'message' => 'Please login to access myaccount.'], 200);
        }
        $orders = DB::table('orders as o')->selectRaw('o.id,o.order_encrypt_key,o.amount,o.sub_total,o.created_at,o.updated_at,o.order_status_id,oss.name as order_status_name,os.updated_at as shipping_update_at,ss.name as shipping_status,os.shipping_status_id')
        ->join('order_statuses as oss','o.order_status_id', '=', 'oss.id')
        #->addSelect(DB::raw("(SELECT COUNT(op.id) as products_count FROM order_products as op WHERE op.order_id = o.id"))
        #->addSelect(DB::raw("(SELECT COUNT(op.id) FROM order_products as op WHERE op.order_id = o.id GROUP BY op.order_id) as products_count"))
        #->addSelect(DB::raw("(SELECT pi.image FROM products as p inner join product_images as pi on p.id=pi.product_id inner join order_products as op on op.product_id=p.id WHERE p.id = op.product_id ORDER BY op.id ASC LIMIT 1) as products_image"))
                #->leftJoin('order_products as op', 'op.order_id', '=', 'o.id') #DB::raw('COUNT(op.id) as products_count')
                ->join('order_shippings as os','os.order_id', '=', 'o.id')->join('shipping_statuses as ss','ss.id', '=', 'os.shipping_status_id')->where('o.user_id',$user_id)->where(function($osq){
            // $osq->where('o.order_status_id',2);
            // $osq->orWhere('o.order_status_id',3);
        })->orderByDesc('o.created_at')->get();
        // ->whereExists(function($sq){
        //             $sq->select(DB::raw(1))->from('order_shippings as os')->whereRaw('os.order_id = o.id AND (shipping_status_id=1 OR shipping_status_id=2)');
        //         })
        
        $data = [];
        foreach($orders as $order) :
            $order_products_array = [];
            $order_products = DB::table('order_products')->select('product_id','product_title','quantity','amount','weight','sku')->where('order_id',$order->id)->get(); 
            foreach($order_products as $order_product) :
                if($order_product){
                    $image = DB::table('product_images')->select('name')->where('product_id',$order_product->product_id)->first();
                    $image_name = null;
                    if($image){
                        $image_name = $image->name;
                    }
                }
                $order_products_array[] = ['product_id'=>$order_product->product_id,'product_title'=>$order_product->product_title,'quantity'=>$order_product->quantity,'amount'=>$order_product->amount,'weight'=>$order_product->weight,'sku'=>$order_product->sku,'image_name'=>$image_name];
            endforeach;
            
            $data[] = ["order_id"=>$order->id,"order_encrypt_key"=>$order->order_encrypt_key,'order_products'=>$order_products_array, "amount"=> $order->amount, "sub_total"=>$order->sub_total,'order_status_name'=>$order->order_status_name,"order_status_id"=>$order->order_status_id, "order_created_at"=>$order->created_at, "order_status_updated_at"=>$order->updated_at, "shipping_update_at"=> $order->shipping_update_at, "shipping_status"=> $order->shipping_status, "shipping_status_id"=> $order->shipping_status_id];
        endforeach;
        return response()->json([ 'success' => true, 'data'    => $data ], 200);
    }
    
    
    public function orderDetails(Request $request)
    {
        $user_id = null;
        if(auth('sanctum')->check()){
            $user = auth('sanctum')->user();
            $user_id = $user->id;
        } else {
             return response()->json(['success' => false,'message' => 'Please login to access myaccount.'], 200);
        }
        $validator = Validator::make($request->all(), [
            'order_encrypt_key' => 'required'
        ]);
        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors());       
        }
        $order_encrypt_key = $request->order_encrypt_key;
        $order = DB::table('orders as o')->select('o.id','o.order_encrypt_key','o.user_id','o.name','o.email','o.amount','o.order_status_id','o.created_at','o.updated_at','o.sub_total','os.name as order_status_name')->join('order_statuses as os', 'os.id', '=', 'o.order_status_id')->where(['o.user_id'=>$user_id,'o.order_encrypt_key'=>$order_encrypt_key])->first();
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
        $shipping = DB::table('order_shippings as os')->select('os.name','os.amount','os.order_id','os.shipping_status_id','ss.name as shipping_status_name','os.created_at','os.updated_at')->join('shipping_statuses as ss','ss.id', '=', 'os.shipping_status_id')->where('os.order_id',$order->id)->first();
        $shipping_address = DB::table('order_shipping_addresses')->where('order_id',$order->id)->first();
        $orderlineitems = DB::table('order_lineitems')->select('order_id','title','amount')->where('order_id',$order->id)->orderBy('weight')->get();
        return response()->json(['status'=>true,'order'=>$order,'products'=>$products,'payment'=>$payment,'shipping'=>$shipping,'shipping_address'=>$shipping_address,'orderlineitems'=>$orderlineitems], 200);
    }
    
    public function wishlist(Request $request)
    {
        $user_id = null;
        if(auth('sanctum')->check()){
            $user = auth('sanctum')->user();
            $user_id = $user->id;
        } else {
             return response()->json(['success' => false,'message' => 'Please login to access myaccount.'], 200);
        }
        $orders = DB::table('wishlist as w')->select('w.id as wishlist_id','w.product_title','w.product_slug','w.weight','w.weight_id','w.product_id','pw.sell_price','pw.list_price','product_images.name as image_name')
        ->leftJoin('product_weights as pw','pw.id','=','w.weight_id')->where('user_id',$user_id)->leftJoin('product_images', function ($imgjoin) {
            $imgjoin->on('product_images.id', '=', DB::raw('(SELECT id FROM product_images WHERE product_images.product_id = w.product_id LIMIT 1)'));
        })->get();
        $response = [
            'success' => true,
            'data'    => $orders
        ];
        return response()->json($response, 200);
    }
    
    public function addWishlist(Request $request){
        $user_id = null;
        if(auth('sanctum')->check()){
            $user = auth('sanctum')->user();
            $user_id = $user->id;
        } else {
             return response()->json(['success' => false,'message' => 'Please login to add product wishlist.'], 200);
        }
        $validator = Validator::make($request->all(), [
            'product_id' => 'required'
        ]);
   
        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors());       
        }
        
        if(DB::table('wishlist')->where(['user_id'=>$user_id,'product_id'=>$request->product_id])->exists()){
            return response()->json(['success' => false,'message' => 'Product already wishlisted.'], 404);
        }
        
        $product = DB::table('products')->where('id',$request->product_id)->first();
        if($product == null){
            return response()->json(['success' => false,'message' => 'Product not found.'], 404);
        }
        $weight = DB::table('product_weights')->where(['product_id'=>$request->product_id])->first();
        // if($weight == null){
        //     return response()->json(['success' => false,'message' => 'Product weight not found.'], 404);
        // }
        $insert['user_id'] = $user_id;
        $insert['product_title'] = $product->title;
        $insert['product_slug'] = $product->slug;
        $insert['product_id'] = $product->id;
        $insert['weight_id'] = $weight->id ?? null;
        $insert['weight'] = $weight->name ?? null;
        $insert['created_at'] = date('Y-m-d H:i:s');
        $id = DB::table('wishlist')->insertGetId($insert);
        if($id){
            return response()->json(['success' => true,'message' => 'Product added to wishlist successfully.'], 200);
        } else {
             return response()->json(['success' => false,'message' => 'Unable to add wishlist.'], 500);
        }
    }
    public function deleteWishlistById(Request $request)
    {
        $user_id = null;
        if(auth('sanctum')->check()){
            $user = auth('sanctum')->user();
            $user_id = $user->id;
        } else {
             return response()->json(['success' => false,'message' => 'Please login to access myaccount.'], 200);
        }
        $validator = Validator::make($request->all(), [
            'wishlist_id' => 'required'
        ]);
        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors());       
        }
        if(DB::table('wishlist')->where(['id'=>$request->wishlist_id,'user_id'=>$user_id])->delete()){
            return response()->json(['success'=>true,'message'=>'Product deleted from wishlist successfully.'],200);
        }else{
            return response()->json(['success'=>false,'message'=>'Product not found.'],404);
        }
    }
    public function addresses(Request $request)
    {
        $user_id = null;
        if(auth('sanctum')->check()){
            $user = auth('sanctum')->user();
            $user_id = $user->id;
        } else {
             return response()->json(['success' => false,'message' => 'Please login to access myaccount.'], 200);
        }
        $addresses = DB::table('addresses')->where('user_id',$user_id)->get();
        return response()->json(['success'=>true,'data'=>$addresses],200);
    }
    public function editAddressById(Request $request)
    {
        $user_id = null;
        if(auth('sanctum')->check()){
            $user = auth('sanctum')->user();
            $user_id = $user->id;
        } else {
             return response()->json(['success' => false,'message' => 'Please login to access myaccount.'], 200);
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
    
    public function getAddressById(Request $request)
    {
        $user_id = null;
        if(auth('sanctum')->check()){
            $user = auth('sanctum')->user();
            $user_id = $user->id;
        } else {
             return response()->json(['success' => false,'message' => 'Please login to access myaccount.'], 200);
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
    
    public function deleteAddressById(Request $request)
    {
        $user_id = null;
        if(auth('sanctum')->check()){
            $user = auth('sanctum')->user();
            $user_id = $user->id;
        } else {
             return response()->json(['success' => false,'message' => 'Please login to access myaccount.'], 200);
        }
        $validator = Validator::make($request->all(), [
            'address_id' => 'required'
        ]);
        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors());       
        }
        if(DB::table('addresses')->where(['id'=>$request->address_id,'user_id'=>$user_id])->delete()){
            return response()->json(['success'=>true,'message'=>'Address deleted successfully.'],200);
        }else{
            return response()->json(['success'=>false,'message'=>'Record not found.'],404);
        }
    }
    public function updatePassword(Request $request)
    {
        $user_id = null;
        if(auth('sanctum')->check()){
            $user = auth('sanctum')->user();
            $user_id = $user->id;
        } else {
             return response()->json(['success' => false,'message' => 'Please login to access myaccount.'], 200);
        }
        $input = $request->all();
        $validator = Validator::make($input, [
            'current_password' => 'required',
            'new_password' => 'required|string|min:8|confirmed|different:current_password',
        ], [
            'new_password.confirmed' => 'New password and confirm password are not matching.',
            'new_password.different' => 'New password and current password should not be same.',
        ]);
        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors());       
        }
        $user = DB::table('users')->where('id',$user_id)->first();
        if (Hash::check($request->get('new_password'), $user->password)) {
            return response()->json(['success'=>false,'message'=>'New Password and Current Password should not be same.'],422);
        }
        if (Hash::check($request->get('current_password'), $user->password)) {
            $new_password = Hash::make($request->get('new_password'));
            if(DB::table('users')->where('id',$user_id)->update(['password'=>$new_password,'updated_at'=>date('Y-m-d H:i:s')])){
                return response()->json(['success'=>true,'message'=>'Password changed successfully.'],200);
            }
            return response()->json(['success'=>false,'message'=>'Some error occured. Please try again later.'],422);
        } else {
            return response()->json(['success'=>false,'message'=>'Current password is incorrect.'],422);
        }
    }
    public function profileUpdate(Request $request)
    {
        $user_id = null;
        if(auth('sanctum')->check()){
            $user = auth('sanctum')->user();
            $user_id = $user->id;
        } else {
             return response()->json(['success' => false,'message' => 'Please login to access myaccount.'], 200);
        }
        $validator = Validator::make($request->all(), [
            'name' => "required|string|max:255",
            'email' => "required|email|max:191|unique:users,email,$user_id",
            'mobile' => "required|digits:10|unique:users,mobile,$user_id",
        ]);
        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors());       
        }
        
        DB::table('users')->where('id',$user_id)->update([
            'name' => trim($request->name),
            'email' => strtolower(trim($request->email)),
            'mobile' => $request->mobile,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        return response()->json(['success'=>true,'message'=>'Profile updated successfully.'],200);
    }
    
    public function cancelorder(Request $request)
    {
        $user_id = null;
        if(auth('sanctum')->check()){
            $user = auth('sanctum')->user();
            $user_id = $user->id;
        } else {
             return response()->json(['success' => false,'message' => 'Please login to access myaccount.'], 200);
        }
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|integer',
        ]);
        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors());
        }
        $cancelledOrderStatusId = $this->statusIdByName('order_statuses', 'Cancelled', 5);
        $cancelledShippingStatusId = $this->statusIdByName('shipping_statuses', 'Cancelled', 4);
        if($order = DB::table('orders')->where(['id'=>$request->order_id,'user_id'=>$user_id])->first()){
            if((int) $order->order_status_id === $cancelledOrderStatusId){
                return response()->json(['success'=>true,'message'=>'Order has already cancelled.']);
            }
        }else{
            return response()->json(['success'=>false,'message'=>'Page not found.']);
        }

        $now = date('Y-m-d H:i:s');
        DB::beginTransaction();
        try {
            DB::table('orders')->where(['id'=>$request->order_id,'user_id'=>$user_id])->update([
                'order_status_id' => $cancelledOrderStatusId,
                'updated_at' => $now,
            ]);
            DB::table('order_shippings')->where('order_id',$request->order_id)->update([
                'shipping_status_id' => $cancelledShippingStatusId,
                'updated_at' => $now,
            ]);
            $this->releaseOrderStock((int) $request->order_id);
            $this->logWorkflowEvent((int) $request->order_id, 'customer_cancelled', 'Active', 'Cancelled', 'Customer cancelled the order.');
            DB::commit();

            return response()->json(['success'=>true,'message'=>'Order Cancelled Successfully.']);
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);
            return response()->json(['success'=>false,'message'=>'Unable to Cancel the order.']);
        }
    }

    private function statusIdByName(string $table, string $name, int $fallback): int
    {
        return (int) (DB::table($table)->whereRaw('LOWER(name) = ?', [strtolower($name)])->value('id') ?: $fallback);
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

    private function logWorkflowEvent(int $orderId, string $eventType, $fromValue = null, $toValue = null, ?string $note = null): void
    {
        if(! \Illuminate\Support\Facades\Schema::hasTable('order_workflow_events')){
            return;
        }

        DB::table('order_workflow_events')->insert([
            'order_id' => $orderId,
            'admin_id' => null,
            'event_type' => $eventType,
            'from_value' => $fromValue,
            'to_value' => $toValue,
            'note' => $note,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
