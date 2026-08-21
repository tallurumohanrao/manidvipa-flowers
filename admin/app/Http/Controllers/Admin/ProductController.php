<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\StoreProductRequest;
use App\Traits\RedirectTrait;
use App\Traits\StoreImageTrait;
use Symfony\Component\HttpFoundation\Response;
use Gate,View,Str,DB,Storage,Cache;

class ProductController extends Controller
{
    use StoreImageTrait,RedirectTrait;
    public function __construct()
    {
        $this->module = 'products';
        View::share ( 'module', $this->module );
    }

    private function clearStorefrontCache(): void
    {
        Cache::flush();
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
        $query = DB::table('products');
        if ($request->filled('title')) {
            $query->where('title', 'like', '%' . $request->title . '%');
        }
        if ($request->filled('sku')) {
            $query->where('sku', 'like', '%' . $request->sku . '%');
        }
        if ($request->filled('category')) {
            $query->whereExists( function ($cq) use ($request) {
                $cq->select(DB::raw(1))
                ->from('category_product')
                ->whereRaw('category_product.product_id=products.id')
                ->where('category_product.category_id', '=', $request->category);
            });
            #$query->where('category_id', $request->category );
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        $data = $query->orderByDesc('id')->paginate($perPage)->withQueryString();
        $categories = DB::table('categories')->get()->pluck('title','id');
        return view('admin.'.$this->module.'.index', compact('data','categories'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        abort_if(Gate::denies($this->module.'_create'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        $categories = DB::table('categories')->get()->pluck('title','id');
        $row = $selected = [];
        return view('admin.'.$this->module.'.create', compact('row','categories','selected'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreProductRequest $request)
    {
        abort_if(Gate::denies($this->module.'_create'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        $formInput = $request->except('product_category','_token','FormButton','seo');
        $seoInput = $request->seo;
        $date = date('Y-m-d H:i:s');
        $formInput['slug'] = $url = Str::slug($seoInput['url']);
        $formInput['created_at'] = $date;
        $id = DB::table('products')->insertGetId($formInput);
        foreach($request->product_category as $categoryId){
            DB::table('category_product')->insert(['product_id'=>$id,'category_id'=>$categoryId]);
        }

        DB::table('seo_urls')->insert(['url'=>'/'.$url,'page_title'=>$seoInput['page_title'],'meta_description'=>$seoInput['meta_description'],'robots'=>$seoInput['robots'],'created_at'=>$date]);

        $this->clearStorefrontCache();

        return $this->redirectAfterSave($request->FormButton, $id);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Admin\Category  $category
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Admin\Category  $category
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        abort_if(Gate::denies($this->module.'_edit'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        $product = DB::table('products')->where('id',$id)->first();
        $categories = DB::table('categories')->get()->pluck('title','id');
        $selected = DB::table('category_product')->where('product_id',$id)->get()->pluck('category_id')->toArray();
        $seo = DB::table('seo_urls')->where('url','/'.$product->slug)->first();
        return view('admin.'.$this->module.'.edit', ['row' => $product,'categories'=>$categories,'selected'=>$selected,'seo'=>$seo]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Admin\Category  $category
     * @return \Illuminate\Http\Response
     */
    public function update(StoreProductRequest $request, $id)
    {
        abort_if(Gate::denies($this->module.'_edit'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        $formInput = $request->except('product_category','seo','_token','_method','FormButton');
        $seoInput = $request->seo;

        $formInput['slug'] = $url = Str::slug($seoInput['url']);
        $formInput['updated_at'] = $date = date('Y-m-d H:i:s');
        DB::table('products')->where('id',$id)->update($formInput);
        DB::table('category_product')->where('product_id',$id)->whereNotIn('category_id',$request->product_category)->delete();
        foreach($request->product_category as $categoryId){
            if(DB::table('category_product')->where(['product_id'=> $id,'category_id'=>$categoryId])->doesntExist()){
                DB::table('category_product')->insert(['product_id'=>$id,'category_id'=>$categoryId]);
            }
        }

        if(DB::table('seo_urls')->where('url',$seoInput['old_url'])->doesntExist()){
            DB::table('seo_urls')->insert(['url'=>'/'.$url,'page_title'=>$seoInput['page_title'],'meta_description'=>$seoInput['meta_description'],'robots'=>$seoInput['robots'],'created_at'=>$date]);
        }else{
            DB::table('seo_urls')->where('url',$seoInput['old_url'])->update(['url'=>'/'.$url,'page_title'=>$seoInput['page_title'],'meta_description'=>$seoInput['meta_description'],'robots'=>$seoInput['robots'],'updated_at'=>$date]);
        }

        $this->clearStorefrontCache();

        return $this->redirectAfterSave($request->FormButton, $id);
    }

    public function reviews($id)
    {
        abort_if(Gate::denies($this->module.'_view'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        $review_ratings = DB::table('review_ratings')->where('product_id',$id)->get();
        $product = DB::table('products')->where('id',$id)->first();
        return view('admin.'.$this->module.'.product_reviews',compact('id','product','review_ratings'));
    }

    public function images($id)
    {
        abort_if(Gate::denies($this->module.'_edit'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        $images = DB::table('product_images')->where('product_id',$id)->orderBy('priority')->get();
        $product = DB::table('products')->where('id',$id)->first();
        return view('admin.'.$this->module.'.product_images',compact('id','product','images'));
    }

    public function storeImage(Request $request,$id)
    {
        abort_if(Gate::denies($this->module.'_edit'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        if($request->isMethod('POST')){
            $insert['name'] = $this->verifyAndStoreImage($request, 'file', 'products');
            $insert['product_id'] = $id;
            $insert['status'] = 1;
            $insert['created_at'] = date('Y-m-d H:i:s');
            $insert['updated_at'] = date('Y-m-d H:i:s');
            DB::table('product_images')->insert($insert);
            $this->clearStorefrontCache();
            return response()->json(['success'=>'File Uploaded Successfully']);
        }
        return view('admin.'.$this->module.'.show',compact('product'));
    }

    public function sizes($id)
    {
        abort_if(Gate::denies($this->module.'_edit'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        $selected = DB::table('product_sizes')->where('product_id',$id)->get()->pluck('id')->toArray();
        $selectboxsizes = DB::table('sizes')->get()->pluck('name','name')->toArray();
        $sizes = DB::table('product_sizes')->where('product_id',$id)->get();
        $product = DB::table('products')->where('id',$id)->first();
        $shtml = '<option>-- Select --</option>';
        foreach($selectboxsizes as $key=>$value){
            $shtml .= '<option name="'.$value.'">'.$value.'</option>';
        }
        return view('admin.'.$this->module.'.product_sizes',compact('product','sizes','selected','selectboxsizes','shtml'));
    }

    public function storeSizes(Request $request,$id)
    {
        abort_if(Gate::denies($this->module.'_edit'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        if($request->isMethod('POST')){
            foreach($request->Size as $size){
                $insert['name'] = $size['name'];
                $insert['sell_price'] = $size['sell_price'];
                $insert['list_price'] = $size['list_price'];
                $insert['cost_price'] = $size['cost_price'];
                $insert['status'] = $size['status']?$size['status']:0;
                if(DB::table('product_sizes')->where('id',$size['id'])->first()){
                    $insert['updated_at'] = date('Y-m-d H:i:s');
                    DB::table('product_sizes')->where('id',$size['id'])->update($insert);
                }else{
                    $insert['product_id'] = $id;
                    $insert['created_at'] = date('Y-m-d H:i:s');
                    DB::table('product_sizes')->insert($insert);
                }
            }
            $this->clearStorefrontCache();
            return back()->with('success','Sizes saved successfully.');
        }
        return view('admin.'.$this->module.'.show',compact('product'));
    }

    public function productsSizeDestroy($id)
    {
        abort_if(Gate::denies($this->module.'_delete'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
            if(DB::table('product_sizes')->where('id',$id)->delete() == 1){
            $this->clearStorefrontCache();
            return response()->json(['success'=>true, 'message' => 'Deleted successfully.']);
        }else{
            return response()->json(['success'=>false, 'message' => 'An unexpected error has occurred.']);
        }
    }

    public function productsWeightDestroy($id)
    {
        abort_if(Gate::denies($this->module.'_delete'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
            if(DB::table('product_weights')->where('id',$id)->delete() == 1){
            $this->clearStorefrontCache();
            return response()->json(['success'=>true, 'message' => 'Deleted successfully.']);
        }else{
            return response()->json(['success'=>false, 'message' => 'An unexpected error has occurred.']);
        }
    }

    public function weights($id)
    {
        abort_if(Gate::denies($this->module.'_edit'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        $selected = DB::table('product_weights')->where('product_id',$id)->get()->pluck('id')->toArray();
        $selectboxweights = DB::table('weights')->get()->pluck('name','name')->toArray();
        $weights = DB::table('product_weights')->where('product_id',$id)->get();
        $product = DB::table('products')->where('id',$id)->first();
        $shtml = '<option value="">-- Select --</option>';
        foreach($selectboxweights as $key=>$value){
            $shtml .= '<option name="'.$value.'">'.$value.'</option>';
        }
        return view('admin.'.$this->module.'.product_weights',compact('product','weights','selected','selectboxweights','shtml'));
    }
    
    public function storeWeights(Request $request,$id)
    {
        abort_if(Gate::denies($this->module.'_edit'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        if($request->isMethod('POST') AND $request->Weight){ 
            foreach($request->Weight as $weight){
                $insert['name'] = $weight['name'];
                $insert['sell_price'] = $weight['sell_price'];
                $insert['list_price'] = $weight['list_price'];
                $insert['cost_price'] = $weight['cost_price'];
                $insert['qty'] = $weight['qty'];
                $insert['stock'] = $weight['stock'] ?? 0;
                $insert['status'] = $weight['status']?$weight['status']:0;
                if(DB::table('product_weights')->where('id',$weight['id'])->first()){
                    $insert['updated_at'] = date('Y-m-d H:i:s');
                    DB::table('product_weights')->where('id',$weight['id'])->update($insert);
                }else{
                    $insert['product_id'] = $id;
                    $insert['created_at'] = date('Y-m-d H:i:s');
                    DB::table('product_weights')->insert($insert);
                }
            }
            $this->clearStorefrontCache();
            return back()->with('success','Weights saved successfully.');
        }
        return back()->with('fail','No modifications applied..');
    }

    public function productsImageDestroy($id)
    {
        abort_if(Gate::denies($this->module.'_delete'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        $query = DB::table('product_images')->where('id',$id);
        $image = $query->first();
        if(!$image){
            return response()->json(['success'=>false, 'message' => 'An unexpected error has occurred.']);
        }
        Storage::delete('public/products/'.$image->name);
        Storage::delete('public/products/100X100/'.$image->name);
        Storage::delete('public/products/280X280/'.$image->name);
        if($query->delete() == 1){
            $this->clearStorefrontCache();
            return response()->json(['success'=>true, 'message' => 'Deleted successfully.']);
        }else{
            return response()->json(['success'=>false, 'message' => 'An unexpected error has occurred.']);
        }
    }

    public function productsImageUpdateStatus(Request $request, $id)
    {
        abort_if(Gate::denies($this->module.'_edit'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        if($request->ajax() && $request->isMethod('PATCH')){
            $value = $request->status == 1 ?:0;
            $result = DB::table('product_images')->where('id',$id)->update(['status'=>$value]);
            if($result){
                $this->clearStorefrontCache();
                $status= $value == 1 ?'enabled':'disabled';
                return response()->json(['status'=>'success','message'=>"Status $status successfully."]);
            }
        }
    }

    public function productImageUpdateSort(Request $request)
    {   
        $i = 1;
        $positions = (array) $request->input('position', []);
        if(empty($positions)) {
            return response()->json(['success'=>false, 'message' => 'No image order received.']);
        }
        foreach ($positions as $order) {  
            DB::table('product_images')->where('id',$order)->update(['priority' => $i]);
            $i++;
        }
        $this->clearStorefrontCache();
        return response()->json(['success'=>true, 'message' => 'Update successfully.']);
    }

    public function productsReviewUpdateStatus(Request $request, $id)
    {
        abort_if(Gate::denies($this->module.'_edit'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        if($request->ajax() && $request->isMethod('PATCH')){
            $value = $request->status == 1 ?:0;
            $result = DB::table('review_ratings')->where('id',$id)->update(['status'=>$value,'updated_at'=>date('Y-m-d H:i:s')]);
            if($result){
                $this->clearStorefrontCache();
                $status= $value == 1 ?'enabled':'disabled';
                return response()->json(['status'=>'success','message'=>"Status $status successfully."]);
            }
        }
    }

    public function updateStatus(Request $request, $id)
    {
        abort_if(Gate::denies($this->module.'_edit'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        if($request->ajax() && $request->isMethod('PATCH')){
            if(DB::table('products')->where('id',$id)->update(['status'=>$request->status,'updated_at'=>date('Y-m-d H:i:s')])){
                $this->clearStorefrontCache();
                $status=$request->status==1?'enabled':'disabled';
                return response()->json(['status'=>'success','message'=>"Status $status successfully."]);
            }
        }
    }
    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Admin\Category  $category
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        abort_if(Gate::denies($this->module.'_delete'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        $result = DB::table('products')->where('id',$id)->delete();
        if($result == 1) {
            $this->clearStorefrontCache();
            return response()->json(['success'=>true, 'message' => 'Deleted successfully.']);
        }
        return response()->json(['success'=>false, 'message' => 'An unexpected error has occurred.']);
    }
    public function productsreviewsDestroy ($id)
    {
        abort_if(Gate::denies($this->module.'_delete'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        $result = DB::table('review_ratings')->where('id',$id)->delete();
        if($result == 1)
        return response()->json(['success'=>true, 'message' => 'Deleted successfully.']);
        else
        return response()->json(['success'=>false, 'message' => 'An unexpected error has occurred.']);
    }

    public function massDestroy(Request $request)
    {
        abort_if(Gate::denies($this->module.'_delete'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        $ids = explode(',',$request->ids);
        $result = 0;
        foreach($ids as $id) :
            $result = DB::table('products')->where('id',$id)->delete();
        endforeach;

        if($result == 1) {
            $this->clearStorefrontCache();
            return response()->json(['success'=>true, 'message' => 'Deleted successfully.']);
        }
        return response()->json(['success'=>false, 'message' => 'An unexpected error has occurred.']);
    }

}
