<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

Route::get('/auth/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
Route::get('/auth/users', [AuthController::class, 'users']);
Route::get('/auth/alma', [AuthController::class, 'alma']);
