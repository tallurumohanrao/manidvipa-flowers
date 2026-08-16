<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB,View;

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
    public function index()
    {
        $products = DB::table('products')->where('status',1)->orderBy('title')->get()->pluck('title','id');
        $data = DB::table('featured_products')->selectRaw('featured_products.id,featured_products.created_at,products.title')->join('products','featured_products.product_id', '=', 'products.id')->orderByDesc('id')->paginate(config('PER_PAGE'));
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
        foreach($request->products as $id){
            $result = DB::table('featured_products')->where('product_id',$id)->first();
            if($result == NULL){
                DB::table('featured_products')->insert(['product_id'=>$id,'created_at'=>date('Y-m-d H:i:s')]);
            }
        }
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
        $result = DB::table('featured_products')->where('id',$id)->delete();
        if ($result==1){
            $data = [ 'success' => true, 'message' => 'Deleted successfully.' ];
          }else{
            $data = [ 'success' => false, "message" => "An unexpected error has occurred." ];
        }
        return response()->json($data);
    }

    public function massDestroy(Request $request)
    {
        $ids = explode(',',$request->ids);
        foreach($ids as $id) :
            $result = DB::table('featured_products')->where('id',$id)->delete();
        endforeach;

        if($result == 1)
        return response()->json(['success'=>true, 'message' => 'Deleted successfully.']);
        else
        return response()->json(['success'=>false, 'message' => 'An unexpected error has occurred.']);
    }
}
