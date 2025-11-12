<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\AddPlayerRequest;
use App\Models\Players;

class PlayerController extends Controller
{
    //
    public function index()
    {
        return Players::all();
    }
    public function show(Players $player)
    {
        return $player;
    }
    public function store(AddPlayerRequest $request)
    {
        Players::create($request->validated());
        return response()->json("Player added");
    }

    public function update(AddPlayerRequest $request,Players $player)
    {
        $player->where('id', $player->id)->update($request->validated());
        return response()->json("Player updated");
    }
    public function destroy(Players $player)
    {
        $player->delete();
        return response()->json("Player deleted");
    }
}
