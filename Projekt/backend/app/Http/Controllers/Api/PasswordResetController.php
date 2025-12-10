<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PasswordResetController extends Controller
{
    /**
     * Jelszó visszaállító link küldése
     */
    public function sendResetLinkEmail(Request $request)
    {
        // 1. Alap validáció - csak email formátum ellenőrzés
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Kérjük, adj meg egy érvényes email címet.',
                'errors' => $validator->errors()
            ], 422);
        }

        // 2. MINDIG sikeres válasz (biztonsági okokból)
        // A valós rendszerben itt küldenénk emailt
        return response()->json([
            'success' => true,
            'message' => 'Ha létezik ilyen email cím a rendszerünkben, elküldtük a visszaállítási linket.',
            'timestamp' => date('Y-m-d H:i:s'),
            'received_email' => $request->input('email')
        ], 200);
    }

    /**
     * Jelszó visszaállítása - üres metódus, ha később kell
     */
    public function reset(Request $request)
    {
        return response()->json([
            'success' => true,
            'message' => 'Jelszó visszaállítási endpoint (még nincs implementálva)'
        ], 200);
    }
}
