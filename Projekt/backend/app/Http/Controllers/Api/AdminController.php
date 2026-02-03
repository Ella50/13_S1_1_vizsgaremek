<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\County;
use App\Models\City;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AdminController extends Controller
{
    // Összes felhasználó lekérdezése
    public function getUsers(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 15);
            $search = $request->get('search', '');
            
            $query = User::query();
            
            // Keresés
            if ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('firstName', 'LIKE', "%{$search}%")
                      ->orWhere('lastName', 'LIKE', "%{$search}%")
                      ->orWhere('email', 'LIKE', "%{$search}%");
                });
            }
            
            // Szűrés userType alapján
            if ($request->has('userType') && $request->userType !== '') {
                $query->where('userType', $request->userType);
            }
            
            // Szűrés userStatus alapján
            if ($request->has('userStatus') && $request->userStatus !== '') {
                $query->where('userStatus', $request->userStatus);
            }
            
            // Rendezés
            $sortBy = $request->get('sort_by', 'id');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);
            
            $users = $query->paginate($perPage);
            
            return response()->json([
                'success' => true,
                'data' => $users->items(),
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
                'from' => $users->firstItem(),
                'to' => $users->lastItem()
            ]);
            
        } catch (\Exception $e) {
            Log::error('Admin user list error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Hiba történt a felhasználók betöltése során'
            ], 500);
        }
    }
    
    /**
     * Megyék lekérdezése
     */
    public function getCounties()
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
            Log::error('Cities by county load error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Hiba történt a városok betöltése során',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Városok keresése (opcionális, ha keresni szeretnél)
     */
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
            Log::error('Cities search error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Hiba történt a városok keresése során'
            ], 500);
        }
    }
    
    // Felhasználó státusz frissítése
    public function updateUserStatus(Request $request, $id)
    {
        try {
            $request->validate([
                'userStatus' => 'required|in:active,inactive,suspended'
            ]);
            
            $user = User::findOrFail($id);
            
            // Admin nem módosíthatja saját státuszát
            if ($user->id === $request->user()->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nem módosíthatod a saját státuszod'
                ], 400);
            }
            
            $oldStatus = $user->userStatus;
            $user->userStatus = $request->userStatus;
            $user->save();
            
            Log::info('User status updated', [
                'user_id' => $user->id,
                'from' => $oldStatus,
                'to' => $user->userStatus,
                'admin_id' => $request->user()->id
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Felhasználó státusza frissítve',
                'user' => $user->fresh()
            ]);
            
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validációs hiba',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('User status update error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Hiba történt a státusz frissítése során'
            ], 500);
        }
    }

    public function bulkUpdateUserStatus(Request $request)
    {
        $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
            'userStatus' => 'required|in:active,inactive,suspended'
        ]);
        
        try {
            User::whereIn('id', $request->user_ids)
                ->update(['userStatus' => $request->userStatus]);
                
            return response()->json([
                'success' => true,
                'message' => count($request->user_ids) . ' felhasználó státusza frissítve',
                'updated_count' => count($request->user_ids)
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Hiba történt a tömeges frissítés során: ' . $e->getMessage()
            ], 500);
        }
    }
    
    // Felhasználó részletes adatainak lekérése
    public function getUserDetails($id)
{
    try {
        Log::info('getUserDetails called', ['id' => $id]);
        
        if (!is_numeric($id)) {
            return response()->json([
                'success' => false,
                'message' => 'Érvénytelen felhasználó azonosító'
            ], 400);
        }
        
        $user = User::with([
            'city', 
            'city.county', // <-- A county-t is betöltjük a városon keresztül
            'studentClass', 
            'group', 
            'rfidCard'
        ])->find($id);
        
        if (!$user) {
            Log::warning('User not found', ['id' => $id]);
            return response()->json([
                'success' => false,
                'message' => 'Felhasználó nem található'
            ], 404);
        }
        
        Log::info('User found', ['id' => $user->id, 'name' => $user->firstName]);
        
        return response()->json([
            'success' => true,
            'data' => $user
        ]);
        
    } catch (\Exception $e) {
        Log::error('getUserDetails error: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Hiba történt a felhasználó betöltése során'
        ], 500);
    }
}
    
    // Felhasználó frissítése
    public function updateUser(Request $request, $id)
    {
        try {
            Log::info('updateUser called', ['id' => $id, 'data' => $request->all()]);
            
            $user = User::findOrFail($id);
            
            // Validáció - BŐVÍTVE county_id és city_id mezőkkel
            $validator = Validator::make($request->all(), [
                'firstName' => 'required|string|max:255',
                'lastName' => 'required|string|max:255',
                'thirdName' => 'nullable|string|max:255',
                'email' => 'required|email|unique:users,email,' . $id,
                'userType' => 'required|in:Tanuló,Tanár,Admin,Konyha,Dolgozó,Külsős',
                'userStatus' => 'required|in:active,inactive,suspended',
                'address' => 'nullable|string|max:500',
                'hasDiscount' => 'boolean',
                'county_id' => 'nullable|exists:counties,id',
                'city_id' => 'nullable|exists:cities,id',
                'password' => 'nullable|string|min:8|confirmed'
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validációs hiba',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            $data = $request->all();
            
            // Jelszó titkosítása, ha meg van adva
            if ($request->has('password') && $request->password) {
                $data['password'] = Hash::make($request->password);
            } else {
                unset($data['password']);
            }
            
            // Jelszó megerősítés mező eltávolítása
            unset($data['password_confirmation']);
            
            $user->update($data);
            
            Log::info('User updated successfully', [
                'user_id' => $user->id,
                'updated_fields' => array_keys($data)
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Felhasználó sikeresen frissítve',
                'data' => $user->fresh(['city', 'city.county'])
            ]);
            
        } catch (\Exception $e) {
            Log::error('updateUser error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Hiba történt a felhasználó frissítése során: ' . $e->getMessage()
            ], 500);
        }
    }
    
    // Felhasználó törlése
    public function deleteUser(Request $request, $id)
    {
        try {
            $user = User::findOrFail($id);
            
            // Admin nem törölheti magát
            if ($user->id === $request->user()->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nem törölheted a saját fiókodat'
                ], 400);
            }
            
            $user->delete();
            
            Log::info('User deleted by admin', [
                'deleted_user_id' => $id,
                'admin_id' => $request->user()->id
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Felhasználó sikeresen törölve'
            ]);
            
        } catch (\Exception $e) {
            Log::error('deleteUser error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Hiba történt a felhasználó törlése során'
            ], 500);
        }
    }
}