<?php

namespace App\Listeners;

use App\Events\PlayerRegistered;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Models\GameConfig;

class AssignDice
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(PlayerRegistered $event)
    {
        $initialDiceCount = GameConfig::getValue('initial_dice_count', 3);
        $event->player->update(['dice_count' => $initialDiceCount]);
    }
}
