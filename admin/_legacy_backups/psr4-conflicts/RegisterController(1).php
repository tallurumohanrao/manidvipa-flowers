<?php
   
namespace App\Http\Controllers\API;
   
use Illuminate\Http\Request;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Validator, DB;

class RegisterController extends BaseController
{
    /**
     * Register api
     *
     * @return \Illuminate\Http\Response
     */
    public function authCheck(){
        if(auth('sanctum')->check()){
            $user = auth('sanctum')->user();
            return response()->json(['success' => true,'user'=>$user,'message' => 'User logged in.'], 200);
        } else {
             return response()->json(['success' => false,'message' => 'User logged out.'], 200);
        }
    }
    public function logout(){
        if(auth('sanctum')->check()){
            if(auth('sanctum')->user()->tokens()->delete()){
                return response()->json(['success' => true,'message'    => 'User logged out.'], 200);
            } else {
                return response()->json(['success' => false,'message'    => 'User not logged out.'], 200);
            }
        } else {
            return response()->json(['success' => false,'message' => 'User logged out.'], 200);
       }
    }
    
    public function register(Request $request)
    {  
        // $user = DB::table('users')->where('id',1)->first();
        // dd(Auth::login($user)); 
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required|email|unique:users',
            #'password' => 'required',
            'mobile' => 'required|unique:users,mobile|size:10',
            #'c_password' => 'required|same:password',
        ]);
   
        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors());       
        }
   
        $input = $request->all();
        $input['status'] = 1;
        $input['password'] = bcrypt($input['password']);
        $user = User::create($input);
        $success['token'] =  $user->createToken('MyApp')->plainTextToken;
        $success['name'] =  $user->name;
        if($cart_session = $request->cart_session ?? null){
            DB::table('carts')->where('cart_session',$cart_session)->update(['user_id'=>$user->id]);
        }
        $response = [
            'success' => true,
            'data' => $success,
            'message' => 'User register successfully.'
        ];
        return response()->json($response, 200);
        #return $this->sendResponse($user, 'User register successfully.');
    }
    
    public function resendOtp(Request $request)
    {
        $user_id = $request->user_id;
        if(empty($user_id)){
            $response = [
                'success' => false,
                'message' => 'User data missed.'
            ];
            return response()->json($response, 422);
        }
        $otp = rand(1234, 9999);
        $user = DB::table('users')->where('id', $user_id)->first();
        DB::table('users')->where('id', $user_id)->update(['otp'=>$otp,'otp_expire_at'=>date('Y-m-d H:i:s', strtotime(' +10 minutes '))]);
        
        $sms['template'] = 'OTP Template';
        $sms['phone'] = $user->phone;
        $sms['{{OTP}}'] = $otp;
        $sms_response = json_decode($this->sendSms($sms));
        
        if($sms_response->Success){
            $response = [
                'success' => true,
                'message' => 'An OTP code has been sent to your mobile number.'
            ];
            return response()->json($response, 200);
        }else{
            $response = [
                'success' => false,
                'message' => 'Unable to send OTP code to your mobile number. Please contact administrator.'
            ];
            return response()->json($response, 500);
        }
    }
   
    /**
     * Login api
     *
     * @return \Illuminate\Http\Response
     */
    public function login(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'email' => 'required|email',
            'password' => 'required'
        ],[
            'email.required'=>'Email is required.',
            'email.email'=>'Please enter valid email address.',
            'password.required'=>'Password is required.'
            ]);

        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors());
        }
        if(auth()->attempt(['email' => $request->email, 'password' => $request->password,'status'=>1])){
            $user = auth()->user();
            if($user){
                $success['id'] =  $user->id;
                $success['name'] =  $user->name;
                $success['token'] =  $user->createToken('MyApp')->plainTextToken;
                if($cart_session = $request->cart_session ?? null){
                    DB::table('carts')->where('cart_session',$request->cart_session)->update(['user_id'=>$user->id]);
                }
            }
            $message = 'You have logged in successfully.';
            return $this->sendResponse($success,$message);
        }
        else{
            return $this->sendError('Invalid credentials.');
        }
    }
    
    
    public function login11(Request $request)
    {   
        $validator = Validator::make($request->all(), [
            'user_id' => 'required',
            'otp' => 'required'
        ]);
        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors());       
        }
        /* Validation Logic */
        $user_id = $request->user_id;
        $user   = DB::table('users')->where(['id'=>$request->user_id,'otp'=>$request->otp])->first();
        $now = now();
        if (!$user) {
            return response()->json(['success'=>false,'message'=>'Incorrect OTP.']);
        }else if($user && $now->isAfter($user->otp_expire_at)){
            return response()->json(['status'=>'error','message'=>'Your OTP has expired.']);
        }
        Auth::loginUsingId($user->id);
        return response()->json(['success'=>true,'message'=>'You have successfully loggedin to your account.','api_token'=>'1|qMEsgOkSkvzDEfiVM1J5eHexNyOXFpRCn3taZs7p'],200);
    }
    
    public function verifyOtp(Request $request){
        $validator = Validator::make($request->all(), [
            'user_id' => 'required',
            'otp' => 'required'
        ]);
        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors());       
        }
        /* Validation Logic */
        $user   = DB::table('users')->where('id',$request->user_id)->first();
        if($user->otp == $request->otp){
            return response()->json(['success'=>true,'message'=>'OTP verified successfully.'],200);
        }else{
            return response()->json(['success'=>false,'message'=>'Invalid OTP.'],422);
        }
    }
    
    public function sendotp(Request $request)
    {    
        $validator = Validator::make($request->all(), [
            'phone' => 'required|size:10',
        ]);
   
        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors());       
        }
        
        $user = DB::table('users')->where('phone', $request->phone)->first(); 
        if($user == null){
            return response()->json(['success'=>'false','message'=>'An account not existed with this mobile number.']);
        }
        $otp = rand(1234, 9999);
        DB::table('users')->where('id', $user->id)->update(['otp'=>$otp,'otp_expire_at'=>date('Y-m-d H:i:s', strtotime(' +10 minutes '))]);
        
        $sms['template'] = 'OTP Template';
        $sms['phone'] = $request->phone;
        $sms['{{OTP}}'] = $otp;
        $sms_response = json_decode($this->sendSms($sms));
        
        if($sms_response->Success){
            $response = [
                'success' => true,
                'data'=>['user_id'=>$user->id,'name'=>$user->name,'email'=>$user->email,'phone'=>$user->phone],
                'message' => 'An OTP code has been sent to your mobile number.'
            ];
            return response()->json($response, 200);
        }else{
            $response = [
                'success' => false,
                'message' => 'Unable to send OTP code to your mobile number. Please contact administrator.'
            ];
            return response()->json($response, 500);
        }
        
        /*if(Auth::guard('web')->attempt(['email' => $request->email, 'password' => $request->password,'status'=>1])){
            $user = Auth::guard('web')->user(); 
            if($user){
                $response = [
                    'success' => true,
                    'data' => $user,
                    'message' => 'You have logged in successfully.'
                ];
            }
            return response()->json($response, 200);
        }else{ 
            $response = [
                'success' => false,
                'message' => 'Unauthorised'
            ];
            return response()->json($response, 401);
        } */
    }
}