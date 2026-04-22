<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckAdmin
{
    public function handle(Request $request, Closure $next)
    {
        // Be van-e jelentkezve
        if (!$request->user()) {
            return response()->json([
                'success' => false,
                'message' => 'Nincs jogosultság'
            ], 401);
        }
        
        // Admin-e
        if ($request->user()->userType !== 'Admin') {
            return response()->json([
                'success' => false,
                'message' => 'Csak adminok férhetnek hozzá'
            ], 403);
        }
        
        return $next($request);
    }
}