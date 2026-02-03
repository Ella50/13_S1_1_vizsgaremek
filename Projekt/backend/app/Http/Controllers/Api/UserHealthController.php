<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Models\Allergen;
use App\Models\UserHealthRestriction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;

use Illuminate\Support\Facades\Log;

class UserHealthController extends Controller
{
    // Összes allergén lekérése (mindenkinek elérhető)
    public function getAllergens()
    {
        $allergens = Allergen::orderBy('allergenName')->get();
        return response()->json($allergens);
    }

    // Felhasználó egészségügyi adatainak lekérése
    public function getUserHealthData()
    {
        $user = Auth::user();
        
        $userAllergens = UserHealthRestriction::where('user_id', $user->id)
            ->whereNotNull('allergen_id')
            ->with('allergen')
            ->get()
            ->map(function($restriction) {
                return $restriction->allergen;
            })
            ->filter();
            
        $hasDiabetes = UserHealthRestriction::where('user_id', $user->id)
            ->where('hasDiabetes', true)
            ->exists();

        return response()->json([
            'allergens' => $userAllergens,
            'has_diabetes' => $hasDiabetes
        ]);
    }

    // Felhasználó allergénjeinek lekérése (külön endpoint)
    public function getUserAllergens()
    {
        $user = Auth::user();
        
        $userAllergens = UserHealthRestriction::where('user_id', $user->id)
            ->whereNotNull('allergen_id')
            ->with('allergen')
            ->get()
            ->map(function($restriction) {
                return $restriction->allergen;
            })
            ->filter();
        
        return response()->json($userAllergens);
    }

    // Allergén hozzáadása
    public function addAllergen(Request $request)
    {
        $request->validate([
            'allergen_id' => 'required|exists:allergens,id'
        ]);

        $user = Auth::user();

        // Ellenőrizzük, hogy már nem létezik-e
        $exists = UserHealthRestriction::where('user_id', $user->id)
            ->where('allergen_id', $request->allergen_id)
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'Ez az allergén már hozzá van adva'], 409);
        }

        UserHealthRestriction::create([
            'user_id' => $user->id,
            'allergen_id' => $request->allergen_id,
            'hasDiabetes' => false
        ]);

        return response()->json(['message' => 'Allergén hozzáadva']);
    }

    // Allergén eltávolítása
    public function removeAllergen($id)
    {
        $user = Auth::user();
        
        $deleted = UserHealthRestriction::where('user_id', $user->id)
            ->where('allergen_id', $id)
            ->delete();

        if ($deleted) {
            return response()->json(['message' => 'Allergén eltávolítva']);
        }

        return response()->json(['message' => 'Allergén nem található'], 404);
    }

    // Cukorbetegség frissítése
    public function updateDiabetes(Request $request)
    {
        $request->validate([
            'has_diabetes' => 'required|boolean'
        ]);

        $user = Auth::user();

        // Töröljük az összes korábbi cukorbetegség jelölést
        UserHealthRestriction::where('user_id', $user->id)
            ->where('hasDiabetes', true)
            ->delete();

        // Ha cukorbeteg, akkor létrehozunk egy új bejegyzést
        if ($request->has_diabetes) {
            UserHealthRestriction::create([
                'user_id' => $user->id,
                'allergen_id' => null,
                'hasDiabetes' => true
            ]);
        }

        return response()->json(['message' => 'Cukorbetegség állapota frissítve']);
    }

}