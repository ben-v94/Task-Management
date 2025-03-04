<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $this->authorize('viewAny', User::class);
        return User::all();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $input = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|in:user,admin',
        ]);
    
        $user = User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => Hash::make($input['password']),
            'role' => $input['role'],
        ]);
    
        return response()->json(['message' => 'User created successfully', 'user' => $user], 201);
    }
    
    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        $this->authorize('view', $user);
        return $user;
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $user = User::findOrFail($id);

        $this->authorize('update', $user);
    
        $input = $request->validate([
            'name'  => 'nullable|string|max:255',
            'email' => 'nullable|email|unique:users,email,' . $user->id,
            'role'  => 'nullable|string|in:user,admin'
        ]);
    
        $user->update($input);
    
        return response()->json(['message' => 'User updated successfully', 'user' => $user]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $user = User::findOrFail($id);
        $this->authorize('delete', $user);

        if (auth()->user()->id == $user->id) 
        {
            return response()->json(['error' => 'You cannot delete yourself'], 403);
        }

        $user->delete();
        return response()->json(['message' => 'User deleted successfully']);
    }

    protected function isAdmin()
    {
        return auth()->user()->role === 'admin';
    }
}
