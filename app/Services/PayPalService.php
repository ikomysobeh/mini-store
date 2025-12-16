<?php

namespace App\Services;

use App\Models\Order;
use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PayPalService
{
    protected string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.paypal.base_url', 'https://api-m.sandbox.paypal.com'), '/');
    }

    /**
     * Create a PayPal order for the given order.
     */
    public function createOrder(Order $order): array
    {
        $payload = [
            'intent' => 'CAPTURE',
            'purchase_units' => [
                [
                    'reference_id' => (string) $order->id,
                    'custom_id' => $order->is_donation ? 'donation' : 'purchase',
                    'amount' => [
                        'currency_code' => 'USD',
                        'value' => number_format((float) $order->total, 2, '.', ''),
                    ],
                ],
            ],
            'application_context' => [
                'brand_name' => config('app.name'),
                'shipping_preference' => 'NO_SHIPPING',
                'user_action' => 'PAY_NOW',
                'return_url' => route('payment.success'),
                'cancel_url' => route('payment.cancel'),
            ],
        ];

        $response = Http::withToken($this->getAccessToken())
            ->post($this->endpoint('/v2/checkout/orders'), $payload);

        if (!$response->successful()) {
            Log::error('PayPal create order failed', ['body' => $response->body()]);
            throw new Exception('Failed to create PayPal order.');
        }

        return $response->json();
    }

    /**
     * Capture a PayPal order.
     */
    public function captureOrder(string $paypalOrderId): array
    {
        $response = Http::withToken($this->getAccessToken())
            ->post($this->endpoint("/v2/checkout/orders/{$paypalOrderId}/capture"));

        if (!$response->successful()) {
            Log::error('PayPal capture order failed', ['body' => $response->body()]);
            throw new Exception('Failed to capture PayPal order.');
        }

        return $response->json();
    }

    protected function getAccessToken(): string
    {
        return Cache::remember('paypal_access_token', now()->addMinutes(50), function () {
            $clientId = config('services.paypal.client_id');
            $secret = config('services.paypal.secret');

            if (!$clientId || !$secret) {
                throw new Exception('PayPal credentials are not configured.');
            }

            $response = Http::asForm()
                ->withBasicAuth($clientId, $secret)
                ->post($this->endpoint('/v1/oauth2/token'), [
                    'grant_type' => 'client_credentials',
                ]);

            if (!$response->successful()) {
                Log::error('PayPal token request failed', ['body' => $response->body()]);
                throw new Exception('Unable to authenticate with PayPal.');
            }

            return Arr::get($response->json(), 'access_token');
        });
    }

    protected function endpoint(string $path): string
    {
        return $this->baseUrl . $path;
    }
}



