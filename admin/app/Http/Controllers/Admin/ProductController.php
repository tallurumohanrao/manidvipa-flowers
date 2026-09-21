<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\StoreProductRequest;
use App\Traits\RedirectTrait;
use App\Traits\StoreImageTrait;
use App\Support\SeoRouteManager;
use App\Support\PriceVisibility;
use App\Support\SellingOption;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Gate,View,Str,DB,Storage,Cache;

class ProductController extends Controller
{
    use StoreImageTrait,RedirectTrait;
    public function __construct()
    {
        $this->module = 'products';
        View::share ( 'module', $this->module );
        View::share('priceVisibilityModes', PriceVisibility::modes());
        View::share('pricingModes', SellingOption::pricingModes());
    }

    private function clearStorefrontCache(): void
    {
        foreach (['api_featured_products', 'api_home_sections', 'home'] as $key) {
            Cache::forget($key);
        }
    }

    private function normalizeProductSlugFromSeoInput(?string $value, ?string $fallback = null): string
    {
        return SeoRouteManager::slugFromPath($value, $fallback ?: 'product');
    }

    private function productSeoPath(string $slug): string
    {
        return '/flowers/'.trim($slug, '/');
    }

    private function productSeoLookupPaths(?string $slug): array
    {
        $slug = trim((string) $slug, '/');

        if ($slug === '') {
            return [];
        }

        return array_values(array_unique([
            $this->productSeoPath($slug),
            '/product-details/'.$slug,
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
            ->where(function ($query) use ($paths) {
                $query->whereIn('url', $paths)->orWhereIn('alias', $paths);
            })
            ->get()
            ->sortBy(fn ($row) => array_search($row->alias ?: $row->url, $paths, true));

        foreach ($paths as $path) {
            $match = $rows->first(fn ($row) => $row->alias === $path || $row->url === $path);
            if ($match) {
                return $match;
            }
        }

        return null;
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

        $inventoryByProduct = DB::table('product_inventory_pools as pip')
            ->join('measurement_units as mu', 'mu.id', '=', 'pip.unit_id')
            ->select('pip.product_id', 'pip.qty', 'pip.track_stock', 'mu.singular_name', 'mu.plural_name')
            ->whereIn('pip.product_id', $productIds)
            ->where('pip.status', 1)
            ->orderBy('mu.priority')
            ->get()
            ->groupBy('product_id');

        $weightsByProduct = DB::table('product_weights')
            ->select('product_id', 'name', 'qty', 'quantity_value', 'quantity_unit')
            ->whereIn('product_id', $productIds)
            ->where('status', 1)
            ->where('stock', 1)
            ->where('qty', '>', 0)
            ->orderBy('id')
            ->get()
            ->groupBy('product_id');

        $products->setCollection($products->getCollection()->map(function ($product) use ($inventoryByProduct, $weightsByProduct) {
            $pools = $inventoryByProduct->get($product->id, collect());
            if ($pools->isNotEmpty()) {
                $product->display_quantity = $pools->map(function ($pool) {
                    if (! (int) $pool->track_stock) return 'Stock not tracked';
                    $qty = (float) $pool->qty;

                    return SellingOption::formatNumber($qty).' '.($qty === 1.0 ? $pool->singular_name : $pool->plural_name);
                })->implode(', ');
            } else {
                $product->display_quantity = $this->resolveDisplayQuantity(
                    trim((string) ($product->qty ?? '')),
                    $weightsByProduct->get($product->id, collect())
                );
            }

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
            return $this->formatDisplayQuantity($preferredWeight->qty, $preferredWeight->name, $preferredWeight->quantity_unit ?? null);
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

    private function formatDisplayQuantity($quantity, string $weightName, ?string $structuredUnit = null): string
    {
        $quantityNumber = is_numeric($quantity) ? (float) $quantity : 0.0;
        $quantityText = rtrim(rtrim(number_format($quantityNumber, 2, '.', ''), '0'), '.') ?: '0';

        if ($structuredUnit && array_key_exists($structuredUnit, SellingOption::units())) {
            return $quantityText.' '.SellingOption::unitLabel($structuredUnit, $quantityNumber);
        }

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
        $formInput['price_visible_from'] = $request->filled('price_visible_from') ? $request->price_visible_from : null;
        $seoInput = $request->seo;
        $date = date('Y-m-d H:i:s');
        $formInput['slug'] = $slug = $this->normalizeProductSlugFromSeoInput($seoInput['url'] ?? null, $request->title);
        $existingSeo = $this->findProductSeo($slug);
        SeoRouteManager::ensurePathIsAvailable(
            SeoRouteManager::normalizePath($seoInput['url'] ?? $this->productSeoPath($slug)),
            $this->productSeoPath($slug),
            $existingSeo?->id
        );
        $formInput['created_at'] = $date;
        $id = DB::table('products')->insertGetId($formInput);
        foreach($request->product_category as $categoryId){
            DB::table('category_product')->insert(['product_id'=>$id,'category_id'=>$categoryId]);
        }

        SeoRouteManager::save(
            $seoInput,
            $this->productSeoPath($slug),
            null,
            $this->productSeoLookupPaths($slug)
        );

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
        $seoUrl = $seo->url ?? $this->productSeoPath($product->slug ?? '');
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
        $formInput['price_visible_from'] = $request->filled('price_visible_from') ? $request->price_visible_from : null;
        $seoInput = $request->seo;
        $product = DB::table('products')->where('id', $id)->first();
        abort_if(! $product, Response::HTTP_NOT_FOUND);

        $oldSlug = $product->slug ?? null;
        $formInput['slug'] = $slug = $oldSlug ?: $this->normalizeProductSlugFromSeoInput($seoInput['url'] ?? null, $request->title);
        $existingSeo = $this->findProductSeo($oldSlug);
        SeoRouteManager::ensurePathIsAvailable(
            SeoRouteManager::normalizePath($seoInput['url'] ?? $this->productSeoPath($slug)),
            $this->productSeoPath($slug),
            $existingSeo?->id
        );
        $formInput['updated_at'] = $date = date('Y-m-d H:i:s');
        DB::table('products')->where('id',$id)->update($formInput);
        DB::table('category_product')->where('product_id',$id)->whereNotIn('category_id',$request->product_category)->delete();
        foreach($request->product_category as $categoryId){
            if(DB::table('category_product')->where(['product_id'=> $id,'category_id'=>$categoryId])->doesntExist()){
                DB::table('category_product')->insert(['product_id'=>$id,'category_id'=>$categoryId]);
            }
        }

        SeoRouteManager::save(
            $seoInput,
            $this->productSeoPath($slug),
            $seoInput['old_url'] ?? $existingSeo?->url,
            $this->productSeoLookupPaths($oldSlug)
        );

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
        $weights = DB::table('product_weights')->where('product_id',$id)->get();
        $product = DB::table('products')->where('id',$id)->first();
        $measurementUnits = DB::table('measurement_units')
            ->where(function ($query) use ($id) {
                $query->where('status', 1)->orWhereIn('id', function ($subQuery) use ($id) {
                    $subQuery->select('unit_id')->from('product_weights')->where('product_id', $id)->whereNotNull('unit_id');
                })->orWhereIn('id', function ($subQuery) use ($id) {
                    $subQuery->select('unit_id')->from('product_inventory_pools')->where('product_id', $id);
                });
            })->orderBy('priority')->orderBy('singular_name')->get();
        $inventoryUnits = $measurementUnits->filter(fn ($unit) => $unit->base_code === $unit->code)->values();
        $inventoryPools = DB::table('product_inventory_pools as pip')
            ->join('measurement_units as mu', 'mu.id', '=', 'pip.unit_id')
            ->where('pip.product_id', $id)
            ->select('pip.*', 'mu.code as unit_code', 'mu.singular_name', 'mu.plural_name', 'mu.allows_decimal')
            ->orderBy('mu.priority')->get();
        $pricingModes = SellingOption::pricingModes();

        return view('admin.'.$this->module.'.product_weights', compact('product', 'weights', 'pricingModes', 'measurementUnits', 'inventoryUnits', 'inventoryPools'));
    }

    public function storeInventory(Request $request, $id)
    {
        abort_if(Gate::denies($this->module.'_edit'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        abort_if(! DB::table('products')->where('id', $id)->exists(), Response::HTTP_NOT_FOUND);
        $validated = $request->validate([
            'Inventory' => ['required', 'array', 'min:1'],
            'Inventory.*.id' => ['nullable', 'integer'],
            'Inventory.*.unit_id' => ['required', 'integer', 'exists:measurement_units,id', 'distinct'],
            'Inventory.*.qty' => ['required', 'numeric', 'min:0', 'max:999999999'],
            'Inventory.*.track_stock' => ['nullable', 'boolean'],
            'Inventory.*.status' => ['required', 'boolean'],
        ]);

        DB::transaction(function () use ($validated, $id) {
            foreach ($validated['Inventory'] as $index => $inventory) {
                $unit = DB::table('measurement_units')->where('id', $inventory['unit_id'])->first();
                if (! $unit || $unit->base_code !== $unit->code) {
                    throw ValidationException::withMessages(["Inventory.$index.unit_id" => 'Inventory must use an independent base unit.']);
                }
                if (! $unit->allows_decimal && floor((float) $inventory['qty']) !== (float) $inventory['qty']) {
                    throw ValidationException::withMessages(["Inventory.$index.qty" => $unit->plural_name.' stock must be a whole number.']);
                }
                $poolId = $inventory['id'] ?? null;
                $existing = $poolId ? DB::table('product_inventory_pools')->where(['id' => $poolId, 'product_id' => $id])->first() : null;
                if ($poolId && ! $existing) {
                    throw ValidationException::withMessages(["Inventory.$index.id" => 'The selected inventory record is invalid.']);
                }
                if ($existing && (int) $existing->unit_id !== (int) $unit->id
                    && DB::table('product_weights')->where('inventory_pool_id', $poolId)->exists()) {
                    throw ValidationException::withMessages(["Inventory.$index.unit_id" => 'The base unit cannot be changed while selling options use this inventory.']);
                }
                $duplicate = DB::table('product_inventory_pools')->where(['product_id' => $id, 'unit_id' => $unit->id])
                    ->when($poolId, fn ($query) => $query->where('id', '<>', $poolId))->exists();
                if ($duplicate) {
                    throw ValidationException::withMessages(["Inventory.$index.unit_id" => 'This product already has inventory for that base unit.']);
                }
                $values = [
                    'product_id' => $id,
                    'unit_id' => $unit->id,
                    'qty' => $inventory['qty'],
                    'track_stock' => ! empty($inventory['track_stock']) ? 1 : 0,
                    'status' => (int) $inventory['status'],
                    'updated_at' => now(),
                ];
                if ($existing) DB::table('product_inventory_pools')->where('id', $poolId)->update($values);
                else {
                    $values['created_at'] = now();
                    DB::table('product_inventory_pools')->insert($values);
                }
            }
        });
        $this->clearStorefrontCache();

        return back()->with('success', 'Product inventory saved successfully. You can now connect selling options to it.');
    }

    public function productInventoryDestroy($id)
    {
        abort_if(Gate::denies($this->module.'_delete'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        if (DB::table('product_weights')->where('inventory_pool_id', $id)->exists()) {
            return response()->json(['success' => false, 'message' => 'This inventory is used by selling options. Remove or move those options first.'], 422);
        }
        $deleted = DB::table('product_inventory_pools')->where('id', $id)->delete();
        $this->clearStorefrontCache();

        return response()->json(['success' => (bool) $deleted, 'message' => $deleted ? 'Inventory deleted successfully.' : 'Inventory not found.'], $deleted ? 200 : 404);
    }
    
    public function storeWeights(Request $request,$id)
    {
        abort_if(Gate::denies($this->module.'_edit'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        $product = DB::table('products')->where('id', $id)->first();
        abort_if(! $product, Response::HTTP_NOT_FOUND);

        $validated = $request->validate([
            'Weight' => ['required', 'array', 'min:1'],
            'Weight.*.id' => ['nullable', 'integer'],
            'Weight.*.name' => ['nullable', 'string', 'max:100'],
            'Weight.*.quantity_value' => ['nullable', 'numeric', 'gt:0', 'max:1000000'],
            'Weight.*.quantity_unit' => ['nullable', 'in:'.implode(',', array_keys(SellingOption::units()))],
            'Weight.*.unit_id' => ['nullable', 'integer', 'exists:measurement_units,id'],
            'Weight.*.inventory_pool_id' => ['nullable', 'integer', 'exists:product_inventory_pools,id'],
            'Weight.*.pricing_mode' => ['required', 'in:manual,automatic'],
            'Weight.*.sell_price' => ['nullable', 'numeric', 'min:0'],
            'Weight.*.list_price' => ['nullable', 'numeric', 'min:0'],
            'Weight.*.cost_price' => ['nullable', 'numeric', 'min:0'],
            'Weight.*.unit_sell_price' => ['nullable', 'numeric', 'min:0'],
            'Weight.*.unit_list_price' => ['nullable', 'numeric', 'min:0'],
            'Weight.*.unit_cost_price' => ['nullable', 'numeric', 'min:0'],
            'Weight.*.allow_custom_quantity' => ['nullable', 'boolean'],
            'Weight.*.minimum_custom_quantity' => ['nullable', 'numeric', 'gt:0', 'max:1000000'],
            'Weight.*.maximum_custom_quantity' => ['nullable', 'numeric', 'gt:0', 'max:1000000'],
            'Weight.*.custom_quantity_step' => ['nullable', 'numeric', 'gt:0', 'max:1000000'],
            'Weight.*.qty' => ['nullable', 'numeric', 'min:0'],
            'Weight.*.stock' => ['nullable', 'boolean'],
            'default_selling_option' => ['nullable', 'integer'],
            'Weight.*.status' => ['required', 'in:0,1'],
        ]);

        if($request->isMethod('POST')){
            DB::transaction(function () use ($validated, $id) {
                $defaultWeightId = null;
                $firstWeightId = null;
                $defaultIndex = array_key_exists('default_selling_option', $validated)
                    ? (string) $validated['default_selling_option']
                    : null;
                foreach($validated['Weight'] as $index => $weight){
                $hasQuantity = isset($weight['quantity_value']) && $weight['quantity_value'] !== '';
                $unit = ! empty($weight['unit_id']) ? DB::table('measurement_units')->where('id', $weight['unit_id'])->first() : null;
                $hasUnit = (bool) $unit || ! empty($weight['quantity_unit']);
                if ($hasQuantity !== $hasUnit) {
                    throw ValidationException::withMessages([
                        "Weight.$index.quantity_value" => 'Enter both the preset quantity and its unit.',
                    ]);
                }
                if ($unit && ! $unit->allows_decimal) {
                    foreach (['quantity_value', 'minimum_custom_quantity', 'maximum_custom_quantity', 'custom_quantity_step'] as $field) {
                        if (isset($weight[$field]) && $weight[$field] !== '' && floor((float) $weight[$field]) !== (float) $weight[$field]) {
                            throw ValidationException::withMessages(["Weight.$index.$field" => $unit->plural_name.' must use whole numbers.']);
                        }
                    }
                }

                $pricingMode = $weight['pricing_mode'];
                if ($pricingMode === SellingOption::AUTOMATIC && (! $hasQuantity || ! isset($weight['unit_sell_price']) || ! isset($weight['unit_list_price']))) {
                    throw ValidationException::withMessages([
                        "Weight.$index.unit_sell_price" => 'Automatic pricing requires a preset quantity, unit selling rate and unit list rate.',
                    ]);
                }
                if ($pricingMode === SellingOption::MANUAL && (! isset($weight['sell_price']) || ! isset($weight['list_price']))) {
                    throw ValidationException::withMessages([
                        "Weight.$index.sell_price" => 'Manual pricing requires selling and list prices.',
                    ]);
                }

                $allowCustom = ! empty($weight['allow_custom_quantity']);
                if ($allowCustom && ! $hasQuantity) {
                    throw ValidationException::withMessages([
                        "Weight.$index.allow_custom_quantity" => 'Add a preset quantity and unit before enabling custom quantity.',
                    ]);
                }
                if ($allowCustom && ! empty($weight['maximum_custom_quantity'])
                    && (float) $weight['maximum_custom_quantity'] < (float) ($weight['minimum_custom_quantity'] ?? 1)) {
                    throw ValidationException::withMessages([
                        "Weight.$index.maximum_custom_quantity" => 'Maximum custom quantity must be greater than or equal to the minimum.',
                    ]);
                }

                $quantityValue = $hasQuantity ? (float) $weight['quantity_value'] : null;
                $insert = [];
                $insert['quantity_value'] = $quantityValue;
                $insert['unit_id'] = $unit->id ?? null;
                $insert['quantity_unit'] = $unit->code ?? ($hasUnit ? $weight['quantity_unit'] : null);
                $pool = ! empty($weight['inventory_pool_id'])
                    ? DB::table('product_inventory_pools')->where(['id' => $weight['inventory_pool_id'], 'product_id' => $id])->first()
                    : null;
                if ($hasQuantity && ! $pool) {
                    throw ValidationException::withMessages(["Weight.$index.inventory_pool_id" => 'Choose the shared inventory used by this selling option.']);
                }
                if ($pool && $unit) {
                    $poolUnit = DB::table('measurement_units')->where('id', $pool->unit_id)->first();
                    if (! $poolUnit || $unit->base_code !== $poolUnit->base_code) {
                        throw ValidationException::withMessages(["Weight.$index.inventory_pool_id" => 'The selling unit is not compatible with the selected inventory unit.']);
                    }
                }
                $insert['inventory_pool_id'] = $pool->id ?? null;
                $insert['pricing_mode'] = $pricingMode;
                $insert['unit_sell_price'] = $weight['unit_sell_price'] ?? null;
                $insert['unit_list_price'] = $weight['unit_list_price'] ?? null;
                $insert['unit_cost_price'] = $weight['unit_cost_price'] ?? null;
                $insert['allow_custom_quantity'] = $allowCustom ? 1 : 0;
                $insert['minimum_custom_quantity'] = $allowCustom ? ($weight['minimum_custom_quantity'] ?? 1) : null;
                $insert['maximum_custom_quantity'] = $allowCustom ? ($weight['maximum_custom_quantity'] ?? null) : null;
                $insert['custom_quantity_step'] = $allowCustom ? ($weight['custom_quantity_step'] ?? 1) : null;

                if ($pricingMode === SellingOption::AUTOMATIC) {
                    $insert['sell_price'] = round($quantityValue * (float) $weight['unit_sell_price'], 2);
                    $insert['list_price'] = round($quantityValue * (float) $weight['unit_list_price'], 2);
                    $insert['cost_price'] = isset($weight['unit_cost_price']) && $weight['unit_cost_price'] !== ''
                        ? round($quantityValue * (float) $weight['unit_cost_price'], 2)
                        : null;
                } else {
                    $insert['sell_price'] = $weight['sell_price'];
                    $insert['list_price'] = $weight['list_price'];
                    $insert['cost_price'] = $weight['cost_price'] ?? null;
                    if ($hasQuantity) {
                        $insert['unit_sell_price'] = round((float) $insert['sell_price'] / $quantityValue, 4);
                        $insert['unit_list_price'] = round((float) $insert['list_price'] / $quantityValue, 4);
                        $insert['unit_cost_price'] = $insert['cost_price'] !== null
                            ? round((float) $insert['cost_price'] / $quantityValue, 4)
                            : null;
                    }
                }

                $labelData = $insert;
                if ($unit) {
                    $labelData['unit_singular'] = $unit->singular_name;
                    $labelData['unit_plural'] = $unit->plural_name;
                }
                $generatedLabel = $hasQuantity ? SellingOption::label($labelData) : null;
                $insert['name'] = trim((string) ($weight['name'] ?? '')) ?: $generatedLabel;
                if (! $insert['name']) {
                    throw ValidationException::withMessages([
                        "Weight.$index.name" => 'Enter an option label or a preset quantity and unit.',
                    ]);
                }
                $insert['qty'] = $pool->qty ?? ($weight['qty'] ?? 0);
                $insert['stock'] = $pool ? (int) $pool->track_stock : ($weight['stock'] ?? 0);
                $insert['status'] = (int) $weight['status'];
                // The default is assigned once after all rows are saved so
                // every product has at most one customer-facing default.
                $insert['is_default'] = 0;
                $weightId = $weight['id'] ?? null;
                if($weightId && DB::table('product_weights')->where('id',$weightId)->where('product_id', $id)->exists()){
                    $insert['updated_at'] = date('Y-m-d H:i:s');
                    DB::table('product_weights')->where('id',$weightId)->where('product_id', $id)->update($insert);
                }else{
                    $insert['product_id'] = $id;
                    $insert['created_at'] = date('Y-m-d H:i:s');
                    $weightId = DB::table('product_weights')->insertGetId($insert);
                }
                $firstWeightId ??= (int) $weightId;
                if ($defaultIndex !== null && (string) $index === $defaultIndex) {
                    $defaultWeightId = (int) $weightId;
                }
                }

                $defaultWeightId ??= $firstWeightId;
                DB::table('product_weights')->where('product_id', $id)->update(['is_default' => 0]);
                if ($defaultWeightId) {
                    DB::table('product_weights')->where(['id' => $defaultWeightId, 'product_id' => $id])->update(['is_default' => 1]);
                }
            });
            $this->clearStorefrontCache();
            return back()->with('success','Selling options and stock saved successfully.');
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
            DB::table('product_inventory_pools')->whereIn('product_id', $productIds)->delete();
            $seoPaths = $products
                ->pluck('slug')
                ->flatMap(fn ($slug) => $this->productSeoLookupPaths($slug))
                ->unique()
                ->values()
                ->all();

            if (! empty($seoPaths)) {
                DB::table('seo_urls')
                    ->where(function ($query) use ($seoPaths) {
                        $query->whereIn('url', $seoPaths)->orWhereIn('alias', $seoPaths);
                    })
                    ->delete();
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
