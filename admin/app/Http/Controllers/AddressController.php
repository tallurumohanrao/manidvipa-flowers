<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\User\StoreAddressRequest;
use Auth,DB;

class AddressController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $data = DB::table('addresses')->where('user_id',Auth::id())->get();
        return view('addresses.index', compact('data'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $data=[];
        return view('addresses.create', compact('data'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreAddressRequest $request)
    {
        $addresses = DB::table('addresses')->where('user_id',Auth::id())->get();
        if($request->is_default == 1){
            DB::table('addresses')->where('user_id',Auth::id())->update(['is_default' => NULL]);
        }
        $input = $request->except('_method','_token');
        $input['user_id'] = Auth::id();
        $id = DB::table('addresses')->insertGetId($input);
        $message = $id ? 'Address added successfully.' : 'An unexpected error has occurred.';
        return redirect()->route('addresses.index')->with('success',$message);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Models\Address  $address
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {

    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Models\Address  $address
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $userId = Auth::id();
        $address =  DB::table('addresses')->where(['id'=>$id,'user_id'=>Auth::id()])->first();
        if($address->user_id != $userId){
            return response()->view('errors.404', [], 404);
        }
        $data = DB::table('addresses')->where('user_id',$userId)->get();
        return view('addresses.edit', compact('address','data'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Models\Address  $address
     * @return \Illuminate\Http\Response
     */
    public function update(StoreAddressRequest $request, $id)
    {
        if($request->is_default == 1){
            DB::table('addresses')->where('user_id',Auth::id())->update(['is_default' => NULL]);
        }
        $input = $request->except('_method','_token');
        $input['is_default'] = $request->filled('is_default') ? 1 : null;
        if(DB::table('addresses')->where(['id'=>$id,'user_id'=>Auth::id()])->update($input)){
            return redirect()->route('addresses.index')->with('success','Address updated successfully.');
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Models\Address  $address
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $userId = Auth::id();
        $address =  DB::table('addresses')->where(['id'=>$id,'user_id'=>$userId])->first();
        if($address->user_id != $userId){
            return response()->json(['success'=>false, 'message' => 'You are not autherized to delete the record.']);
        }
        if(DB::table('addresses')->where(['id'=>$id,'user_id'=>$userId])->delete()){            
            return response()->json(['success'=>true, 'message' => 'Deleted successfully.']);
        }else{
            return response()->json(['success'=>false, 'message' => 'An unexpected error has occurred.']);
        }
    }
}
