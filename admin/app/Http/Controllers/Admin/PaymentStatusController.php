<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\StorePaymentStatusRequest;
use Symfony\Component\HttpFoundation\Response;
use App\Traits\RedirectTrait;
use Gate,View,DB;

class PaymentStatusController extends Controller
{
    use RedirectTrait;
    public function __construct()
    {
        $this->module = 'paymentstatuses';
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
        $data = DB::table('payment_statuses')->get();
        return view('admin.'.$this->module.'.index', compact('data'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        abort_if(Gate::denies($this->module.'_create'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        return view('admin.'.$this->module.'.create', ['row' => []]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StorePaymentStatusRequest $request)
    {
        abort_if(Gate::denies($this->module.'_create'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        $formInput = $request->only('name','subject','body_html');
        $formInput['created_at'] = date('Y-m-d H:i:s');
        $id = DB::table('payment_statuses')->insertGetId($formInput);
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
        abort_if(Gate::denies($this->module.'_edit'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        $row = DB::table('payment_statuses')->where('id',$id)->first();
        abort_if(!$row, 404);
        return view('admin.'.$this->module.'.edit', compact('row'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  $id
     * @return \Illuminate\Http\Response
     */
    public function update(StorePaymentStatusRequest $request, $id)
    {
        abort_if(Gate::denies($this->module.'_edit'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        $formInput = $request->only('name','subject','body_html');
        $formInput['updated_at'] = date('Y-m-d H:i:s');
        DB::table('payment_statuses')->where('id',$id)->update($formInput);
        return $this->redirectAfterSave($request->FormButton, $id);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        abort_if(Gate::denies($this->module.'_delete'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        $row = DB::table('payment_statuses')->where('id',$id)->first();
        if($row && DB::table('order_payments')->where('payment_status',$row->name)->exists()){
            return response()->json(['success'=>false, 'message' => 'This payment status is used by existing orders and cannot be deleted.'], 422);
        }
        if(DB::table('payment_statuses')->where('id',$id)->delete() == 1)
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
            $row = DB::table('payment_statuses')->where('id',$id)->first();
            if($row && DB::table('order_payments')->where('payment_status',$row->name)->exists()){
                continue;
            }
            $result = DB::table('payment_statuses')->where('id',$id)->delete();
        endforeach;

        if($result == 1)
        return response()->json(['success'=>true, 'message' => 'Deleted successfully.']);
        else
        return response()->json(['success'=>false, 'message' => 'An unexpected error has occurred.']);
    }

}
