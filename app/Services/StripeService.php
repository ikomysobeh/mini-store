<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Log;
use App\Models\Donation;


class StripeService
{
    private $stripe;

    public function __construct()
    {
        $this->stripe = new \Stripe\StripeClient(config('services.stripe.secret'));
    }

    public function createCheckoutSession(Order $order)
{
    $lineItems = $this->buildSecureLineItems($order);

    $session = $this->stripe->checkout->sessions->create([
        'payment_method_types' => ['card'],
        'line_items' => $lineItems,
        'mode' => 'payment',
        'success_url' => route('payment.success') . '?session_id={CHECKOUT_SESSION_ID}',
        'cancel_url' => route('payment.cancel'),
        'metadata' => [
            'type' => 'order',  // ← ADD THIS LINE
            'order_id'    => $order->id,
            'customer_id' => $order->customer_id,
            'is_donation' => $order->is_donation ? 'true' : 'false',
        ],
        'customer_email' => $order->customer->user->email,
        'client_reference_id' => (string) $order->id,
        'payment_intent_data' => [
            'metadata' => [
                'order_id'  => $order->id,
                'timestamp' => now()->timestamp,
            ]
        ],
        'expires_at' => now()->addHour()->timestamp,
    ]);

$paymentIntentId = $session->payment_intent; // ← Get it here

$order->update([
    'payment_id' => $session->id,
    'payment_intent_id' => $paymentIntentId, // ← ADD THIS
    'stripe_session_expires_at' => now()->addHour(),
]);

    return $session->url;
}


    private function buildSecureLineItems(Order $order)
    {
        // Always build line items from database data, never frontend
        $lineItems = [];

        if ($order->is_donation) {
            $lineItems[] = [
                'price_data' => [
                    'currency' => 'usd', // CHANGED: from 'eur' to 'usd'
                    'product_data' => [
                        'name' => 'Donation - ' . config('app.name'),
                        'description' => 'Thank you for supporting our cause!',
                    ],
                    'unit_amount' => $this->convertToStripeAmount($order->total),
                ],
                'quantity' => 1,
            ];
        } else {
            // Build from order items (database data)
            foreach ($order->items as $item) {
                $lineItems[] = [
                    'price_data' => [
                        'currency' => 'usd', // CHANGED: from 'eur' to 'usd'
                        'product_data' => [
                            'name' => $item->product_name,
                            'description' => 'Product ID: ' . $item->product_id,
                        ],
                        'unit_amount' => $this->convertToStripeAmount($item->price),
                    ],
                    'quantity' => $item->quantity,
                ];
            }
        }

        return $lineItems;
    }

    private function convertToStripeAmount($amount)
    {
        // Convert to smallest currency unit (cents for USD) - UPDATED COMMENT
        return intval($amount * 100);
    }

    public function verifyPayment($sessionId, $orderId = null)
    {
        try {
            $session = $this->stripe->checkout->sessions->retrieve($sessionId);

            // Additional verification if order ID provided
            if ($orderId && isset($session->metadata['order_id'])) {
                if ($session->metadata['order_id'] != $orderId) {
                    return false;
                }
            }

            return $session->payment_status === 'paid';
        } catch (\Exception $e) {
            Log::error('Stripe verification error: ' . $e->getMessage());
            return false;
        }
    }

    public function getSessionDetails($sessionId)
    {
        try {
            return $this->stripe->checkout->sessions->retrieve($sessionId);
        } catch (\Exception $e) {
            Log::error('Stripe session retrieval error: ' . $e->getMessage());
            return null;
        }
    }

     // ✅ ADD THIS: Alias method for backward compatibility
    public function createPaymentUrl(Order $order)
    {
        return $this->createCheckoutSession($order);
    }

    /**
 * Create Stripe checkout session for donation
 */
public function createDonationCheckoutSession(Donation $donation)
{
    \Stripe\Stripe::setApiKey(config('services.stripe.secret'));

    try {
        $session = \Stripe\Checkout\Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'usd',
                    'product_data' => [
                        'name' => 'Donation - ' . config('app.name'),
                        'description' => 'Thank you for your generous donation!',
                    ],
                    'unit_amount' => $this->convertToStripeAmount($donation->value),
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => route('donation.success') . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('donation.cancel'),
            
            // ✅ CRITICAL: Metadata to identify this as a donation
            'metadata' => [
                'type' => 'donation',  // ← Identifies this as donation
                'donation_id' => $donation->id,
                'donor_name' => $donation->name,
                'donor_phone' => $donation->phone,
            ],
            
            'customer_email' => null, // Optional: add if you collect email
            'expires_at' => now()->addHour()->timestamp,
        ]);

        // Store payment ID for tracking
        $donation->update([
            'payment_id' => $session->id,
        ]);

        Log::info('Stripe donation session created', [
            'donation_id' => $donation->id,
            'session_id' => $session->id,
        ]);

        return $session->url;

    } catch (\Exception $e) {
        Log::error('Stripe donation session failed: ' . $e->getMessage());
        throw $e;
    }
}

}
