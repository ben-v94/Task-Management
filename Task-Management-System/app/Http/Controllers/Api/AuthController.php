<?php

namespace App\Http\Controllers\Api;


use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\UpdateUserRequest;
use App\Http\Requests\Auth\ChangePasswordRequest;


class AuthController extends Controller
{
    public function register(RegisterRequest $request)
    {
    
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);
    
        $token = $user->createToken('auth-token')->plainTextToken;
    
        return response()->json([
            'message' => 'User created successfully',
            'user' => $user,
            'token' => $token
        ], 201);
    }
    

    public function login(LoginRequest $request)
    {
        $user = User::where('email', $request->email)->first();

        if ($user && Hash::check($request->password, $user->password)) 
        {
            $token = $user->createToken('auth-token')->plainTextToken;

            return response()->json([
                'message' => 'User logged in successfully',
                'user' => $user,
                'token' => $token
            ]);
        }

        return response()->json(['error' => 'Unauthorized'], 401);

    }

    public function logout(Request $request)
    {
        auth()->user()->currentAccessToken()->delete();

        return ['message' => 'Logged out successfully'];            
    }

    public function update(UpdateUserRequest $request)
    {
        $user = auth()->user();

        if (isset($request->name)) 
        {
            $user->name = $request->name;
        }

        if (isset($request->email)) 
        {
            $user->email = $request->email;
        }

        $user->save();

        return response()->json([
            'message' => 'User name and/or email updated successfully',
            'user' => $user
        ]);
    }

    public function changePassword(ChangePasswordRequest $request)
    {
        $user = auth()->user();

        if (!Hash::check($request->current_password, $user->password)) 
        {
            return response()->json(['error' => 'Current password is incorrect'], 403);
        }

        $user->password = Hash::make($request->new_password);
        $user->save();
        auth()->user()->tokens()->delete();
        return ['message' => 'Password changed successfully'];
    }


}
