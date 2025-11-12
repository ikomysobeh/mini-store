<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class DonationSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $donationSettings = [
            [
                'key' => 'donation_page_title',
                'value' => 'Support Our Cause',
                'type' => 'text',  // CHANGED from 'string' to 'text'
                'is_public' => true,
            ],
            [
                'key' => 'donation_page_subtitle',
                'value' => 'Your contribution makes a real difference',
                'type' => 'text',  // CHANGED from 'string' to 'text'
                'is_public' => true,
            ],
            [
                'key' => 'donation_page_message',
                'value' => 'Thank you for considering a donation to our cause. Your generous support helps us continue our important work and make a positive impact in our community. Every contribution, no matter the size, brings us closer to our goals and helps those in need.',
                'type' => 'text',
                'is_public' => true,
            ],
            [
                'key' => 'donation_min_amount',
                'value' => '5',
                'type' => 'text',  // CHANGED from 'number' to 'text'
                'is_public' => true,
            ],
            [
                'key' => 'donation_enable',
                'value' => '1', // 1 = enabled, 0 = disabled
                'type' => 'text',  // CHANGED from 'boolean' to 'text'
                'is_public' => true,
            ],
        ];

        foreach ($donationSettings as $setting) {
            Setting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }

        $this->command->info('✅ Donation settings created successfully!');
    }
}
