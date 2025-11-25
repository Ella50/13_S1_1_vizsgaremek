<?php 

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;


class AuthController extends Controller
{
    
    public function users(){
        return response()->json(User::all());
    }

    public function register(Request $request) {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password)
        ]);

        return response()->json(['user' => $user], 201);
    }

    public function login(Request $request) {
        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Hibás adatok'], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;
        
        return response()->json([
            'message' => 'Sikeres bejelentkezés',
            'token' => $token
        ]);
    }

}