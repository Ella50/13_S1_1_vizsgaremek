<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        Log::info('Regisztráció kísérlet', $request->all());
        
        try {
            $validator = Validator::make($request->all(), [
                'firstName' => 'required|string|max:255',
                'lastName' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users', //|regex:/^[a-z]+\.[a-z]+@iskola\.hu$/
                'password' => 'required|string|min:8|confirmed',
                'userType' => 'required|in:Tanuló,Tanár,Dolgozó',
            ]);

            if ($validator->fails()) {
                Log::error('Validációs hiba', $validator->errors()->toArray());

                $errors = $validator->errors();
                if ($errors->has('email')) {
                    return response()->json([
                        'errors' => $errors,
                        'message' => /*$errors->first('email') ??*/ 'Érvénytelen email formátum'
                    ], 422);
                }
                    return response()->json([
                        'errors' => $validator->errors(),
                        'message' => 'Validációs hiba'
                    ], 422);
            }

            $user = User::create([
                'firstName' => $request->firstName,      
                'lastName' => $request->lastName,  
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'userType' => $request->userType,      
                'userStatus' => 'inactive',
                // A többi mező nullable, így automatikusan null lesz
                'city_id' => null,
                'address' => null,
                'thirdName' => null,
                'rfidCard_id' => null,
                'class_id' => null,
                'group_id' => null,
                'hasDiscount' => false,
            ]);

            Log::info('Felhasználó létrehozva', ['user_id' => $user->id]);

            // Sanctum token létrehozása
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'success' => true,
                'user' => [
                    'id' => $user->id,
                    'firstName' => $user->firstName,
                    'lastName' => $user->lastName,
                    'email' => $user->email,
                    'userType' => $user->userType,
                    'userStatus' => $user->userStatus
                ],
                'token' => $token,
                'message' => 'Regisztráció sikeres. Várja az admin jóváhagyását.'
            ], 201);

        } catch (\Exception $e) {
            Log::error('Regisztrációs hiba', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
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
                    'success' => false,
                    'message' => 'Hibás bejelentkezési adatok'
                ], 401);
            }

            // JAVÍTVA: 'password' mezőt ellenőrizzük, nem 'password_hash'-t!
            if (!Hash::check($request->password, $user->password)) {
                Log::warning('Hibás jelszó', ['email' => $request->email]);
                return response()->json([
                    'success' => false,
                    'message' => 'Hibás bejelentkezési adatok'
                ], 401);
            }

            // Ellenőrizzük, hogy aktív-e
            if ($user->userStatus !== 'active') {
                Log::warning('Inaktív felhasználó', ['user_id' => $user->id]);
                return response()->json([
                    'success' => false,
                    'message' => 'Felhasználó nincs aktiválva. Várja az admin jóváhagyását.'
                ], 403);
            }

            $token = $user->createToken('auth_token')->plainTextToken;

            Log::info('Sikeres bejelentkezés', ['user_id' => $user->id]);

            return response()->json([
                'success' => true,
                'user' => [
                    'id' => $user->id,
                    'firstName' => $user->firstName,
                    'lastName' => $user->lastName,
                    'email' => $user->email,
                    'userType' => $user->userType,
                    'userStatus' => $user->userStatus
                ],
                'token' => $token,
            ]);

        } catch (\Exception $e) {
            Log::error('Bejelentkezési hiba', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
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
                'success' => true,
                'message' => 'Sikeres kijelentkezés'
            ]);
        } catch (\Exception $e) {
            Log::error('Kijelentkezési hiba', [
                'message' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Hiba történt a kijelentkezés során'
            ], 500);
        }
    }

    public function me(Request $request)
    {
        return response()->json([
            'success' => true,
            'user' => $request->user()
        ]);
    }
}