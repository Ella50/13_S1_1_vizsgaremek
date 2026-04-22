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
    public function getAllergens()
    {
        $allergens = Allergen::orderBy('allergenName')->get();
        return response()->json($allergens);
    }

  //Allergének, cukorbeteg-e
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
            ->filter()
            ->values(); 
        
 
        $hasDiabetes = $user->hasDiabetes ?? false;

        return response()->json([
            'allergens' => $userAllergens,
            'has_diabetes' => $hasDiabetes
        ]);
    }

    // Felhasználó allergénjeinek lekérése
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
            ->filter()
            ->values();
        
        return response()->json($userAllergens);
    }


    public function addAllergen(Request $request)
    {
        $request->validate([
            'allergen_id' => 'required|exists:allergens,id'
        ]);

        $user = Auth::user();

        $exists = UserHealthRestriction::where('user_id', $user->id)
            ->where('allergen_id', $request->allergen_id)
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'Ez az allergén már hozzá van adva'], 409);
        }

        UserHealthRestriction::create([
            'user_id' => $user->id,
            'allergen_id' => $request->allergen_id
        ]);

        return response()->json(['message' => 'Allergén hozzáadva']);
    }

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

    public function updateDiabetes(Request $request)
    {
        $request->validate([
            'has_diabetes' => 'required|boolean'
        ]);

        $user = Auth::user();
        
        User::where('id', $user->id)->update([
            'hasDiabetes' => $request->has_diabetes
        ]);
        
        $user->hasDiabetes = $request->has_diabetes;

        return response()->json([
            'message' => 'Cukorbetegség állapota frissítve',
            'has_diabetes' => $user->hasDiabetes
        ]);
    }

}