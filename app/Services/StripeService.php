<?php

namespace App\Services;

use App\Models\Order;

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
        'success_url' => route('payment.success'), // no session_id logic needed in URL now
        'cancel_url' => route('payment.cancel'),
        'metadata' => [
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

    $order->update([
        'payment_id' => $session->id,
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
}
