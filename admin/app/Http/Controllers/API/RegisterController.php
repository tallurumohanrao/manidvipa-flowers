<?php
   
namespace App\Http\Controllers\API;
   
use Illuminate\Http\Request;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Validator,DB,Str,Mail,Hash;

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
    public function sendPasswordResetNotification(Request $request){
        $input = $request->all();
        $validator = Validator::make($input, [
            'email' => 'required|email'
        ],[
            'email.required'=>'Email is required.',
            'email.email'=>'Please enter valid email address.'
            ]);

        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors());
        }
        $email = $request->email;
        $user = DB::table('users')->where('email',$email)->where('status',1)->first();
        if($user == null){
            return response()->json(['success' => false, 'message' => 'An account with this email address does not exist.'], 200);
        }
        $token = Str::random(64);
        DB::table('password_resets')->insert(['email' => $email,'token' => $token, 'created_at' => date('Y-m-d H:i:s', strtotime(' +60 minutes '))]);
        $link = config('app.frontend_url')."/password-reset/$token?email=$email";#route('password.reset', ['token' => $token, 'email' => $request->email]);
        $userBodyHtml = view('emails.reset-password',compact('user','link'))->render();#exit;
        
        try {
            Mail::html($userBodyHtml, function($message) use($user) {
                $message->to($user->email, $user->name)->subject(config('SITE_NAME').' Forget Password Notification!.');
                $message->from(config('SITE_EMAIL'), config('SITE_NAME'));
    
            });
            return response()->json([ 'success' => true, 'message' => 'An email with password reset link has benn sent your email address.'], 200);
        } catch (\Exception $e) {
            return response()->json(['success' =>false, 'message' => 'Mail server not working at this moment. Please contact webiste administrator.'], 200);
        }
    }
    public function resetPassword(Request $request){
        $input = $request->all();
        $input['token'] = $request->token;
        $validator = Validator::make($input, [
            'email' => 'required|email',
            'token' => 'required',
            'password' => ['required', 'confirmed'],
        ],[
            'email.required'=>'Email is required.',
            'email.email'=>'Please enter valid email address.'
            ]);

        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors());
        }
        $email = $request->email;
        $tokenData = DB::table('password_resets')->where(['email'=>$email,'token'=>$request->token])->first();
        if (!$tokenData) {
            return response()->json(['success'=>false,'message'=>'Please click on the forget password link at login page to get password reset mail.']);
        #}else if($tokenData && now()->isAfter($tokenData->created_at)){
        }else if($tokenData && (strtotime(date('Y-m-d H:i:s')) > strtotime($tokenData->created_at))){
            return response()->json(['success' =>false, 'message' => 'Your password reset link expired.'], 200);
        }
        $result = DB::table('users')->where('email',$email)->update(['password'=>Hash::make($request->password),'updated_at'=>date('Y-m-d H:i:s')]);
        //Delete the token
        DB::table('password_resets')->where('email', $email)->delete();
        if($result){
            return response()->json([ 'success' => true, 'message' => 'Your password reset completed successfully. Please login to acces your account.'], 200);
        } else {
            return response()->json(['success' =>false, 'message' => 'Something went wrong!.'], 200);
        }
    }
}