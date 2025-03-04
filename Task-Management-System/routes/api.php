<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\TaskController;
use App\Models\Task;  
use App\Models\User;  
use App\Mail\WelcomeMail;


Route::get('/user', function (Request $request) {

    return $request->user();
})->middleware('auth:sanctum');


Route::post('/register',[ AuthController::class,'register']);
Route::post('/login',[ AuthController::class,'login']);

Route::middleware('auth:sanctum')->group(function () 
{
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/update', [AuthController::class, 'update']);
    Route::apiResource('users', UserController::class);
    Route::apiResource('tasks', TaskController::class);
    Route::post('/changePassword', [AuthController::class, 'changePassword']);
});


