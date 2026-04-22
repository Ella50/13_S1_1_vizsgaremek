<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;
use App\Models\City;
use App\Models\County;

class UserController extends Controller
{

    public function me(Request $request)
    {
        try {
            $user = $request->user()->load(['city',  'rfidCard']);
            
            return response()->json([
                'success' => true,
                'data' => $user,
                'message' => 'Felhasználói adatok betöltve'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Hiba történt az adatok betöltése során: ' . $e->getMessage()
            ], 500);
        }
    }


    public function updateProfile(Request $request)
    {
        $user = $request->user();
        
        $validator = Validator::make($request->all(), [
            'address' => 'sometimes|nullable|string|max:255',
            'city_id' => 'sometimes|nullable|exists:cities,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validációs hiba',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user->update($request->only(['address', 'city_id']));
            
            $user->load(['city', 'rfidCard']);

            return response()->json([
                'success' => true,
                'data' => $user,
                'message' => 'Profil sikeresen frissítve'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Hiba történt a profil frissítése során: ' . $e->getMessage()
            ], 500);
        }
    }

        
    // Összes felhasználó lekérése
     
    public function index()
    {
        try {
            $users = User::with(['city', 'rfidCard'])->get();
            
            return response()->json([
                'success' => true,
                'data' => $users,
                'message' => 'Felhasználók sikeresen betöltve'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Hiba történt a felhasználók betöltése során: ' . $e->getMessage()
            ], 500);
        }
    }

    // Új felhasználó létrehozása
     
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'firstName' => 'required|string|max:255',
            'lastName' => 'required|string|max:255',
            'thirdName' => 'nullable|string|max:255',
            'city_id' => 'required|integer|exists:city,id',
            'address' => 'nullable|string|max:255',
            'email' => 'required|email|unique:user,email',
            'password' => 'required|string|min:8',
            'userType' => 'required|in:Tanuló,Tanár,Admin',
            'rfidCard_id' => 'required|integer|exists:rfidcard,id',
            'status' => 'required|in:active,inactive,suspended',
            'hasDiscount' => 'sometimes|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validációs hiba',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $userData = $request->all();
            $userData['password'] = Hash::make($request->password);

            $user = User::create($userData);

      
            $user->load(['city', 'rfidCard']);

            return response()->json([
                'success' => true,
                'data' => $user,
                'message' => 'Felhasználó sikeresen létrehozva'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Hiba történt a felhasználó létrehozása során: ' . $e->getMessage()
            ], 500);
        }
    }

    // Egy felhasználó lekérése
     
    public function show($id)
    {
        try {
            $user = User::with(['city', 'rfidCard'])->find($id);
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Felhasználó nem található'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $user,
                'message' => 'Felhasználó sikeresen betöltve'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Hiba történt a felhasználó betöltése során: ' . $e->getMessage()
            ], 500);
        }
    }

    //Felhasználó frissítése
     
    public function update(Request $request, $id)
    {
        $user = User::find($id);
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Felhasználó nem található'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'firstName' => 'sometimes|string|max:255',
            'lastName' => 'sometimes|string|max:255',
            'thirdName' => 'sometimes|string|max:255',
            'city_id' => 'sometimes|integer|exists:city,id',
            'address' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:user,email,' . $id,
            'password' => 'sometimes|string|min:8',
            'userType' => 'sometimes|in:Tanuló,Tanár,Admin',
            'rfidCard_id' => 'sometimes|integer|exists:rfidcard,id',
            'status' => 'sometimes|in:active,inactive,suspended',
            'hasDiscount' => 'sometimes|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validációs hiba',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $userData = $request->all();

            if ($request->has('password')) {
                $userData['password'] = Hash::make($request->password);
            }

            $user->update($userData);

            $user->load(['city', 'rfidCard']);

            return response()->json([
                'success' => true,
                'data' => $user,
                'message' => 'Felhasználó sikeresen frissítve'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Hiba történt a felhasználó frissítése során: ' . $e->getMessage()
            ], 500);
        }
    }

    //Felhasználó törlése
    
    public function destroy($id)
    {
        try {
            $user = User::find($id);
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Felhasználó nem található'
                ], 404);
            }

            $user->delete();

            return response()->json([
                'success' => true,
                'message' => 'Felhasználó sikeresen törölve'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Hiba történt a felhasználó törlése során: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getByType($type)
    {
        try {
            $validTypes = ['Tanuló', 'Tanár', 'Admin'];
            
            if (!in_array($type, $validTypes)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Érvénytelen felhasználó típus'
                ], 400);
            }

            $users = User::with(['city', 'rfidCard'])
                        ->where('userType', $type)
                        ->get();

            return response()->json([
                'success' => true,
                'data' => $users,
                'message' => $type . ' típusú felhasználók betöltve'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Hiba történt a felhasználók betöltése során: ' . $e->getMessage()
            ], 500);
        }
    }


    public function getByStatus($status)
    {
        try {
            $validStatuses = ['active', 'inactive', 'suspended'];
            
            if (!in_array($status, $validStatuses)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Érvénytelen státusz'
                ], 400);
            }

            $users = User::with(['city', 'rfidCard'])
                        ->where('status', $status)
                        ->get();

            return response()->json([
                'success' => true,
                'data' => $users,
                'message' => $status . ' státuszú felhasználók betöltve'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Hiba történt a felhasználók betöltése során: ' . $e->getMessage()
            ], 500);
        }
    }

  
    public function getCounties()
    {
        try {
            $counties = County::orderBy('countyName')->get(['id', 'countyName']);
            
            return response()->json([
                'success' => true,
                'data' => $counties
            ]);
        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'Hiba történt a megyék betöltése során'
            ], 500);
        }
    }


    public function getCitiesByCounty($county_id)
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
                ->get(['id', 'cityName', 'zipCode', 'county_id']); 
            
            return response()->json([
                'success' => true,
                'data' => $cities
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Hiba történt a városok betöltése során'
            ], 500);
        }
    }


    public function searchCities(Request $request)
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
            
            return response()->json([
                'success' => false,
                'message' => 'Hiba történt a városok keresése során'
            ], 500);
        }
    }


    public function changePassword(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'current_password' => 'required|string',
                'new_password' => 'required|string|min:8|confirmed',
            ], [
                'current_password.required' => 'A jelenlegi jelszó megadása kötelező',
                'new_password.required' => 'Az új jelszó megadása kötelező',
                'new_password.min' => 'Az új jelszónak legalább 8 karakter hosszúnak kell lennie',
                'new_password.confirmed' => 'Az új jelszó megerősítése nem egyezik',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validációs hiba',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            $user = $request->user();
            
            if (!Hash::check($request->current_password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'A jelenlegi jelszó helytelen',
                    'errors' => [
                        'current_password' => ['A megadott jelenlegi jelszó helytelen']
                    ]
                ], 422);
            }
            

            $user->password = Hash::make($request->new_password);
            $user->save();
            

            return response()->json([
                'success' => true,
                'message' => 'Jelszó sikeresen megváltoztatva'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Hiba történt a jelszó megváltoztatása során: ' . $e->getMessage()
            ], 500);
        }
    }

}