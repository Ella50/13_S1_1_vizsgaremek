<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\UserController;

/*Route::get('/auth/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');*/
//Route::get('/auth/users', [AuthController::class, 'users']);
//Route::get('/auth/alma', [AuthController::class, 'alma']);


Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::apiResource('users', UserController::class);
// Speciális user route-ok
Route::get('/users/type/{type}', [UserController::class, 'getByType']);
Route::get('/users/status/{status}', [UserController::class, 'getByStatus']);
Route::get('/users/class/{classId}', [UserController::class, 'getByClass']);

Route::get('/cities', function () {
    return response()->json([
        'success' => true,
        'data' => \App\Models\City::all()
    ]);
});

Route::get('/classes', function () {
    return response()->json([
        'success' => true,
        'data' => \App\Models\StudentClass::all()
    ]);
});

Route::get('/groups', function () {
    return response()->json([
        'success' => true,
        'data' => \App\Models\Group::all()
    ]);
});

Route::get('/rfidcards', function () {
    return response()->json([
        'success' => true,
        'data' => \App\Models\RfidCard::all()
    ]);
});