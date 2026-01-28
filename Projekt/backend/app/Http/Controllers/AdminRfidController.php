<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\User;
use App\Models\RfidCard;

class AdminRfidController extends Controller
{
    // 1) Python gateway proxy
    public function latestScan(Request $request)
    {
        $since = $request->query('since');

        $resp = Http::timeout(2)->get('http://127.0.0.1:5001/latest-scan', [
            'since' => $since
        ]);

        if (!$resp->ok()) {
            return response()->json([
                'success' => false,
                'message' => 'RFID gateway nem elérhető.'
            ], 503);
        }

        return response()->json($resp->json());
    }

    // 2) Hozzárendelés userhez (rfidCards + users.rfidCard_id)
    public function assign(Request $request, $id)
    {
        $request->validate([
            'uid' => 'required|string'
        ]);

        $uid = trim($request->input('uid'));

        // 1) rfidCards.cardNumber (NÁLAD EZ A HELYES OSZLOP)
        $card = RfidCard::firstOrCreate([
            'cardNumber' => $uid
        ]);

        // 2) foglalt-e?
        $busy = \App\Models\User::where('rfidCard_id', $card->id)
            ->where('id', '!=', $id)
            ->exists();

        if ($busy) {
            return response()->json([
                'success' => false,
                'code' => 'CARD_BUSY',
                'message' => 'A kártya foglalt.'
            ], 409);
        }

        // 3) hozzárendelés
        $user = \App\Models\User::findOrFail($id);
        $user->rfidCard_id = $card->id;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Sikeres hozzárendelés.',
            'data' => [
                'rfidCard_id' => $card->id,
                'cardNumber' => $card->cardNumber
            ]
        ]);
    }

}
