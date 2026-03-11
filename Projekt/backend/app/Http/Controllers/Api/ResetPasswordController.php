<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;

class ResetPasswordController extends Controller
{
    /**
     * Jelszó visszaállító link küldése
     */
    public function sendResetLink(Request $request)
    {
        try {
            // Validáció
            $request->validate([
                'email' => 'required|email|exists:users,email'
            ], [
                'email.exists' => 'Nem található ilyen email cím a rendszerünkben.'
            ]);
            
            // Link küldése
            $status = Password::sendResetLink(
                $request->only('email')
            );
            
            Log::info('Password reset requested', ['email' => $request->email, 'status' => $status]);
            
            if ($status === Password::RESET_LINK_SENT) {
                return response()->json([
                    'success' => true,
                    'message' => 'Jelszó visszaállítási link elküldve az email címedre!'
                ]);
            }
            
            // Ha nem sikerült
            return response()->json([
                'success' => false,
                'message' => 'Hiba történt a link küldése során. Kérjük, próbáld újra később.'
            ], 500);
            
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validációs hiba',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Password reset error: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Szerver hiba: ' . $e->getMessage()
            ], 500);
        }
    }
}