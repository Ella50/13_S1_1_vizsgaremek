<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckKitchen
{
    public function handle(Request $request, Closure $next)
    {
        // Ellenőrizzük, hogy be van-e jelentkezve
        if (!$request->user()) {
            return response()->json([
                'success' => false,
                'message' => 'Nincs jogosultság'
            ], 401);
        }
        
        // Ellenőrizzük, hogy konyha-e
        if ($request->user()->userType !== 'Konyha') {
            return response()->json([
                'success' => false,
                'message' => 'Csak konyha dolgozók férhetnek hozzá'
            ], 403);
        }
        
        return $next($request);
    }
}