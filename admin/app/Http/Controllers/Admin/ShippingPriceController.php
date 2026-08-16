<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Requests\StoreShippingPriceRequest;
use App\Traits\RedirectTrait;
use Gate,View,DB,Str;

class ShippingPriceController extends Controller
{
    use RedirectTrait;
    public function __construct()
    {
        $this->module = 'shippingprices';
        View::share ( 'module', $this->module );
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        abort_if(Gate::denies($this->module.'_view'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        $data = DB::table('shipping_prices')->get();
        return view('admin.'.$this->module.'.index', compact('data'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('admin.'.$this->module.'.create', ['row' => []]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreShippingPriceRequest $request)
    {
        $formInput = $request->except('_token','FormButton');
        $formInput['name'] = Str::slug($request->title,'');
        $formInput['created_at'] = date('Y-m-d H:i:s');
        $id = DB::table('shipping_prices')->insertGetId($formInput);
        return $this->redirectAfterSave($request->FormButton, $id);
    }

    /**
     * Display the specified resource.
     *
     * @param  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $row = DB::table('shipping_prices')->where('id',$id)->first();
        return view('admin.'.$this->module.'.edit', compact('row'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  $id
     * @return \Illuminate\Http\Response
     */
    public function update(StoreShippingPriceRequest $request, $id)
    {
        $formInput = $request->except('_method','_token','FormButton');
        $formInput['name'] = Str::slug($request->title,'');
        $formInput['updated_at'] = date('Y-m-d H:i:s');
        DB::table('shipping_prices')->where('id',$id)->update($formInput);
        return $this->redirectAfterSave($request->FormButton, $id);
    }

    public function updateStatus(Request $request, $id)
    {
        abort_if(Gate::denies($this->module.'_create'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        if($request->ajax() && $request->isMethod('PATCH')){
            if(DB::table('shipping_prices')->where('id',$id)->update(['status'=>$request->status])){
                $status=$request->status==1?'enabled':'disabled';
                return response()->json(['status'=>'success','message'=>"Status $status successfully."]);
            }
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if(DB::table('shipping_prices')->where('id',$id)->delete() == 1)
        return response()->json(['success'=>true, 'message' => 'Deleted successfully.']);
        else
        return response()->json(['success'=>false, 'message' => 'An unexpected error has occurred.']);
    }
    public function massDestroy(Request $request)
    {
        $ids = explode(',',$request->ids);
        foreach($ids as $id) :
            $result = DB::table('shipping_prices')->where('id',$id)->delete();
        endforeach;

        if($result == 1)
        return response()->json(['success'=>true, 'message' => 'Deleted successfully.']);
        else
        return response()->json(['success'=>false, 'message' => 'An unexpected error has occurred.']);
    }

}
