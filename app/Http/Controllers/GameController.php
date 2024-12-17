<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\GameConfig;
use App\Events\DiceRolled;

class GameController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function showDice()
    {
        return view('game.dice', ['dice_count' => Auth::user()->dice_count]);
    }

    public function rollDice()
    {
        $user = Auth::user();
        $diceCost = GameConfig::getValue('dice_cost_per_roll', 1);

        if (!$user->canRollDice($diceCost)) {
            return redirect()->back()->withErrors(['error' => 'Not enough dice to roll!']);
        }

        $roll = rand(1, 6);
        $user->decrement('dice_count', $diceCost);

        event(new DiceRolled($user, $roll));

        return redirect()->back()->with('message', "You rolled a $roll!");
    }
}
