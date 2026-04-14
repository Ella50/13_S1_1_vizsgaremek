<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;




class ResetPasswordController extends Controller
{
    /**
     * Jelszó visszaállító link küldése
     */
    public function sendResetLink(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email|exists:users,email'
            ]);
            
            Log::info('Send reset link for: ' . $request->email);
            
            $status = Password::sendResetLink(
                $request->only('email')
            );
            
            Log::info('Password send status: ' . $status);
            
            if ($status === Password::RESET_LINK_SENT) {
                return response()->json([
                    'success' => true,
                    'message' => 'Jelszó visszaállítási link elküldve az email címedre!'
                ]);
            }
            
            return response()->json([
                'success' => false,
                'message' => 'Nem sikerült elküldeni a linket'
            ], 500);
            
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validációs hiba',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Send reset link error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Hiba: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Jelszó visszaállítás a token alapján
     */
    public function resetPassword(Request $request)
    {
        try {
            $request->validate([
                'token' => 'required',
                'email' => 'required|email',
                'password' => 'required|min:8|confirmed',
            ]);
            
            $status = Password::reset(
                $request->only('email', 'password', 'password_confirmation', 'token'),
                function ($user, $password) {
                    $user->password = Hash::make($password);
                    $user->save();
                    
                    Log::info('Password reset successful for: ' . $user->email);
                }
            );
            
            if ($status === Password::PASSWORD_RESET) {
                return response()->json([
                    'success' => true,
                    'message' => 'Jelszó sikeresen megváltoztatva!'
                ]);
            }
            
            return response()->json([
                'success' => false,
                'message' => 'Érvénytelen token vagy email'
            ], 400);
            
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validációs hiba',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Reset password error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Hiba: ' . $e->getMessage()
            ], 500);
        }
    }
}