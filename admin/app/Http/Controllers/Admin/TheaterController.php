<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\Theater;
use App\Models\Admin\Slot;
use Illuminate\Http\Request;
use App\Traits\StoreImageTrait;
use App\Traits\RedirectTrait;
use App\Http\Requests\StoreTheaterRequest;
use Symfony\Component\HttpFoundation\Response;
use Storage,Gate,View,Str,File,DB;

class TheaterController extends Controller
{
    use StoreImageTrait,RedirectTrait;

    public function __construct(Theater $model)
    {
        $this->model = $model;
        $this->module = 'theaters';
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
        return view('admin.'.$this->module.'.index', ['data' => $this->model::all() ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        abort_if(Gate::denies($this->module.'_create'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        return view('admin.'.$this->module.'.create', [ 'row' => [],'slots'=>[],'images'=>[]]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreTheaterRequest $request)
    {
        abort_if(Gate::denies($this->module.'_create'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        $formInput = $request->all();
        $formInput['slug'] = Str::slug($formInput['name'].'-'.$formInput['city']);
        $formInput['image'] = $this->verifyAndStoreImage($request, 'image', $this->module);
        $model = $this->model::create($formInput);
        return $this->redirectAfterSave($request->FormButton, $model->id);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Admin\Theater  $theater
     * @return \Illuminate\Http\Response
     */
    public function show(Theater $theater)
    {
        //
    }

    public function theatersajax(Request $request)
    {
        $alltheaters = DB::table('theaters')->where(['city'=>$request->city,'status'=>1])->get();
        $theaters = [];
        foreach ($alltheaters as $theater){
            $bookedslots = DB::table('bookings')->where('booking_status_id',1)->where('theater_id',$theater->id)->whereDate('booking_date',$request->booking_date)->get()->pluck('slot_id');
            $slotsCount = DB::table('slots')->where(['status'=>1])->whereNotIn('id',$bookedslots)->count();

            $theaters[] = ['id'=>$theater->id,'name'=>$theater->name,'max_people'=>$theater->max_people,'slotsCount'=>$slotsCount];
        }
        return response()->json(['status'=>'success','data'=>$theaters]);
    }

    public function theatersslots(Request $request){
        $theater = DB::table('theaters')->where(['id'=>$request->theater_id,'status'=>1])->first();
        $bookedslots = DB::table('bookings')->where('booking_status_id',1)->where('theater_id',$request->theater_id)->whereDate('booking_date',$request->booking_date)->get()->pluck('slot_id');
        $slots = DB::table('slots')->where(['status'=>1])->whereNotIn('id',$bookedslots)->get();
        return response()->json(['status' => 'success','slots' => $slots,'theater'=>$theater]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Admin\Theater  $theater
     * @return \Illuminate\Http\Response
     */
    public function edit(Theater $theater)
    {
        abort_if(Gate::denies($this->module.'_edit'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        $images = DB::table('theater_images')->where(['theater_id'=>$theater->id])->get();
        $slots = DB::table('slots')->where(['theater_id'=>$theater->id])->get();
        
        return view('admin.'.$this->module.'.edit', ['row' => $theater,'images'=>$images,'slots'=>$slots]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Admin\Theater  $theater
     * @return \Illuminate\Http\Response
     */
    public function update(StoreTheaterRequest $request, Theater $theater)
    {
        abort_if(Gate::denies($this->module.'_edit'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        $formInput = $request->all();
        $formInput['slug'] = Str::slug($formInput['name'].'-'.$formInput['city']);
        $formInput['image'] = $this->verifyAndStoreImage($request, 'image', $this->module);
        
        if($request->Slot){ 
            $i = 1;
            foreach($request->Slot as $k=>$row)
            {
                $slot = DB::table('slots')->where('id',$row['id'])->first();
                if($slot){
                    $exp = explode('-',str_replace(' ','',$row['timings']));
                    $start_time = date("H:i:s", strtotime($exp[0]));
                    $row['start_time'] = $start_time;
                    DB::table('slots')->where('id',$row['id'])->update($row);
                }else{
                    $exp = explode('-',str_replace(' ','',$row['timings']));
                    $start_time = date("H:i:s", strtotime($exp[0]));
                    $row['start_time'] = $start_time;
                    $row['theater_id'] = $theater->id;
                    DB::table('slots')->insert($row);
                }
                $i++;
            }
        }
        
        if($request->Gallery){ 
            $i = 1;
            foreach($request->Gallery as $k=>$row)
            {
                $gallery = DB::table('theater_images')->where('id',$row['id'])->first();
                if($row['image'] ?? ''){
                    $image = $this->verifyAndStoreMultipleImage($row['image'], 'image', 'theaters');
                    $row['image'] = $image;
                }
                unset($row['old_file']);
                if($gallery){
                    DB::table('theater_images')->where('id',$row['id'])->update($row);
                }else{
                    $row['theater_id'] = $theater->id;
                    DB::table('theater_images')->insert($row);
                }
                $i++;
            }
        }
        if($theater->update($formInput))
        return $this->redirectAfterSave($request->FormButton, $theater->id);
    }
    
    public function updateStatus(Request $request, $id)
    {
        abort_if(Gate::denies($this->module.'_edit'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        if($request->ajax() && $request->isMethod('PATCH')){
            $theater = $this->model::find($id);
            if($theater->update(['status'=> $request->status])){
                $status= $request->status == 1 ?'enabled':'disabled';
                return response()->json(['status'=>'success','message'=>"Status $status successfully."]);
            }
        }
    }
    
    public function updateSlotStatus(Request $request, $id)
    {
        abort_if(Gate::denies($this->module.'_edit'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        if($request->ajax() && $request->isMethod('PATCH')){
            $slot = Slot::find($id);
            if($slot->update(['status'=> $request->status])){
                $status= $request->status == 1 ?'enabled':'disabled';
                return response()->json(['status'=>'success','message'=>"Status $status successfully."]);
            }
        }
    }
    
    public function updateGalleryStatus(Request $request, $id)
    {
        abort_if(Gate::denies($this->module.'_edit'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        if($request->ajax() && $request->isMethod('PATCH')){
            if(DB::table('theater_images')->where('id',$id)->update(['status'=> $request->status])){
                $status= $request->status == 1 ?'enabled':'disabled';
                return response()->json(['status'=>'success','message'=>"Status $status successfully."]);
            }
        }
    }
    public function updateSort(Request $request)
    {
        abort_if(Gate::denies($this->module.'_edit'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        foreach ($request->order as $order) {
            $result = $this->model::find($order['id'])->update(['priority' => $order['position']]);
        }
		if($result)
        return response()->json(['success'=>true, 'message' => 'Update successfully.']);
        else
        return response()->json(['success'=>false, 'message' => 'An unexpected error has occurred.']);
    }
    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Admin\Theater  $theater
     * @return \Illuminate\Http\Response
     */
    public function destroy(Theater $theater)
    {
        abort_if(Gate::denies($this->module.'_delete'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        if($theater->image && File::exists('storage/'.$this->module.'/'. $theater->image)){
            Storage::delete('public/'.$this->module.'/'.$theater->image);
        }
        if($theater->delete() == 1)
        return response()->json(['success'=>true, 'message' => 'Deleted successfully.']);
        else
        return response()->json(['success'=>false, 'message' => 'An unexpected error has occurred.']);
    }
    
    public function slotDestroy($id)
    {
        abort_if(Gate::denies($this->module.'_delete'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        $slot = Slot::find($id);
        if($slot->delete() == 1)
        return response()->json(['success'=>true, 'message' => 'Deleted successfully.']);
        else
        return response()->json(['success'=>false, 'message' => 'An unexpected error has occurred.']);
    }
    
    public function galleryDestroy(Request $request)
    {
        abort_if(Gate::denies($this->module.'_delete'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        $gallery = DB::table('theater_images')->where(['id'=>$request->id])->first();
        if($gallery->image && File::exists('storage/'.$this->module.'/'. $gallery->image)){
            Storage::delete('public/'.$this->module.'/'.$gallery->image);
        }
        if(DB::table('theater_images')->where(['id'=>$request->id])->delete() == 1)
        return response()->json(['success'=>true, 'message' => 'Deleted successfully.']);
        else
        return response()->json(['success'=>false, 'message' => 'An unexpected error has occurred.']);
    }

    public function massDestroy(Request $request)
    {
        abort_if(Gate::denies($this->module.'_delete'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        $ids = explode(',',$request->ids);
        foreach($ids as $id) :
            $model = $this->model::find($id);
            if(@$model->image && File::exists('storage/'.$this->module.'/'. @$model->image)){
                $images = 'public/'.$this->module.'/'.$model->image;
                Storage::delete($images);
            }
            $result = $model->delete();
        endforeach;

        if($result == 1)
        return response()->json(['success'=>true, 'message' => 'Deleted successfully.']);
        else
        return response()->json(['success'=>false, 'message' => 'An unexpected error has occurred.']);
    }
}
