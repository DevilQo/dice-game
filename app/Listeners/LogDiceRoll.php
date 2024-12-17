<?php

namespace App\Listeners;

use App\Events\DiceRolled;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Models\DiceLog;

class LogDiceRoll implements \Illuminate\Contracts\Queue\ShouldQueue
{
    public function handle(DiceRolled $event)
    {
        DiceLog::create([
            'user_id' => $event->player->id,
            'roll' => $event->roll,
        ]);
    }
}
