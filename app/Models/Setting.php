<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = ['key', 'value', 'type', 'is_public'];

    protected $casts = [
        'is_public' => 'boolean',
    ];

    /**
     * Get a setting value by key
     */
    public static function get($key, $default = null)
    {
        $setting = self::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }

    /**
     * Set a setting value
     */
    public static function set($key, $value, $type = 'string', $isPublic = true)
    {
        return self::updateOrCreate(
            ['key' => $key],
            [
                'value' => $value,
                'type' => $type,
                'is_public' => $isPublic,
            ]
        );
    }

    /**
     * Get public settings (for frontend)
     */
    public static function public()
    {
        return self::where('is_public', true);
    }
}
