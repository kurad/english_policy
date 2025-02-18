<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Exceptions\JWTException;

class AuthController extends Controller
{
    public function register(Request $request){
        $request->validate([
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' =>'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'email' => $request->email,
            'password' =>bcrypt($request->password),
            'role' => 'student',
            'complete_profile' =>false,
        ]);
        $token = JWTAuth::fromUser($user);

        return response()->json([
            'message' => 'Registration successful. Please login.',
        ], 200);
    }

    public function login(Request $request){
        $validator = Validator::make($request->all(),[
            'email' => 'required|email',
            'password' =>'required|string',
        ]);
        if($validator->fails()){
            return response()->json([
                'errors' => $validator->errors(),
                'message' => 'Validation failed. Please check your inputs.',
            ], 422);
        }
        $credentials = $request->only('email', 'password');
        try{
            if(!$token = JWTAuth::attempt($credentials)){
                return response()->json([
                    'error' => 'Invalid credentials'
                ], 401);
            }
        }catch (JWTException $e){
            return response()->json(['error' => 'Could not create token'], 500);
        }
        $user = JWTAuth::user();


        $redirectPath = '';
        
        if($user->role == 'student')
        {
            $redirectPath = $user->complete_profile == 0 ? 'complete-profile' : '/student/dashboard';
        }elseif($user->role == 'teacher')
        {
            $redirectPath = 'teacher/dashboard';
        }
        
        return response()->json([
            'token' => $token, 
            'user' =>$user,
            'role' =>$user->role,
            'redirect' => $redirectPath,
            'message' => 'Login successful'
        ], 200);
    }

    public function completeProfile(Request $request){
        $request->validate([
            'firstname' =>'required|string|max:255',
            'lastname'=>'required|string|max:255',
            'class' =>'required|string',
        ]);
        
        $user = JWTAuth::user();
        
        
            $user->firstname = $request->firstname;
            $user->middlename = $request->middlename;
            $user->lastname=$request->lastname;
            $user->class=$request->class;
            $user->complete_profile = true;

            $user->save();

            $customClaims = [
                'firstname' => $user->firstname,
                'lastname' =>$user->lastname,
                'middlename' =>$user->middlename,
                'class' =>$user->class,
            ];
    
            $token = JWTAuth::claims($customClaims)->fromUser($user);
            $redirectPath = '/student/dashboard';
    
            return response()->json([
                'message' => 'User Profile completed',
                'token' =>$token,
                'redirect' =>$redirectPath,
            ], 200);
    }
    public function logout(Request $request){
        try{
            JWTAuth::invalidate(JWTAuth::getToken());
            return response()->json(['message'=>'Successfully logged out']);
        } catch(JWTException $e){
            return response()->json(['error'=>'Failed to log out'], 500);
        }
    }
    public function getUser(Request $request)
    {
        $user = JWTAuth::parseToken()->authenticate();
        return response()->json(compact('user'));
    }
    public function fetchStudents()
    {
        $students = User::where('role', 'student')->where('complete_profile', 1)->get();
        return response()->json($students);
    }

    public function refreshToken(Request $request)
    {
        try{
            $token = JWTAuth::getToken();
            if(!$token){
                return response()->json([
                    'error' => 'Token not provided'
                ], 400);
            }
            $newToken = JWTAuth::refresh($token);
            return response()->json(['token'=>$newToken]);
        }catch(JWTException $e){
            return response()->json(['error' =>'Could not refresh token'], 500);
        }
    }

}