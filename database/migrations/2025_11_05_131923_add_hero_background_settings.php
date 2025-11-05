<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Setting;

return new class extends Migration
{
    public function up()
    {
        // SAFE: Use updateOrCreate instead of create
        Setting::updateOrCreate(
            ['key' => 'hero_background_image'],
            [
                'value' => null,
                'type' => 'string',
                'is_public' => true,
            ]
        );

        Setting::updateOrCreate(
            ['key' => 'hero_use_background_image'],
            [
                'value' => 'false',
                'type' => 'boolean',
                'is_public' => true,
            ]
        );

        Setting::updateOrCreate(
            ['key' => 'hero_background_overlay'],
            [
                'value' => 'dark',
                'type' => 'string',
                'is_public' => true,
            ]
        );
    }

    public function down()
    {
        Setting::whereIn('key', [
            'hero_background_image',
            'hero_use_background_image',
            'hero_background_overlay'
        ])->delete();
    }
};
