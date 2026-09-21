<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use DB,View,Gate;
use Symfony\Component\HttpFoundation\Response;

class FeaturedProductController extends Controller
{
    public function __construct()
    {
        $this->module = 'featuredproducts';
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
        $products = DB::table('products')->where('status',1)->orderBy('title')->get()->pluck('title','id');
        $query = DB::table('featured_products')->selectRaw('featured_products.id,featured_products.created_at,products.title')->join('products','featured_products.product_id', '=', 'products.id');
        if ($request->filled('q')) {
            $query->where('products.title', 'like', '%'.$request->input('q').'%');
        }
        $data = $query->orderByDesc('featured_products.id')->paginate($request->input('per_page') ?: config('PER_PAGE'))->withQueryString();
        return view('admin.'.$this->module.'.index', compact('products','data'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        abort_if(Gate::denies($this->module.'_create'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        $request->validate(['products' => ['required', 'array', 'min:1'], 'products.*' => ['integer', 'exists:products,id']]);
        foreach($request->products as $id){
            $result = DB::table('featured_products')->where('product_id',$id)->first();
            if($result == NULL){
                DB::table('featured_products')->insert(['product_id'=>$id,'created_at'=>date('Y-m-d H:i:s')]);
            }
        }
        Cache::forget('api_home_sections');
        return back()->with('success','Feature products added successfully.');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Admin\FeaturedProduct  $featuredproduct
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Admin\FeaturedProduct  $featuredproduct
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Admin\FeaturedProduct  $featuredproduct
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Admin\FeaturedProduct  $featuredproduct
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        abort_if(Gate::denies($this->module.'_delete'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        $result = DB::table('featured_products')->where('id',$id)->delete();
        if ($result) Cache::forget('api_home_sections');
        if ($result==1){
            $data = [ 'success' => true, 'message' => 'Deleted successfully.' ];
          }else{
            $data = [ 'success' => false, "message" => "An unexpected error has occurred." ];
        }
        return response()->json($data);
    }

    public function massDestroy(Request $request)
    {
        abort_if(Gate::denies($this->module.'_delete'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        $ids = explode(',',$request->ids);
        foreach($ids as $id) :
            $result = DB::table('featured_products')->where('id',$id)->delete();
        endforeach;

        if (! empty($ids)) Cache::forget('api_home_sections');

        if($result == 1)
        return response()->json(['success'=>true, 'message' => 'Deleted successfully.']);
        else
        return response()->json(['success'=>false, 'message' => 'An unexpected error has occurred.']);
    }
}
