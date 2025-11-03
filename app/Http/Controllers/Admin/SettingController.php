<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SettingController extends Controller
{


    public function index()
    {
        $settings = Setting::all()->pluck('value', 'key');

        return Inertia::render('Admin/Settings/Index', [
            'settings' => $settings,
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'site_name' => 'required|string|max:255',
            'hero_title' => 'required|string|max:255',
            'hero_subtitle' => 'nullable|string',
            'donation_message' => 'nullable|string',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $settings = [
            'site_name' => $request->site_name,
            'hero_title' => $request->hero_title,
            'hero_subtitle' => $request->hero_subtitle,
            'donation_message' => $request->donation_message,
        ];

        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store('settings', 'public');
            $settings['logo'] = $logoPath;
        }

        foreach ($settings as $key => $value) {
            Setting::set($key, $value, 'string', true);
        }

        return back()->with('success', 'Settings updated successfully');
    }
}
