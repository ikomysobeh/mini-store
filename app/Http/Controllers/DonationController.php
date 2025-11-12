<?php

namespace App\Http\Controllers;

use App\Models\Donation;
use App\Models\Setting;
use App\Models\Category;
use App\Http\Requests\DonationRequest;
use App\Services\StripeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class DonationController extends Controller
{
    protected $stripeService;

    public function __construct(StripeService $stripeService)
    {
        $this->stripeService = $stripeService;
    }

    /**
     * Display the donation form page
     */
    public function index()
    {
        // Get donation settings
        $donationSettings = Setting::whereIn('key', [
            'donation_page_title',
            'donation_page_subtitle',
            'donation_page_message',
            'donation_min_amount',
            'donation_enable',
        ])->pluck('value', 'key')->toArray();

        // Get general settings for navbar
        $settings = Setting::public()->pluck('value', 'key')->toArray();
        
        // Merge donation settings into main settings
        $settings = array_merge($settings, $donationSettings);

        // Add logo URL if exists
        if (isset($settings['logo'])) {
            $settings['logo_url'] = asset('storage/' . $settings['logo']);
        }

        // Get categories for navbar
        $categories = Category::active()->ordered()->get();

        // Get authenticated user
        $user = auth()->user();

        return Inertia::render('Donation', [
            'settings' => $settings,
            'categories' => $categories,
            'auth' => ['user' => $user],
        ]);
    }

    /**
     * Store donation and redirect to Stripe
     */
    public function store(DonationRequest $request)
    {
        try {
            // Check if donations are enabled
            $donationEnabled = Setting::get('donation_enable', '1');
            if ($donationEnabled === '0') {
                return back()->with('error', 'Donations are currently disabled.');
            }

            // Create donation record with pending status
            $donation = Donation::create([
                'name' => $request->name,
                'phone' => $request->phone,
                'value' => $request->value,
                'message' => $request->message,
                'status' => Donation::STATUS_PENDING,
                'payment_method' => 'stripe',
            ]);

            Log::info('Donation created', ['donation_id' => $donation->id]);

            // Create Stripe checkout session
            $checkoutUrl = $this->stripeService->createDonationCheckoutSession($donation);

            // Redirect to Stripe
            return Inertia::location($checkoutUrl);

        } catch (\Exception $e) {
            Log::error('Donation creation failed: ' . $e->getMessage());
            
            // Delete donation if Stripe session failed
            if (isset($donation)) {
                $donation->delete();
            }

            return back()->with('error', 'Failed to process donation. Please try again.');
        }
    }

    /**
     * Handle successful donation payment
     */
    public function success(Request $request)
    {
        $sessionId = $request->get('session_id');

        if (!$sessionId) {
            return redirect()->route('donation.form')
                ->with('error', 'Invalid payment session.');
        }

        // Find donation by payment_id
        $donation = Donation::where('payment_id', $sessionId)->first();

        if (!$donation) {
            return redirect()->route('donation.form')
                ->with('error', 'Donation not found.');
        }

        // Get settings and categories for the page
        $settings = Setting::public()->pluck('value', 'key')->toArray();
        if (isset($settings['logo'])) {
            $settings['logo_url'] = asset('storage/' . $settings['logo']);
        }

        $categories = Category::active()->ordered()->get();

        return Inertia::render('DonationSuccess', [
            'donation' => $donation,
            'settings' => $settings,
            'categories' => $categories,
            'auth' => ['user' => auth()->user()],
        ]);
    }

    /**
     * Handle cancelled donation payment
     */
    public function cancel()
    {
        // Get settings and categories
        $settings = Setting::public()->pluck('value', 'key')->toArray();
        if (isset($settings['logo'])) {
            $settings['logo_url'] = asset('storage/' . $settings['logo']);
        }

        $categories = Category::active()->ordered()->get();

        return Inertia::render('DonationCancel', [
            'settings' => $settings,
            'categories' => $categories,
            'auth' => ['user' => auth()->user()],
        ]);
    }
}
