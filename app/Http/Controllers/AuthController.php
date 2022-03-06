<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Users;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        //validate incoming request 
        $this->validate($request, [
            'name' => 'required|string',
            'email' => 'required|email|unique:users',
            'password' => 'required|confirmed',
        ]);

        try {

            $user = new Users;
            $user->name = $request->input('name');
            $user->email = $request->input('email');
            $plainPassword = $request->input('password');
            $user->password = app('hash')->make($plainPassword);

            $user->save();

            //return successful response
            return response()->json(['user' => $user, 'message' => 'CREATED'], 201);
        } catch (\Exception $e) {
            //return error message
            return response()->json(['message' => 'User Registration Failed!', 'exception' => $e->getMessage()], 409);
        }
    }

    public function login(Request $request)
    {
        $username = $request->get('email');
        $password = $request->get('password');
        if(!$username){
            return response()->json(['status' => false, 'message' => 'Email is required'], 422);
        }
        if(!$password){
            return response()->json(['status' => false, 'message' => 'Password is required'], 422);
        }

        /* $this->validate($request, [
            'username' => 'required|string',
            'password' => 'required|string',
        ]); */
        $credentials = $request->only(['email', 'password']);
        if (!$token = Auth::attempt($credentials)) {
            return response()->json(['status' => false, 'message' => 'Email/Password is incorrect!'], 401);
        }
        $user = app('db')->select("SELECT u.*, (SELECT name from roles WHERE id IN (u.roles)) as roles FROM users u WHERE u.email = '{$username}'");
        $lab = [];
        if(!empty($user) && count($user) > 0){
            $lab = app('db')->select("SELECT * FROM labs WHERE id = '{$user[0]->lab_assigned}'");
            if(empty($lab) && stripos($user[0]->roles, 'Admin') === false){
                return response()->json(['status' => false, 'message' => 'You have no lab access assigned. Please contact administrator.'], 401);
            }
        }
        return response()->json([
            'status' => true,
            'data' => [
                'token' => [
                    'token' => $token,
                    'token_type' => 'bearer',
                    'expires_in' => Auth::factory()->getTTL() * 60
                ],
                'user' => (array)$user[0],
                'lab' => !empty($lab) && count($lab) > 0 ? (array)$lab[0] : []
            ]
        ], 200);
    }

    public function forgotPassword(Request $request) {
        $username = $request->get('email');
        if(!$username){
            return response()->json(['status' => false, 'message' => 'Email is required'], 422);
        }
        $user = app('db')->select("SELECT u.*, (SELECT name from roles WHERE id IN (u.roles)) as roles FROM users u WHERE u.email = '{$username}'");
        if(!empty($user) && count($user) > 0){
            $user = $user[0];
            $resetToken = app('hash')->make($user->email.$user->password);
            $resetLink = env("APP_FRONTEND_URL", $request->url()).'/reset-password?email='.urlencode($user->email).'&token='.urlencode($resetToken);
            $data = array(
                'name' => $user->firstname,
                'resetLink' => $resetLink
            );
            
            Mail::send('forgot-password', $data, function($message) use ($user) {
                $message->to($user->email, $user->firstname.' '.$user->lastname)->subject('Reset you password - Telestar Health');
                $message->from(env("MAIL_FROM_ADDRESS"), env("MAIL_FROM_NAME"));
            });
        }
        return response()->json(['status' => true, 'message' => 'Email has been sent to your provided email address.']);
    }

    public function resetPassword(Request $request) {
        $username = urldecode($request->get('email'));
        $password = $request->get('password');
        $token = urldecode($request->get('token'));
        if(!$username || !$password || !$token){
            return response()->json(['status' => false, 'message' => 'Please provide all the reuired field `email`, `password` and `token`.'], 422);
        }
        $user = app('db')->select("SELECT u.*, (SELECT name from roles WHERE id IN (u.roles)) as roles FROM users u WHERE u.email = '{$username}'");
        if(!empty($user) && count($user) > 0){
            $user = $user[0];
            $isTokenValid = false;
            $isTokenValid = Hash::check($user->email.$user->password, $token);
            if($isTokenValid){
                app('db')->table('users')->where('id', $user->id)->update(['password' => app('hash')->make($password)]);
                return response()->json(['status' => true, 'message' => 'Your password has been reset. You can login with your new password.']);
            } else {
                return response()->json(['status' => false, 'message' => 'Invalid token. Please request a new password reset link.']);
            }
        } else {
            return response()->json(['status' => false, 'message' => 'User not found.']);
        }
    }

    public function logout() {
        Auth::logout();
        return response()->json(['status' => true, 'message' => 'Successfully logged out']);
    }

    public function refreshToken(){
        $token = Auth::refresh();
        return response()->json([
            'status' => true,
            'data' => [
                'token' => $token,
                'token_type' => 'bearer',
                'expires_in' => Auth::factory()->getTTL() * 60
            ]
        ], 200);
    }
}
