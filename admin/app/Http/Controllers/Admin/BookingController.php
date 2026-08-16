<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\Booking;
use App\Models\Admin\Addon;
use App\Models\Admin\BookingAddon;
use Illuminate\Http\Request;
use App\Traits\StoreImageTrait;
use App\Traits\RedirectTrait;
use App\Http\Requests\StoreBookingRequest;
use Symfony\Component\HttpFoundation\Response;
use Storage,Gate,View,Str,File,DB;

class BookingController extends Controller
{
    use StoreImageTrait,RedirectTrait;

    public function __construct(Booking $model)
    {
        $this->model = $model;
        $this->module = 'bookings';
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

        $perPage = config('ADMIN_PER_PAGE');
        $query = $this->model::query();
        if($request->filled('booking_type')){
            $query->where('booking_type', $request->booking_type);
        }
        if($request->filled('booking_date')){
            $query->whereDate('booking_date', $request->booking_date);
        }
        if($request->filled('name')){
            $query->where('name', 'like', '%'. $request->name .'%');
        }
        if($request->filled('theater')){
            $query->where('theater_name', $request->theater);
        }
        if($request->filled('booking_status')){
            $query->where('booking_status_id', $request->booking_status);
        }
        if($request->filled('payment_status')){
            $query->where('payment_status', $request->payment_status);
        }
        $data = $query->orderByDesc('id')->paginate($perPage)->withQueryString();
        $theaters = DB::table('theaters')->whereStatus(1)->get()->pluck('name','name');
        return view('admin.'.$this->module.'.index',compact('data','theaters'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        abort_if(Gate::denies($this->module.'_create'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        $decorations = DB::table('decorations')->whereStatus(1)->get();
        $cakes = DB::table('cakes')->whereStatus(1)->get();
        $result_addons = DB::table('addons')->whereStatus(1)->orderBy('priority')->get();
        $addonsgroup = [];
        foreach($result_addons as $result_addon){
            $addonsgroup[$result_addon->type][] = ['id'=>$result_addon->id,'name'=>$result_addon->name,'price'=>$result_addon->price];
        }
        //dd($addonsgroup);
        return view('admin.'.$this->module.'.create', compact('decorations','cakes','addonsgroup'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreBookingRequest $request)
    {
        abort_if(Gate::denies($this->module.'_create'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');

        $formInput = $request->all();
        $decoration_exp = explode('||',$formInput['decorations']);
        $cake_exp = explode('||',$formInput['cakes']);

        $theater = DB::table('theaters')->where(['id'=>$formInput['theater_id'],'status'=>1])->first();
        $slot = DB::table('slots')->where(['id'=>$formInput['slot_id'],'status'=>1])->first();

        $decoration = DB::table('decorations')->where(['id'=>$decoration_exp[0],'status'=>1])->first();
        $cake = DB::table('cakes')->where(['id'=>$cake_exp[0],'status'=>1])->first();

        $create['booking_type'] = 1;
        $create['amount'] = $formInput['total_amount'];
        $create['payment_amount'] = 100 * $formInput['amount_paid'];
        $create['name'] = $formInput['name'];
        $create['email'] = $formInput['email'];
        $create['whatsapp_number'] = $formInput['whatsapp_number'];
        $create['booking_date'] = $formInput['booking_date'];
        $create['no_of_persons'] = $formInput['no_of_persons'];
        $create['theater_id'] = $theater->id;
        $create['theater_name'] = $theater->name;
        $create['city_name'] = $theater->city;
        $create['theater_price'] = $theater->price1;
        $create['slot_id'] = $slot->id;
        $create['slot_timings'] = $slot->timings;
        // decoration
        $create['decoration_id'] = $decoration->id;
        $create['decoration_name'] = $decoration->name;
        $create['decoration_price'] = $decoration->price;
        //cake
        $create['cake_id'] = $cake->id;
        $create['cake_name'] = $cake->name;
        $create['cake_price'] = $cake->price;

        $create['food'] =  $formInput['food'] ?? 0;
        $create['booking_status_id'] =  $formInput['booking_status_id'];
        $create['payment_status'] =  $formInput['payment_status'];
        $create['payment_method'] =  $formInput['payment_method'];
        $create['transaction_id'] =  $formInput['transaction_id'];

        $booking = Booking::create($create);

        $addons = $formInput['addons'];
        if(count($addons)){
            foreach($addons as $ak=>$addon){
                $addon_exp = explode('||',$addon);
                $addon = Addon::find($addon_exp[0]);

                $crate_addon['booking_id'] = $booking->id;
                $crate_addon['addon_id'] = $addon->id;
                $crate_addon['addon_name'] = $addon->name;
                $crate_addon['price'] = $addon->price;
                BookingAddon::create($crate_addon);
            }
        }
        return response()->json(['status'=>'success','message'=>"Booking successfull."]);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Admin\Booking  $booking
     * @return \Illuminate\Http\Response
     */
    public function show(Booking $booking)
    {
        $addons = DB::table('booking_addons')->where('booking_id',$booking->id)->get();
        return view('admin.'.$this->module.'.show', compact('booking','addons'));
    }
    
    public function print(Booking $booking)
    {   
        $addons = DB::table('booking_addons')->where('booking_id',$booking->id)->get();
        return view('admin.'.$this->module.'.print', compact('booking','addons'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Admin\Booking  $booking
     * @return \Illuminate\Http\Response
     */
    public function edit(Booking $booking)
    {
        abort_if(Gate::denies($this->module.'_edit'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        return view('admin.'.$this->module.'.edit', ['row' => $booking]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Admin\Booking  $booking
     * @return \Illuminate\Http\Response
     */
    public function update(StoreBookingRequest $request, Booking $booking)
    {
        abort_if(Gate::denies($this->module.'_edit'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        $formInput = $request->all();
        $formInput['image'] = $this->verifyAndStoreImage($request, 'image', $this->module);
        if($booking->update($formInput))
        return $this->redirectAfterSave($request->FormButton, $booking->id);
    }
    public function updateStatus(Request $request, $id)
    {
        abort_if(Gate::denies($this->module.'_edit'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        if($request->ajax() && $request->isMethod('PATCH')){
            $booking = $this->model::find($id);
            if($booking->update(['status'=> $request->status])){
                $status= $request->status == 1 ?'enabled':'disabled';
                return response()->json(['status'=>'success','message'=>"Status $status successfully."]);
            }
        }
    }
    public function updateSort(Request $request)
    {
        abort_if(Gate::denies($this->module.'_edit'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        foreach ($request->order as $booking) {
            $result = $this->model::find($booking['id'])->update(['priority' => $booking['position']]);
        }
		if($result)
        return response()->json(['success'=>true, 'message' => 'Update successfully.']);
        else
        return response()->json(['success'=>false, 'message' => 'An unexpected error has occurred.']);
    }
    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Admin\Booking  $booking
     * @return \Illuminate\Http\Response
     */
    public function destroy(Booking $booking)
    {
        abort_if(Gate::denies($this->module.'_delete'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        if($booking->image && File::exists('storage/'.$this->module.'/'. $booking->image)){
            Storage::delete('public/'.$this->module.'/'.$booking->image);
        }
        if($booking->delete() == 1)
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
    
    public function updateBooking(Request $request,$id)
    {
        $booking = $this->model::find($id);
        $booking->update(['booking_status_id' => $request->booking_status_id]);
        $booking = $this->model::find($id);
        $status = bookingStatuses()[$booking->booking_status_id];
        return response()->json(['success'=>true, 'message' => 'Order status successfully updated.','html' => $status]);
    }
}
