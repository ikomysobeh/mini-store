<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;

class SettingController extends Controller
{
    public function index()
    {
        $settings = Setting::all()->pluck('value', 'key');

        // Add logo URL if logo setting exists
        if (isset($settings['logo']) && $settings['logo']) {
            $settings['logo_url'] = asset('storage/' . $settings['logo']);
        }

        // Add hero background URL if it exists
        if (isset($settings['hero_background_image']) && $settings['hero_background_image']) {
            $settings['hero_background_url'] = asset('storage/' . $settings['hero_background_image']);
        }

        return Inertia::render('Admin/Settings/Index', [
            'settings' => $settings,
        ]);
    }

    public function update(Request $request)
{
    $request->validate([
        // Existing validations
        'site_name' => 'required|string|max:255',
        'hero_title' => 'required|string|max:255',
        'hero_subtitle' => 'nullable|string',
        'donation_message' => 'nullable|string',
        'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        'hero_background_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
        'hero_use_background_image' => 'nullable|boolean',
        'hero_background_overlay' => 'nullable|in:light,dark,none',
        'remove_logo' => 'nullable|boolean',
        'remove_hero_background' => 'nullable|boolean',
        
        // ✅ NEW: Donation Settings Validation
        'donation_page_title' => 'nullable|string|max:255',
        'donation_page_subtitle' => 'nullable|string|max:500',
        'donation_page_message' => 'nullable|string|max:5000',
        'donation_min_amount' => 'nullable|numeric|min:1',
        'donation_enable' => 'nullable|boolean',
    ]);

    $settings = [
        'site_name' => $request->site_name,
        'hero_title' => $request->hero_title,
        'hero_subtitle' => $request->hero_subtitle,
        'donation_message' => $request->donation_message,
        
        // FIXED: Convert boolean to string properly
        'hero_use_background_image' => $request->boolean('hero_use_background_image') ? '1' : '0',
        'hero_background_overlay' => $request->hero_background_overlay ?? 'dark',
        
        // ✅ NEW: Donation Settings
        'donation_page_title' => $request->donation_page_title ?? 'Support Our Cause',
        'donation_page_subtitle' => $request->donation_page_subtitle ?? 'Your contribution makes a difference',
        'donation_page_message' => $request->donation_page_message ?? '',
        'donation_min_amount' => $request->donation_min_amount ?? '5',
        'donation_enable' => $request->boolean('donation_enable') ? '1' : '0',
    ];

    // Handle logo upload
    if ($request->hasFile('logo')) {
        $oldLogo = Setting::get('logo');
        if ($oldLogo && Storage::disk('public')->exists($oldLogo)) {
            Storage::disk('public')->delete($oldLogo);
        }

        $logoPath = $request->file('logo')->store('settings', 'public');
        $settings['logo'] = $logoPath;
    }

    // Handle logo removal
    if ($request->boolean('remove_logo')) {
        $oldLogo = Setting::get('logo');
        if ($oldLogo && Storage::disk('public')->exists($oldLogo)) {
            Storage::disk('public')->delete($oldLogo);
        }
        $settings['logo'] = null;
    }

    // Handle hero background image upload
    if ($request->hasFile('hero_background_image')) {
        $oldBackground = Setting::get('hero_background_image');
        if ($oldBackground && Storage::disk('public')->exists($oldBackground)) {
            Storage::disk('public')->delete($oldBackground);
        }

        $backgroundPath = $request->file('hero_background_image')->store('heroes', 'public');
        $settings['hero_background_image'] = $backgroundPath;
    }

    // Handle hero background removal
    if ($request->boolean('remove_hero_background')) {
        $oldBackground = Setting::get('hero_background_image');
        if ($oldBackground && Storage::disk('public')->exists($oldBackground)) {
            Storage::disk('public')->delete($oldBackground);
        }
        $settings['hero_background_image'] = null;
        $settings['hero_use_background_image'] = '0'; // FIXED: Use string '0'
    }

    // DEBUG: Log what we're saving
    Log::info('Saving settings:', $settings);

    // Save all settings
    foreach ($settings as $key => $value) {
        Setting::set($key, $value, 'text', true); // ✅ FIXED: Changed 'string' to 'text'
    }

    return back()->with('success', 'Settings updated successfully');
}


}
