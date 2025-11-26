<?php 

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Enums\UserType;
use App\Enums\UserStatus;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'firstName' => 'required|string|max:255',
            'lastName' => 'required|string|max:255',
            'email' => 'required|email|unique:user,email',
            'password' => 'required|string|min:8|confirmed',
            'city_id' => 'required|integer|exists:city,id',
            'address' => 'required|string|max:255',
            'userType' => 'required|in:Tanuló,Tanár,Admin,Külsős,Dolgozó,Konyha',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validációs hiba',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = User::create([
                "firstName" => $request->firstName,
                "lastName" => $request->lastName,
                "email" => $request->email,
                "password" => Hash::make($request->password), // Csak itt hash-eljük
                "city_id" => $request->city_id,
                "address" => $request->address,
                "userType" => $request->userType,
                "status" => UserStatus::ACTIVE->value,
                "hasDiscount" => false
            ]);

            $token = $user->createToken("auth_token")->plainTextToken;

            return response()->json([
                "message" => "Sikeres regisztráció",
                "user" => $user,
                "token" => $token
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                "message" => "Hiba történt a regisztráció során",
                "error" => $e->getMessage()
            ], 500);
        }
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                "message" => "Validációs hiba",
                "errors" => $validator->errors()
            ], 422);
        }

        $user = User::where("email", $request->email)->first();

        if (!$user) {
            return response()->json([
                "message" => "Hibás email vagy jelszó"
            ], 401);
        }

        // Hash ellenőrzés - most már helyesen működik
        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                "message" => "Hibás email vagy jelszó"
            ], 401);
        }

        // Ellenőrizzük, hogy aktív-e a felhasználó
        if ($user->status !== UserStatus::ACTIVE->value) {
            return response()->json([
                "message" => "A fiók nem aktív. Kérjük, lépj kapcsolatba az adminisztrátorral."
            ], 403);
        }

        $token = $user->createToken("auth_token")->plainTextToken;

        return response()->json([
            "message" => "Sikeres bejelentkezés",
            "user" => $user,
            "token" => $token,
            "token_type" => "Bearer"
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            "message" => "Sikeres kijelentkezés"
        ]);
    }

    public function user(Request $request)
    {
        return response()->json([
            "user" => $request->user()->load(['city', 'studentClass', 'group', 'rfidCard'])
        ]);
    }

    public function users(){
        return response()->json(User::all());
    }
}