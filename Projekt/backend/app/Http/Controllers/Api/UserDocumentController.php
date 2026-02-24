<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class UserDocumentController extends Controller
{
    /**
     * Cukorbetegség igazolás feltöltése
     */
    public function uploadDiabetesCertificate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'certificate' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120', // max 5MB
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validációs hiba',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = Auth::user();
            
            // Régi fájl törlése, ha van
            if ($user->diabetes_certificate) {
                Storage::disk('public')->delete($user->diabetes_certificate);
            }

            // Új fájl feltöltése
            $path = $request->file('certificate')->store('certificates/diabetes', 'public');
            
            // Felhasználó frissítése - elmentjük a fájl elérési útját
            $user->diabetes_certificate = $path;
            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'Cukorbetegség igazolás sikeresen feltöltve',
                'data' => [
                    'file_url' => $user->diabetes_certificate_url
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Hiba történt a feltöltés során: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Kedvezmény igazolás feltöltése
     */
    public function uploadDiscountCertificate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'certificate' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validációs hiba',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = Auth::user();
            
            // Régi fájl törlése
            if ($user->discount_certificate) {
                Storage::disk('public')->delete($user->discount_certificate);
            }

            // Új fájl feltöltése
            $path = $request->file('certificate')->store('certificates/discount', 'public');
            
            $user->discount_certificate = $path;
            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'Kedvezmény igazolás sikeresen feltöltve',
                'data' => [
                    'file_url' => $user->discount_certificate_url
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Hiba történt a feltöltés során: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Dokumentum törlése
     */
    public function deleteDocument(Request $request, $type)
    {
        $validTypes = ['diabetes', 'discount'];
        
        if (!in_array($type, $validTypes)) {
            return response()->json([
                'success' => false,
                'message' => 'Érvénytelen dokumentum típus'
            ], 400);
        }

        try {
            $user = Auth::user();
            
            $field = $type . '_certificate';
            
            if ($user->$field) {
                Storage::disk('public')->delete($user->$field);
                $user->$field = null;
                $user->save();
            }

            return response()->json([
                'success' => true,
                'message' => 'Dokumentum sikeresen törölve'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Hiba történt a törlés során: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Dokumentum megtekintése
     */
    public function viewDocument($type)
    {
        $validTypes = ['diabetes', 'discount'];
        
        if (!in_array($type, $validTypes)) {
            return response()->json([
                'success' => false,
                'message' => 'Érvénytelen dokumentum típus'
            ], 400);
        }

        try {
            $user = Auth::user();
            $field = $type . '_certificate';
            
            if (!$user->$field) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dokumentum nem található'
                ], 404);
            }

            // Fájl megtekintése (nem letöltés)
            return response()->file(storage_path('app/public/' . $user->$field));

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Hiba történt a dokumentum megnyitása során'
            ], 500);
        }
    }
}