<?php
   
namespace App\Http\Controllers\API;
   
use Illuminate\Http\Request;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
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
        $email = strtolower(trim($request->email));
        $user = User::where('email',$email)->where('status',1)->first();
        if($user == null){
            return response()->json(['success' => false, 'message' => 'An account with this email address does not exist.'], 200);
        }
        $broker = Password::broker('users');

        try {
            $response = $broker->sendResetLink([
                'email' => $email,
                'status' => 1,
            ], function ($user, $token) {
                $link = rtrim(config('app.frontend_url'), '/')."/password-reset/".urlencode($token)."?email=".urlencode($user->email);
                $userBodyHtml = view('emails.reset-password',compact('user','link'))->render();#exit;

                Mail::html($userBodyHtml, function($message) use($user) {
                    $message->to($user->email, $user->name)->subject(config('SITE_NAME').' Forget Password Notification!.');
                    $message->from(config('SITE_EMAIL'), config('SITE_NAME'));
                });
            });

            if($response === Password::RESET_LINK_SENT){
                return response()->json([ 'success' => true, 'message' => 'An email with password reset link has been sent to your email address.'], 200);
            }

            return response()->json(['success' => false, 'message' => __($response)], 200);
        } catch (\Exception $e) {
            $broker->deleteToken($user);
            return response()->json(['success' =>false, 'message' => 'Mail server not working at this moment. Please contact website administrator.'], 200);
        }
    }
    public function resetPassword(Request $request){
        $input = $request->all();
        $input['token'] = $request->token;
        $validator = Validator::make($input, [
            'email' => 'required|email',
            'token' => 'required|string',
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ],[
            'email.required'=>'Email is required.',
            'email.email'=>'Please enter valid email address.'
            ]);

        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors());
        }

        $status = Password::broker('users')->reset([
            'email' => strtolower(trim($request->email)),
            'password' => $request->password,
            'password_confirmation' => $request->password_confirmation,
            'token' => $request->token,
            'status' => 1,
        ], function ($user, $password) {
            $user->forceFill([
                'password' => Hash::make($password),
                'remember_token' => Str::random(60),
            ])->save();

            event(new PasswordReset($user));
        });

        if($status === Password::PASSWORD_RESET){
            return response()->json([ 'success' => true, 'message' => 'Your password reset completed successfully. Please login to access your account.'], 200);
        }

        return response()->json(['success' =>false, 'message' => __($status)], 200);
    }
}
