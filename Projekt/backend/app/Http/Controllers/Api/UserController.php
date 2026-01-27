<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Api\AuthController;

class UserController extends Controller
{
    /**
     * Összes felhasználó lekérése
     */
    public function index()
    {
        try {
            $users = User::with(['city', 'studentClass', 'group', 'rfidCard'])->get();
            
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

    /**
     * Új felhasználó létrehozása
     */
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
            'class_id' => 'nullable|integer|exists:class,id',
            'group_id' => 'nullable|integer|exists:group,id',
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

            // Újra betöltjük a kapcsolatokkal
            $user->load(['city', 'studentClass', 'group', 'rfidCard']);

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

    /**
     * Egy felhasználó lekérése
     */
    public function show($id)
    {
        try {
            $user = User::with(['city', 'studentClass', 'group', 'rfidCard'])->find($id);
            
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

    /**
     * Felhasználó frissítése
     */
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
            'class_id' => 'sometimes|integer|exists:class,id',
            'group_id' => 'sometimes|integer|exists:group,id',
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

            // Jelszó titkosítása, ha megváltozott
            if ($request->has('password')) {
                $userData['password'] = Hash::make($request->password);
            }

            $user->update($userData);

            // Újra betöltjük a kapcsolatokkal
            $user->load(['city', 'studentClass', 'group', 'rfidCard']);

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

    /**
     * Felhasználó törlése
     */
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

    /**
     * Felhasználók keresése típus szerint
     */
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

            $users = User::with(['city', 'studentClass', 'group', 'rfidCard'])
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

    /**
     * Felhasználók keresése státusz szerint
     */
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

            $users = User::with(['city', 'studentClass', 'group', 'rfidCard'])
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

    /**
     * Osztályonkénti felhasználók
     */
    public function getByClass($classId)
    {
        try {
            $users = User::with(['city', 'studentClass', 'group', 'rfidCard'])
                        ->where('class_id', $classId)
                        ->get();

            return response()->json([
                'success' => true,
                'data' => $users,
                'message' => 'Osztályhoz tartozó felhasználók betöltve'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Hiba történt a felhasználók betöltése során: ' . $e->getMessage()
            ], 500);
        }
    }

    /*public function changePassword(Request $request)
    {
        try {
            $request->validate([
                'current_password' => 'required',
                'new_password' => 'required|min:8|confirmed',
            ]);
            
            $user = $request->user();
            
            if (!Hash::check($request->current_password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'A jelenlegi jelszó helytelen'
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
                'message' => 'Hiba történt'
            ], 500);
        }
    }*/

}