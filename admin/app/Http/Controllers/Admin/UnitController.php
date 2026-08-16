<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\StoreUnitRequest;
use App\Traits\RedirectTrait;
use App\Traits\StoreImageTrait;
use Symfony\Component\HttpFoundation\Response;
use Gate,View,DB,Str;

class UnitController extends Controller
{
    use RedirectTrait,StoreImageTrait;
    public function __construct()
    {
        $this->module = 'units';
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */    
    public function index(Request $request)
    {
        abort_if(Gate::denies('units_view'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        $perPage = $request->input('per_page') ?: config('PER_PAGE');
        $query = DB::table('weights');
        $data = $query->paginate($perPage)->withQueryString();
        return view('admin.units.index', compact('data'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        abort_if(Gate::denies('units_create'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        return view('admin.units.create', ['row' => []]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreUnitRequest $request)
    {
        abort_if(Gate::denies('units_create'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        $input = $request->except('_token','FormButton');
        $input['created_at'] = date('Y-m-d H:i:s');
        $id = DB::table('weights')->insertGetId($input);
        return $this->redirectAfterSave($request->FormButton, 'units', $id);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Admin\Category  $course
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Admin\Category  $course
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        abort_if(Gate::denies('units_create'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        $row = DB::table('weights')->where('id',$id)->first();
        return view('admin.units.edit', ['row' => $row]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Admin\Category  $course
     * @return \Illuminate\Http\Response
     */
    public function update(StoreUnitRequest $request, $id)
    {
        abort_if(Gate::denies('units_create'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        $input = $request->except('_token','_method','FormButton');
        $input['updated_at'] = date('Y-m-d H:i:s');
        DB::table('weights')->where('id',$id)->update($input);
        return $this->redirectAfterSave($request->FormButton,'units', $id);
    }

    public function updateStatus(Request $request, $id)
    {
        abort_if(Gate::denies('units_create'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        if($request->ajax() && $request->isMethod('PATCH')){
            if(DB::table('weights')->where('id',$id)->update(['status'=>$request->status])){
                $status=$request->status==1?'enabled':'disabled';
                return response()->json(['status'=>'success','message'=>"Status $status successfully."]);
            }
        }
    }
    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Admin\Category  $course
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        abort_if(Gate::denies('units_delete'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        if(DB::table('weights')->where('id',$id)->delete() == 1)
        return response()->json(['success'=>true, 'message' => 'Deleted successfully.']);
        else
        return response()->json(['success'=>false, 'message' => 'An unexpected error has occurred.']);
    }
    public function massDestroy(Request $request)
    {
        abort_if(Gate::denies('units_delete'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        $ids = explode(',',$request->ids);
        $result = DB::table('weights')->whereIn('id',$ids)->delete();
        if($result)
        return response()->json(['success'=>true, 'message' => 'Deleted successfully.']);
        else
        return response()->json(['success'=>false, 'message' => 'An unexpected error has occurred.']);
    }

}
