<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log; // Naplózáshoz

class AuthController extends Controller
{
    public function register(Request $request)
    {
        Log::info('Regisztráció kísérlet', $request->all());
        
        try {
            $validator = Validator::make($request->all(), [
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8|confirmed',
                'user_type' => 'required|in:Tanuló,Külsős,Tanár,Dolgozó',
            ]);

            if ($validator->fails()) {
                Log::error('Validációs hiba', $validator->errors()->toArray());
                return response()->json([
                    'errors' => $validator->errors(),
                    'message' => 'Validációs hiba'
                ], 422);
            }

            $user = User::create([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'password_hash' => Hash::make($request->password),
                'user_type' => $request->user_type,
                'status' => 'inactive',
            ]);

            Log::info('Felhasználó létrehozva', ['user_id' => $user->id]);

            // Sanctum token létrehozása
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'user' => $user,
                'access_token' => $token,
                'token_type' => 'Bearer',
                'message' => 'Regisztráció sikeres. Várja az admin jóváhagyását.'
            ], 201);

        } catch (\Exception $e) {
            Log::error('Regisztrációs hiba', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'message' => 'Szerver hiba történt',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function login(Request $request)
    {
        Log::info('Bejelentkezés kísérlet', ['email' => $request->email]);
        
        try {
            $request->validate([
                'email' => 'required|email',
                'password' => 'required',
            ]);

            $user = User::where('email', $request->email)->first();

            if (!$user) {
                Log::warning('Felhasználó nem található', ['email' => $request->email]);
                return response()->json([
                    'message' => 'Hibás bejelentkezési adatok'
                ], 401);
            }

            if (!Hash::check($request->password, $user->password_hash)) {
                Log::warning('Hibás jelszó', ['email' => $request->email]);
                return response()->json([
                    'message' => 'Hibás bejelentkezési adatok'
                ], 401);
            }

            // Ellenőrizzük, hogy aktív-e
            if ($user->status !== 'active') {
                Log::warning('Inaktív felhasználó', ['user_id' => $user->id]);
                return response()->json([
                    'message' => 'Felhasználó nincs aktiválva. Várja az admin jóváhagyását.'
                ], 403);
            }

            $token = $user->createToken('auth_token')->plainTextToken;

            Log::info('Sikeres bejelentkezés', ['user_id' => $user->id]);

            return response()->json([
                'user' => $user,
                'access_token' => $token,
                'token_type' => 'Bearer',
            ]);

        } catch (\Exception $e) {
            Log::error('Bejelentkezési hiba', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'message' => 'Szerver hiba történt',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function logout(Request $request)
    {
        try {
            $request->user()->currentAccessToken()->delete();
            Log::info('Sikeres kijelentkezés', ['user_id' => $request->user()->id]);
            
            return response()->json([
                'message' => 'Sikeres kijelentkezés'
            ]);
        } catch (\Exception $e) {
            Log::error('Kijelentkezési hiba', [
                'message' => $e->getMessage()
            ]);
            
            return response()->json([
                'message' => 'Hiba történt a kijelentkezés során'
            ], 500);
        }
    }

    public function me(Request $request)
    {
        return response()->json($request->user());
    }
}