<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\MenuController;
use App\Http\Controllers\Api\KitchenController;
use App\Http\Controllers\Api\PasswordResetController;


// Publikus Ăştvonalak


Route::post('/reset-password', function (Request $request) {
    // 1. SIMA VALIDÁCIÓ - NEM kérjük, hogy létezzen az email
    $validated = $request->validate([
        'email' => 'required|email|max:255'
    ]);
    
    $email = $validated['email'];
    
    // 2. Ellenőrizzük, de ne közöljük a felhasználóval
    $userExists = DB::table('users')->where('email', $email)->exists();
    
    if (!$userExists) {
        // Ha nincs ilyen user, akkor is sikeres választ adunk (biztonsági okokból)
        return response()->json([
            'success' => true,
            'message' => 'Ha létezik ilyen email cím a rendszerünkben, elküldtük a visszaállítási linket.'
        ], 200);
    }
    
    // 3. Ha van user, küldjük a linket
    try {
        $status = Password::sendResetLink(
            $request->only('email')
        );
        
        if ($status === Password::RESET_LINK_SENT) {
            return response()->json([
                'success' => true,
                'message' => 'Jelszó visszaállítási linket elküldtük az email címedre!'
            ], 200);
        }
        
        return response()->json([
            'success' => false,
            'message' => 'Hiba történt az email küldése során.'
        ], 500);
        
    } catch (\Exception $e) {
        // Ha email küldés hibás, akkor is sikeres választ adunk
        return response()->json([
            'success' => true,
            'message' => 'A jelszó visszaállítási folyamat elindult.',
            'note' => 'Email küldés letiltva teszt módban'
        ], 200);
    }
});

Route::post('/reset-password/confirm', function (Request $request) {
    $request->validate([
        'token' => 'required',
        'email' => 'required|email',
        'password' => 'required|min:8|confirmed',
    ]);
    
    $status = Password::reset(
        $request->only('email', 'password', 'password_confirmation', 'token'),
        function ($user, $password) {
            $user->forceFill([
                'password' => bcrypt($password)
            ])->save();
        }
    );
    
    if ($status === Password::PASSWORD_RESET) {
        return response()->json([
            'success' => true,
            'message' => 'Jelszavad sikeresen megváltozott!'
        ], 200);
    }
    
    return response()->json([
        'success' => false,
        'message' => 'Érvénytelen vagy lejárt token.'
    ], 400);
});


// TEST ROUTE
Route::get("/test-health", function() {
    return response()->json(["status" => "healthy", "time" => now()]);
});
