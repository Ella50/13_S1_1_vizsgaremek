<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Api\UserController;

// Nyilvános route-ok
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Városok lekérése (regisztrációhoz szükséges)
Route::get('/cities', function () {
    return response()->json([
        'success' => true,
        'data' => \App\Models\City::all()
    ]);
});

// Védett route-ok (bejelentkezés szükséges)
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);
    
    // User management
    Route::apiResource('users', UserController::class);
    Route::get('/users/type/{type}', [UserController::class, 'getByType']);
    Route::get('/users/status/{status}', [UserController::class, 'getByStatus']);
    Route::get('/users/class/{classId}', [UserController::class, 'getByClass']);

    // Egyéb resource-ok
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
});

// Debug route - check database connection and users
Route::get('/debug/users', function () {
    try {
        // Check database connection
        DB::connection()->getPdo();
        $dbConnected = true;
        $dbName = DB::connection()->getDatabaseName();
    } catch (\Exception $e) {
        $dbConnected = false;
        $dbName = 'Cannot connect';
    }

    // Get all users with basic info
    $users = \App\Models\User::select('id', 'firstName', 'lastName', 'email', 'userType', 'status')->get();

    return response()->json([
        'database_connected' => $dbConnected,
        'database_name' => $dbName,
        'users_count' => $users->count(),
        'users' => $users,
        'server_time' => now()->toDateTimeString()
    ]);
});

// Debug route - test specific user
Route::get('/debug/user/{email}', function ($email) {
    $user = \App\Models\User::where('email', $email)->first();
    
    if (!$user) {
        return response()->json([
            'error' => 'User not found',
            'email' => $email
        ], 404);
    }

    return response()->json([
        'user' => [
            'id' => $user->id,
            'name' => $user->firstName . ' ' . $user->lastName,
            'email' => $user->email,
            'userType' => $user->userType,
            'status' => $user->status,
            'hasDiscount' => $user->hasDiscount,
            'created_at' => $user->created_at
        ]
    ]);
});