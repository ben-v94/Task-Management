<?php

namespace App\Http\Controllers\Api;


use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Controller;


class AuthController extends Controller
{
    public function register(Request $request)
    {
        $input = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8|confirmed',
        ]);
    
        $user = User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => Hash::make($input['password']),
        ]);
    
        $token = $user->createToken('auth-token')->plainTextToken;
    
        return response()->json([
            'message' => 'User created successfully',
            'user' => $user,
            'token' => $token
        ], 201);
    }
    

    public function login(Request $request)
    {
        $input = $request->validate([
            'email' => 'required|email',
            'password'=> 'required',
        ]);
        
        $user = User::where('email',$input['email'])->first();

        return $user && Hash::check($input['password'], $user->password) ?
             ['token' => $user->createToken('auth-token')->plainTextToken] : 
             response()->json(['error' => 'Unauthorized'], 401);

    }

    public function logout(Request $request)
    {
     $request->user()->currentAccessToken()->delete();


        return ['message' => 'Logged out successfully'];            
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $input = $request->validate([
            'name' => 'nullable|string|max:255',
            'email' => 'nullable|email|unique:users,email,' . $user->id,
        ]);

        if (isset($input['name'])) 
        {
            $user->name = $input['name'];
        }

        if (isset($input['email'])) 
        {
            $user->email = $input['email'];
        }

        $user->save();

        return response()->json([
            'message' => 'User name and/or email updated successfully',
            'user' => $user
        ]);
    }

    public function changePassword(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|min:8|confirmed',
        ]);

        if (!Hash::check($request->current_password, $user->password)) 
        {
            return response()->json(['error' => 'Current password is incorrect'], 403);
        }

        $user->password = Hash::make($request->new_password);
        $user->save();
        $request->user()->tokens()->delete();
        return ['message' => 'Password changed successfully'];
    }


}
