<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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
            if ($request->has('user_type')) {
                $query->where('userType', $request->user_type);
            }
            
            // Szűrés status alapján
            if ($request->has('status')) {
                $query->where('userStatus', $request->status);
            }
            
            // Rendezés
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);
            
            $users = $query->paginate($perPage);
            
            return response()->json([
                'success' => true,
                'users' => $users,
                'total' => $users->total()
            ]);
            
        } catch (\Exception $e) {
            Log::error('Admin user list error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Hiba történt'
            ], 500);
        }
    }
    
    // Felhasználó státuszának frissítése
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
                'user' => [
                    'id' => $user->id,
                    'userStatus' => $user->userStatus
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Hiba történt'
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
            return response()->json([
                'success' => false,
                'message' => 'Hiba történt'
            ], 500);
        }
    }
}