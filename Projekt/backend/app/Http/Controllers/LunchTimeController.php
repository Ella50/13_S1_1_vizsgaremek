<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Carbon;
use App\Models\User;
use App\Models\Order;
use App\Models\RfidCard;

class LunchTimeController extends Controller
{
    // proxy a python gateway felé
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

    // ellenőrzés: van-e user, van-e rendelés ma, evett-e ma
   public function verify(Request $request)
    {
        $request->validate([
            'uid' => 'required|string'
        ]);

        $uid = trim($request->input('uid'));
        $today = Carbon::today();

        // 1) kártya
        $card = RfidCard::where('cardNumber', $uid)->first();
        if (!$card) {
            return response()->json([
                'success' => true,
                'data' => [
                    'status' => 'UNKNOWN_CARD',
                    'message' => 'Ismeretlen kártya.'
                ]
            ]);
        }

        // 2) user a kártyához
        $user = User::where('rfidCard_id', $card->id)->first();
        if (!$user) {
            return response()->json([
                'success' => true,
                'data' => [
                    'status' => 'UNASSIGNED_CARD',
                    'message' => 'A kártya nincs felhasználóhoz rendelve.'
                ]
            ]);
        }

        $fullName = trim($user->firstName . ' ' . $user->lastName . ' ' . ($user->thirdName ?? ''));

        // 3) Mai rendelés (mit eszik) + van-e rendelése
        $order = Order::where('user_id', $user->id)
            ->whereDate('orderDate', $today)
            ->first();

        // nincs rendelés
        if (!$order) {
            return response()->json([
                'success' => true,
                'data' => [
                    'status' => 'NO_ORDER',
                    'fullName' => $fullName,
                    'userType' => $user->userType,
                    'selectedOption' => null,
                    'hasDiabetes' => (bool)($user->hasDiabetes ?? false),
                    'canEat' => false,
                    'message' => 'Nincs mai rendelés.',
                    'lastUsedAt' => $card->lastUsedAt,
                ]
            ]);
        }

        // lemondva
        if (($order->orderStatus ?? '') === 'Lemondva' || !empty($order->cancelledAt)) {
            return response()->json([
                'success' => true,
                'data' => [
                    'status' => 'CANCELLED',
                    'fullName' => $fullName,
                    'userType' => $user->userType,
                    'selectedOption' => $order->selectedOption,
                    'hasDiabetes' => (bool)($user->hasDiabetes ?? false),
                    'canEat' => false,
                    'message' => 'A rendelés le van mondva.',
                    'lastUsedAt' => $card->lastUsedAt,
                ]
            ]);
        }

        // 4) Már evett ma? -> rfidCards.lastUsedAt alapján
        if (!empty($card->lastUsedAt) && Carbon::parse($card->lastUsedAt)->isSameDay($today)) {
            return response()->json([
                'success' => true,
                'data' => [
                    'status' => 'ALREADY_ATE',
                    'fullName' => $fullName,
                    'userType' => $user->userType,
                    'selectedOption' => $order->selectedOption,
                    'hasDiabetes' => (bool)($user->hasDiabetes ?? false),
                    'canEat' => false,
                    'message' => 'Már evett ma.',
                    'lastUsedAt' => $card->lastUsedAt,
                ]
            ]);
        }

        // 5) Ehet -> AZONNAL rögzítjük a lastUsedAt-ot
        $card->lastUsedAt = now();
        $card->save();

        return response()->json([
            'success' => true,
            'data' => [
                'status' => 'OK',
                'fullName' => $fullName,
                'userType' => $user->userType,
                'selectedOption' => $order->selectedOption,
                'hasDiabetes' => (bool)($user->hasDiabetes ?? false),
                'canEat' => true,
                'message' => 'Ehet.',
                'lastUsedAt' => $card->lastUsedAt,
            ]
        ]);
    }


    // fogyasztás rögzítése (hogy ne ehessen még egyszer)
    public function consume(Request $request)
    {
        $request->validate([
            'order_id' => 'required|integer'
        ]);

        $order = Order::findOrFail($request->order_id);

        if (!empty($order->consumedAt)) {
            return response()->json([
                'success' => false,
                'message' => 'Már evett (fogyasztás rögzítve).'
            ], 409);
        }

        $order->consumedAt = now();
        $order->save();

        return response()->json([
            'success' => true,
            'message' => 'Kiadás rögzítve.'
        ]);
    }
}
