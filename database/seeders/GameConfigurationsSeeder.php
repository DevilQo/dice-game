<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\GameConfig;

class GameConfigurationsSeeder extends Seeder
{
    public function run()
    {
        GameConfig::setValue('initial_dice_count', '3');
        GameConfig::setValue('dice_cost_per_roll', '1');
    }
}
