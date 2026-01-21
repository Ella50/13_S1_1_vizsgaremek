<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
class VerySimplePasswordController extends Controller
{
    public function sendReset(Request $request) {
        return response()->json([
            "success" => true,
            "message" => "This works!",
            "email" => $request->input("email", "no email provided")
        ]);
    }
}
