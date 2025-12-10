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
        // Alap validáció - csak email formátum
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

        // MINDIG sikeres válasz (biztonsági okokból)
        return response()->json([
            'success' => true,
            'message' => 'Ha létezik ilyen email cím a rendszerünkben, elküldtük a visszaállítási linket.',
            'timestamp' => date('Y-m-d H:i:s'),
            'received_email' => $request->input('email')
        ], 200);
    }

    /**
     * Jelszó visszaállítása
     */
    public function reset(Request $request)
    {
        return response()->json([
            'success' => true,
            'message' => 'Jelszó visszaállítási endpoint (még nincs implementálva)'
        ], 200);
    }
}