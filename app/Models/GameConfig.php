<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GameConfig extends Model
{
    protected $fillable = ['key', 'value'];

    public static function getValue($key, $default = null)
    {
        return cache()->rememberForever("game_config_$key", function () use ($key, $default) {
            return self::where('key', $key)->value('value') ?? $default;
        });
    }

    public static function setValue($key, $value)
    {
        $config = self::updateOrCreate(['key' => $key], ['value' => $value]);
        cache()->forever("game_config_$key", $value);
        return $config;
    }
}
