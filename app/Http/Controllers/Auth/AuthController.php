<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\Events\Registered; 


class AuthController extends Controller
{
    public function register(RegisterRequest $request){
       $userData =[
        'name' => $request->name,
        'username' => $request->username,
        'email' => $request->email,
        'role' => $request->role ?? 'user', 
        'password' => Hash::make($request->password),
        ];

        $user = User::create($userData);
        event(new Registered($user));
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
           'message' => 'User registered successfully.Please verify your email to continue.',
           'user' => $user,
           'token' => $token,
             ], 201);
    }

    public function login(LoginRequest $request){
               $user = User::whereUsername($request->username)->first();

               if(!$user || !Hash::check($request->password, $user -> password)){
               return response([
                'message'=> 'Invalid credentials',
                ], 401);
             }

       if (!$user->hasVerifiedEmail()) {
        return response([
            'message' => 'Email not verified. Please check your email.',
        ], 403);
    }

        $token = $user->createToken('auth_token')->plainTextToken;
        return response()->json([
        'message' => 'Login successful',
        'user' => $user,
        'token' => $token,
    ], 200);



    }
}
