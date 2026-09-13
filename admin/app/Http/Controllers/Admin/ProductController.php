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

    private function normalizeProductSlugFromSeoInput(?string $value, ?string $fallback = null): string
    {
        $path = parse_url((string) $value, PHP_URL_PATH) ?: (string) $value;
        $path = trim($path, '/');

        foreach (['product-details/', 'productDetails/', 'products/'] as $prefix) {
            if (Str::startsWith($path, $prefix)) {
                $path = Str::after($path, $prefix);
                break;
            }
        }

        $slug = Str::slug($path);

        return $slug !== '' ? $slug : Str::slug($fallback ?: 'product');
    }

    private function productSeoPath(string $slug): string
    {
        return '/product-details/'.trim($slug, '/');
    }

    private function productSeoLookupPaths(?string $slug): array
    {
        $slug = trim((string) $slug, '/');

        if ($slug === '') {
            return [];
        }

        return array_values(array_unique([
            $this->productSeoPath($slug),
            '/productDetails/'.$slug,
            '/'.$slug,
        ]));
    }

    private function findProductSeo(?string $slug): ?object
    {
        $paths = $this->productSeoLookupPaths($slug);

        if (empty($paths)) {
            return null;
        }

        $rows = DB::table('seo_urls')
            ->whereIn('url', $paths)
            ->get()
            ->keyBy('url');

        foreach ($paths as $path) {
            if ($rows->has($path)) {
                return $rows->get($path);
            }
        }

        return null;
    }

    private function productSeoPayload(array $seoInput, string $slug, string $date, bool $create = false): array
    {
        $payload = [
            'url' => $this->productSeoPath($slug),
            'page_title' => $seoInput['page_title'] ?? null,
            'meta_keywords' => $seoInput['meta_keywords'] ?? null,
            'meta_description' => $seoInput['meta_description'] ?? null,
            'schema_markup' => $seoInput['schema_markup'] ?? null,
            'robots' => $seoInput['robots'] ?? null,
            'updated_at' => $date,
        ];

        if ($create) {
            $payload['created_at'] = $date;
        }

        return $payload;
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
        $categoryNames = DB::table('category_product')
            ->join('categories', 'categories.id', '=', 'category_product.category_id')
            ->select('category_product.product_id', DB::raw("GROUP_CONCAT(DISTINCT categories.title ORDER BY categories.title SEPARATOR ', ') AS category_titles"))
            ->groupBy('category_product.product_id');

        $query = DB::table('products')
            ->leftJoinSub($categoryNames, 'product_categories', function ($join) {
                $join->on('product_categories.product_id', '=', 'products.id');
            })
            ->select(
                'products.*',
                'product_categories.category_titles'
            );
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
            $query->where('products.status', $request->status);
        }
        $data = $query->orderByDesc('products.id')->paginate($perPage)->withQueryString();
        $this->attachDisplayQuantities($data);
        $categories = DB::table('categories')->get()->pluck('title','id');
        return view('admin.'.$this->module.'.index', compact('data','categories'));
    }

    private function attachDisplayQuantities($products): void
    {
        $productIds = $products->getCollection()->pluck('id')->filter()->values()->all();

        if (empty($productIds)) {
            return;
        }

        $weightsByProduct = DB::table('product_weights')
            ->select('product_id', 'name', 'qty')
            ->whereIn('product_id', $productIds)
            ->where('status', 1)
            ->where('stock', 1)
            ->where('qty', '>', 0)
            ->orderBy('id')
            ->get()
            ->groupBy('product_id');

        $products->setCollection($products->getCollection()->map(function ($product) use ($weightsByProduct) {
            $product->display_quantity = $this->resolveDisplayQuantity(
                trim((string) ($product->qty ?? '')),
                $weightsByProduct->get($product->id, collect())
            );

            return $product;
        }));
    }

    private function resolveDisplayQuantity(string $productQuantity, $weights): string
    {
        if ($productQuantity !== '' && preg_match('/[A-Za-z]/', $productQuantity)) {
            return $productQuantity;
        }

        $preferredWeight = $this->preferredQuantityWeight($weights);

        if ($preferredWeight) {
            return $this->formatDisplayQuantity($preferredWeight->qty, $preferredWeight->name);
        }

        return $productQuantity !== '' ? $productQuantity : '-';
    }

    private function preferredQuantityWeight($weights): ?object
    {
        foreach (['bunch', 'stem', 'piece', 'each one', '1 kg', 'packet'] as $preferredUnit) {
            $match = $weights->first(function ($weight) use ($preferredUnit) {
                $name = $this->normalizeWeightName($weight->name ?? '');

                return $preferredUnit === '1 kg'
                    ? preg_match('/^1\s*(kg|kgs|kilogram|kilograms)$/', $name)
                    : str_contains($name, $preferredUnit);
            });

            if ($match) {
                return $match;
            }
        }

        return $weights->first();
    }

    private function formatDisplayQuantity($quantity, string $weightName): string
    {
        $quantityNumber = is_numeric($quantity) ? (float) $quantity : 0.0;
        $quantityText = rtrim(rtrim(number_format($quantityNumber, 2, '.', ''), '0'), '.') ?: '0';
        $name = $this->normalizeWeightName($weightName);

        if (str_contains($name, 'bunch')) {
            return $quantityText.' '.(abs($quantityNumber - 1.0) < 0.00001 ? 'bunch' : 'bunches');
        }

        if (str_contains($name, 'stem')) {
            return $quantityText.' '.(abs($quantityNumber - 1.0) < 0.00001 ? 'stem' : 'stems');
        }

        if (str_contains($name, 'piece') || str_contains($name, 'each one') || $name === 'each') {
            return $quantityText.' '.(abs($quantityNumber - 1.0) < 0.00001 ? 'piece' : 'pieces');
        }

        if (str_contains($name, 'packet')) {
            return $quantityText.' '.(abs($quantityNumber - 1.0) < 0.00001 ? 'packet' : 'packets');
        }

        if (preg_match('/^1\s*(kg|kgs|kilogram|kilograms)$/', $name)) {
            return $quantityText.' KG';
        }

        if (preg_match('/^1\s*(ltr|liter|litre|liters|litres|l)$/', $name)) {
            return $quantityText.' LTR';
        }

        if (preg_match('/\b(kg|kgs|kilogram|kilograms|gram|grams|grm|gm|g|ml|ltr|liter|litre|liters|litres|l)\b/', $name)) {
            return $quantityText.' '.(abs($quantityNumber - 1.0) < 0.00001 ? 'pack' : 'packs').' of '.$weightName;
        }

        return $quantityText;
    }

    private function normalizeWeightName(string $name): string
    {
        return Str::of($name)
            ->lower()
            ->replace(['.', '_', '-'], ' ')
            ->squish()
            ->toString();
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
        $formInput['slug'] = $url = $this->normalizeProductSlugFromSeoInput($seoInput['url'] ?? null, $request->title);
        $formInput['created_at'] = $date;
        $id = DB::table('products')->insertGetId($formInput);
        foreach($request->product_category as $categoryId){
            DB::table('category_product')->insert(['product_id'=>$id,'category_id'=>$categoryId]);
        }

        DB::table('seo_urls')->insert($this->productSeoPayload($seoInput, $url, $date, true));

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
        $seo = $this->findProductSeo($product->slug ?? null);
        $seoUrl = $product->slug ?? null;
        $seoOldUrl = $seo->url ?? null;

        return view('admin.'.$this->module.'.edit', ['row' => $product,'categories'=>$categories,'selected'=>$selected,'seo'=>$seo,'seoUrl'=>$seoUrl,'seoOldUrl'=>$seoOldUrl]);
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
        $product = DB::table('products')->where('id', $id)->first();
        abort_if(! $product, Response::HTTP_NOT_FOUND);

        $oldSlug = $product->slug ?? null;
        $formInput['slug'] = $url = $this->normalizeProductSlugFromSeoInput($seoInput['url'] ?? null, $request->title);
        $formInput['updated_at'] = $date = date('Y-m-d H:i:s');
        DB::table('products')->where('id',$id)->update($formInput);
        DB::table('category_product')->where('product_id',$id)->whereNotIn('category_id',$request->product_category)->delete();
        foreach($request->product_category as $categoryId){
            if(DB::table('category_product')->where(['product_id'=> $id,'category_id'=>$categoryId])->doesntExist()){
                DB::table('category_product')->insert(['product_id'=>$id,'category_id'=>$categoryId]);
            }
        }

        $seoLookupPaths = array_values(array_unique(array_filter(array_merge(
            [$seoInput['old_url'] ?? null],
            $this->productSeoLookupPaths($oldSlug),
            $this->productSeoLookupPaths($url)
        ))));
        $seoPayload = $this->productSeoPayload($seoInput, $url, $date);
        $existingSeo = DB::table('seo_urls')->whereIn('url', $seoLookupPaths)->first(['id']);

        if(! $existingSeo){
            DB::table('seo_urls')->insert($this->productSeoPayload($seoInput, $url, $date, true));
        }else{
            DB::table('seo_urls')->where('id',$existingSeo->id)->update($seoPayload);
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
            $escapedValue = e($value);
            $shtml .= '<option value="'.$escapedValue.'">'.$escapedValue.'</option>';
        }
        return view('admin.'.$this->module.'.product_sizes',compact('product','sizes','selected','selectboxsizes','shtml'));
    }

    public function storeSizes(Request $request,$id)
    {
        abort_if(Gate::denies($this->module.'_edit'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        abort_if(! DB::table('products')->where('id', $id)->exists(), Response::HTTP_NOT_FOUND);
        $validated = $request->validate([
            'Size' => ['required', 'array', 'min:1'],
            'Size.*.id' => ['nullable', 'integer'],
            'Size.*.name' => ['required', 'string', 'max:100'],
            'Size.*.sell_price' => ['required', 'numeric', 'min:0'],
            'Size.*.list_price' => ['required', 'numeric', 'min:0'],
            'Size.*.cost_price' => ['nullable', 'numeric', 'min:0'],
            'Size.*.status' => ['required', 'in:0,1'],
        ]);
        if($request->isMethod('POST')){
            foreach($validated['Size'] as $size){
                $insert['name'] = $size['name'];
                $insert['sell_price'] = $size['sell_price'];
                $insert['list_price'] = $size['list_price'];
                $insert['cost_price'] = $size['cost_price'] ?? null;
                $insert['status'] = (int) $size['status'];
                $sizeId = $size['id'] ?? null;
                if($sizeId && DB::table('product_sizes')->where('id',$sizeId)->where('product_id', $id)->exists()){
                    $insert['updated_at'] = date('Y-m-d H:i:s');
                    DB::table('product_sizes')->where('id',$sizeId)->where('product_id', $id)->update($insert);
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
            $escapedValue = e($value);
            $shtml .= '<option value="'.$escapedValue.'">'.$escapedValue.'</option>';
        }
        return view('admin.'.$this->module.'.product_weights',compact('product','weights','selected','selectboxweights','shtml'));
    }
    
    public function storeWeights(Request $request,$id)
    {
        abort_if(Gate::denies($this->module.'_edit'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        $product = DB::table('products')->where('id', $id)->first();
        abort_if(! $product, Response::HTTP_NOT_FOUND);

        $validated = $request->validate([
            'Weight' => ['required', 'array', 'min:1'],
            'Weight.*.id' => ['nullable', 'integer'],
            'Weight.*.name' => ['required', 'string', 'max:100'],
            'Weight.*.sell_price' => ['required', 'numeric', 'min:0'],
            'Weight.*.list_price' => ['required', 'numeric', 'min:0'],
            'Weight.*.cost_price' => ['nullable', 'numeric', 'min:0'],
            'Weight.*.qty' => ['nullable', 'numeric', 'min:0'],
            'Weight.*.stock' => ['nullable', 'boolean'],
            'Weight.*.status' => ['required', 'in:0,1'],
        ]);

        if($request->isMethod('POST')){
            foreach($validated['Weight'] as $weight){
                $insert['name'] = $weight['name'];
                $insert['sell_price'] = $weight['sell_price'];
                $insert['list_price'] = $weight['list_price'];
                $insert['cost_price'] = $weight['cost_price'] ?? null;
                $insert['qty'] = $weight['qty'] ?? 0;
                $insert['stock'] = $weight['stock'] ?? 0;
                $insert['status'] = (int) $weight['status'];
                $weightId = $weight['id'] ?? null;
                if($weightId && DB::table('product_weights')->where('id',$weightId)->where('product_id', $id)->exists()){
                    $insert['updated_at'] = date('Y-m-d H:i:s');
                    DB::table('product_weights')->where('id',$weightId)->where('product_id', $id)->update($insert);
                }else{
                    $insert['product_id'] = $id;
                    $insert['created_at'] = date('Y-m-d H:i:s');
                    DB::table('product_weights')->insert($insert);
                }
            }
            $this->clearStorefrontCache();
            return back()->with('success','Weights saved successfully.');
        }
        return back()->with('fail','No modifications applied.');
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
            $value = $request->boolean('status') ? 1 : 0;
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
        abort_if(Gate::denies($this->module.'_edit'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
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
            $value = $request->boolean('status') ? 1 : 0;
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
        $result = $this->deleteProducts([(int) $id]);
        if($result > 0) {
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
        $ids = array_values(array_filter(array_map('intval', explode(',', (string) $request->ids))));
        $result = $this->deleteProducts($ids);

        if($result > 0) {
            $this->clearStorefrontCache();
            return response()->json(['success'=>true, 'message' => 'Deleted successfully.']);
        }
        return response()->json(['success'=>false, 'message' => 'An unexpected error has occurred.']);
    }

    private function deleteProducts(array $ids): int
    {
        if (empty($ids)) {
            return 0;
        }

        $products = DB::table('products')->whereIn('id', $ids)->get(['id', 'slug']);
        if ($products->isEmpty()) {
            return 0;
        }

        $productIds = $products->pluck('id')->all();
        $images = DB::table('product_images')->whereIn('product_id', $productIds)->pluck('name')->filter()->unique();

        $deleted = DB::transaction(function () use ($products, $productIds) {
            DB::table('carts')->whereIn('product_id', $productIds)->delete();
            DB::table('featured_products')->whereIn('product_id', $productIds)->delete();
            DB::table('category_product')->whereIn('product_id', $productIds)->delete();
            DB::table('review_ratings')->whereIn('product_id', $productIds)->delete();
            DB::table('product_images')->whereIn('product_id', $productIds)->delete();
            DB::table('product_sizes')->whereIn('product_id', $productIds)->delete();
            DB::table('product_weights')->whereIn('product_id', $productIds)->delete();
            $seoPaths = $products
                ->pluck('slug')
                ->flatMap(fn ($slug) => $this->productSeoLookupPaths($slug))
                ->unique()
                ->values()
                ->all();

            if (! empty($seoPaths)) {
                DB::table('seo_urls')->whereIn('url', $seoPaths)->delete();
            }

            return DB::table('products')->whereIn('id', $productIds)->delete();
        });

        foreach ($images as $image) {
            Storage::delete([
                'public/products/'.$image,
                'public/products/100X100/'.$image,
                'public/products/280X280/'.$image,
            ]);
        }

        return $deleted;
    }

}
