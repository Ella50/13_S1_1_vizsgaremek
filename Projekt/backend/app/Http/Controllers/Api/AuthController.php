<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use App\Models\County;
use App\Models\City;

class AuthController extends Controller
{
    public function register(Request $request)
{
    Log::info('Regisztráció kísérlet', $request->all());
    
    try {
        $validator = Validator::make($request->all(), [
            'firstName' => 'required|string|max:255',
            'lastName' => 'required|string|max:255',
            'thirdName' => 'nullable|string|max:255', // HIÁNYZOTT!
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'userType' => 'required|in:Tanuló,Tanár,Dolgozó',
            'county_id' => 'required|exists:counties,id', // HIÁNYZOTT!
            'city_id' => 'required|exists:cities,id', // HIÁNYZOTT!
            'address' => 'required|string|max:500', // HIÁNYZOTT!
        ]);

        if ($validator->fails()) {
            Log::error('Validációs hiba', $validator->errors()->toArray());
            return response()->json([
                'errors' => $validator->errors(),
                'message' => 'Validációs hiba'
            ], 422);
        }

        $user = User::create([
            'firstName' => $request->firstName,      
            'lastName' => $request->lastName,
            'thirdName' => $request->thirdName, 
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'userType' => $request->userType,      
            'userStatus' => 'inactive',
            'city_id' => $request->city_id, 
            'address' => $request->address, 
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




    public function getCountiesReg()
    {
        try {
            $counties = County::orderBy('countyName')->get(['id', 'countyName']);
            
            return response()->json([
                'success' => true,
                'data' => $counties
            ]);
        } catch (\Exception $e) {
            Log::error('Counties load error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Hiba történt a megyék betöltése során',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Városok lekérdezése megye alapján
     */
    public function getCitiesByCountyReg($county_id)
    {
        try {
            if (!is_numeric($county_id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Érvénytelen megye azonosító'
                ], 400);
            }
            
            $cities = City::where('county_id', $county_id)
                ->orderBy('cityName')
                ->get(['id', 'cityName', 'zipCode']);
            
            return response()->json([
                'success' => true,
                'data' => $cities
            ]);
        } catch (\Exception $e) {
            Log::error('Cities by county load error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Hiba történt a városok betöltése során'
            ], 500);
        }
    }
        
    /**
     * Városok keresése
     */
    public function searchCitiesReg(Request $request)
    {
        try {
            $search = $request->get('search', '');
            
            $query = City::with('county');
            
            if ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('cityName', 'LIKE', "%{$search}%")
                      ->orWhere('zipCode', 'LIKE', "%{$search}%")
                      ->orWhereHas('county', function($q2) use ($search) {
                          $q2->where('countyName', 'LIKE', "%{$search}%");
                      });
                });
            }
            
            $cities = $query->orderBy('cityName')->limit(50)->get();
            
            return response()->json([
                'success' => true,
                'data' => $cities
            ]);
        } catch (\Exception $e) {
            Log::error('Cities search error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Hiba történt a városok keresése során'
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

            if (!Hash::check($request->password, $user->password)) {
                Log::warning('Hibás jelszó', ['email' => $request->email]);
                return response()->json([
                    'success' => false,
                    'message' => 'Hibás bejelentkezési adatok'
                ], 401);
            }


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