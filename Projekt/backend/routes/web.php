<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

// Jelszó visszaállító űrlap megjelenítése
Route::get('/reset-password/{token}/{email}', function ($token, $email) {
    return view('reset-password', [
        'token' => $token,
        'email' => urldecode($email)
    ]);
})->name('password.reset');

// Jelszó visszaállítás feldolgozása
Route::post('/reset-password', function (Request $request) {
    $request->validate([
        'token' => 'required',
        'email' => 'required|email',
        'password' => 'required|min:8|confirmed',
    ]);
    
    $status = Password::reset(
        $request->only('email', 'password', 'password_confirmation', 'token'),
        function ($user, $password) {
            $user->forceFill([
                'password' => Hash::make($password),
                'remember_token' => Str::random(60)
            ])->save();
        }
    );
    
    if ($status === Password::PASSWORD_RESET) {
        return redirect()->route('password.reset.success');
    }
    
    return back()->withErrors(['email' => [__($status)]]);
})->name('password.update');

// Sikeres visszaállítás oldal
Route::get('/reset-password-success', function () {
    return view('reset-password-success');
})->name('password.reset.success');