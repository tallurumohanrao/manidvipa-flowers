<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContactRequest;
use App\Http\Requests\StoreBookingRequest;
use Illuminate\Http\Request;
use App\Models\Booking;
use App\Models\BookingAddon;
use App\Models\Admin\Addon;
use App\Models\Admin\Contact;
use DB,Str;
use Illuminate\Validation\ValidationException;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //$this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        return view('home');
    }

    public function booknow()
    {
        return view('booknow');
    }

    public function about()
    {
        return view('about');
    }

    public function contact()
    {
        return view('contact');
    }

    public function contactStore(StoreContactRequest $request)
    {
        $create = $request->all();
        if(Str::contains($create['message'],['xx','Sex','sex','fuck','pussy','porn','http','www','@','!','#','$','%','^','&','*','(',')','[',']','{','}'])){
            if($request->type == 1){
            return response()->json(['status' => 'fail','message' => 'You have entered miscellaneous data or special charecters not allowed.']);
            }else{
                throw ValidationException::withMessages(['message' => 'You have entered miscellaneous data or special charecters not allowed.']);
                return redirect()->route('contact')->withInput();
            }
        }
        Contact::create($create);
        // Mail::to(config('SITE_EMAIL'))->send(new ContactAdminMail($request));
        // Mail::to($request->email)->send(new ContactMail($request));
        if($request->type == 1){
            return response()->json(['status' => 'success','message' => '<strong>Thank you for contacting us!</strong> We`ll get back to you as soon as possible.']);
        }else{
            return redirect()->route('contact')->with('success', "Thank you for contacting us! We'll get back to you as soon as possible.");
        }
    }

    public function theaters($city)
    {
        session(['booking_date'=>date('Y-m-d')]);
        $alltheaters = DB::table('theaters')->where(['city'=>$city,'status'=>1])->get();
        foreach($alltheaters as $theater){
            $bookedslots = DB::table('bookings')->where('booking_status_id',1)->where('theater_id',$theater->id)->whereDate('booking_date',date('Y-m-d'))->get()->pluck('slot_id');
            $slotsCount = DB::table('slots')->where(['status'=>1])->whereNotIn('id',$bookedslots)->count();
            $theaters[] = ['image'=>$theater->image,'slug'=>$theater->slug,'name'=>$theater->name,'slotsCount'=>$slotsCount,'price_description'=>$theater->price_description];
        }
        return view('theaters',compact('theaters'));
    }

    public function fetchslots(Request $request){
        session(['booking_date'=>$request->booking_date]);
        //$slots = DB::table('bookings')->where('theater_id',$request->theater_id)->whereDate('booking_date',$request->booking_date)->get()->pluck('slot_id');
        $theaters = [];
        $alltheaters = DB::table('theaters')->where(['city'=>$request->city,'status'=>1])->get();
        foreach($alltheaters as $theater){
            $bookedslots = DB::table('bookings')->where('booking_status_id',1)->where('theater_id',$theater->id)->whereDate('booking_date',$request->booking_date)->get()->pluck('slot_id');
            $slotsCount = DB::table('slots')->where(['status'=>1])->whereNotIn('id',$bookedslots)->count();
            $theaters[] = ['image'=>$theater->image,'slug'=>$theater->slug,'name'=>$theater->name,'slotsCount'=>$slotsCount,'price_description'=>$theater->price_description];
        }
        return response()->json(['status' => 'success','html' => view('includes.theaters',compact('theaters'))->render()]);
    }

    public function fetchsingletheater(Request $request){
        session(['booking_date'=>$request->booking_date]);
        $bookedslots = DB::table('bookings')->where('booking_status_id',1)->where('theater_id',$request->theater_id)->whereDate('booking_date',$request->booking_date)->get()->pluck('slot_id');
        $slots = DB::table('slots')->where(['status'=>1])->whereNotIn('id',$bookedslots)->get();
        return response()->json(['status' => 'success','slots' => $slots]);
    }

    public function theaterShow($slug)
    {
        $theater = DB::table('theaters')->where(['slug'=>$slug,'status'=>1])->first();
        $bookedslots = DB::table('bookings')->where('booking_status_id',1)->where('theater_id',$theater->id)->whereDate('booking_date',session('booking_date'))->get()->pluck('slot_id');
        $slots = DB::table('slots')->where(['status'=>1])->whereNotIn('id',$bookedslots)->get()->pluck('timings','id');
        return view('slots',compact('theater','slots'));
    }

    public function bookingstore(StoreBookingRequest $request,$id)
    {
        session(['booking_date'=>$request->booking_date]);
        $theater = DB::table('theaters')->where(['id'=>$id,'status'=>1])->first();
        $slot = DB::table('slots')->where(['id'=>$request->slot_id,'status'=>1])->first();
        $create = $request->all();
        $create['theater_id'] = $theater->id;
        $create['theater_name'] = $theater->name;
        $create['slot_id'] = $slot->id;
        $create['slot_timings'] = $slot->timings;
        $result = Booking::create($create);
        session(['booking_id'=>$result->id]);
        return redirect()->route('decorations');
    }

    public function decorations()
    {
        if(empty(session('booking_id'))){
            return redirect()->route('booknow');
        }
        $decorations = DB::table('decorations')->where(['status'=>1])->orderBy('priority')->get();
        $bookingId = session('booking_id');
        $booking = Booking::find($bookingId);
        return view('decorations',compact('decorations','booking'));
    }

    public function cakes()
    {
        if(empty(session('booking_id'))){
            return redirect()->route('booknow');
        }
        $cakes = DB::table('cakes')->where(['type'=>0,'status'=>1])->orderBy('priority')->get();
        $egglesscakes = DB::table('cakes')->where(['type'=>1,'status'=>1])->orderBy('priority')->get();
        $bookingId = session('booking_id');
        return view('cakes',compact('cakes','egglesscakes','bookingId'));
    }

    public function bookingupdate(Request $request, $bookingId)
    {
        if(empty(session('booking_id'))){
            return redirect()->route('booknow');
        }
        $booking = Booking::find($bookingId);
        if($request->decoration_id){
            $decoration = DB::table('decorations')->where(['id'=>$request->decoration_id,'status'=>1])->first();
            $update['decoration_id'] = $request->decoration_id;
            $update['decoration_name'] = $decoration->name;
            $update['amount'] = $booking->amount +  $decoration->price;
            $result = $booking->update($update);
            session(['decoration_id'=>$request->decoration_id]);
            return redirect()->route('cakes');
        }else if($request->cake_id){
            $update['cake_id'] = $request->cake_id;
            $cake = DB::table('cakes')->where(['id'=>$request->cake_id,'status'=>1])->first();
            $update['cake_name'] = $cake->name;
            $update['amount'] = $booking->amount +  $cake->price;
            $result = $booking->update($update);
            session(['cake_id'=>$request->cake_id]);
            return redirect()->route('addons');
        }else if($request->addons){
            $addons = $request->addons;
            foreach($addons as $addonId){
                $addon = Addon::find($addonId);
                $booking_addon = DB::table('booking_addons')->where(['booking_id'=>$booking->id,'addon_id'=>$addon->id])->first();
                if($booking_addon == null){
                    BookingAddon::create(['booking_id'=>$booking->id,'addon_id'=>$addon->id,'addon_name'=>$addon->name,'price'=>$addon->price ]);
                }
            }
            $addonSumAmount = DB::table("booking_addons")->select(DB::raw("SUM(booking_addons.price) as total_price"))->first();
            $update['amount'] = $booking->amount + $addonSumAmount->total_price;
            $booking->update($update);
            session(['addons'=>$request->addons]);
            return redirect()->route('review');
        }else if($request->step == 'user_data'){
            $booking->update($request->all());
            return redirect()->route('paywithrazorpay');
        }
    }

    public function addons()
    {
        if(empty(session('booking_id'))){
            return redirect()->route('booknow');
        }
        $decorations = DB::table('addons')->where(['type'=>'decorations','status'=>1])->orderBy('priority')->get();
        $roses       = DB::table('addons')->where(['type'=>'roses','status'=>1])->orderBy('priority')->get();
        $photographs = DB::table('addons')->where(['type'=>'photography','status'=>1])->orderBy('priority')->get();
        $bookingId   = session('booking_id');
        return view('addons',compact('bookingId','decorations','roses','photographs'));
    }
    public function review(){
        if(empty(session('booking_id'))){
            return redirect()->route('booknow');
        }
        $bookingId = session('booking_id');
        $booking = Booking::find($bookingId);
        $addons = DB::table('booking_addons')->where('booking_id',$booking->id)->get();
        return view('review',compact('booking','addons'));
    }
}
