<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use Stripe\StripeClient;
use Exception;

class PaymentService
{
    protected StripeClient $stripe;

    public function __construct()
    {
        $this->stripe = new StripeClient(config('services.stripe.secret'));
    }

    public function createPaymentIntent(Order $order, array $options = []): \Stripe\PaymentIntent
    {
        $amount = (int) ($order->total * 100); // Convert to cents

        $paymentIntentData = [
            'amount' => $amount,
            'currency' => 'usd',
            'metadata' => [
                'order_id' => $order->id,
                'customer_id' => $order->customer_id,
                'is_donation' => $order->is_donation ? 'true' : 'false',
            ],
        ];

        if (isset($options['customer_email'])) {
            $paymentIntentData['receipt_email'] = $options['customer_email'];
        }

        return $this->stripe->paymentIntents->create($paymentIntentData);
    }

    public function processPayment(Order $order, string $paymentMethodId): Payment
    {
        $amount = (int) ($order->total * 100); // Convert to cents

        $paymentIntent = $this->stripe->paymentIntents->create([
            'amount' => $amount,
            'currency' => 'usd',
            'payment_method' => $paymentMethodId,
            'confirmation_method' => 'manual',
            'confirm' => true,
            'return_url' => route('orders.show', $order),
            'metadata' => [
                'order_id' => $order->id,
                'customer_id' => $order->customer_id,
                'is_donation' => $order->is_donation ? 'true' : 'false',
            ],
        ]);

        $payment = Payment::create([
            'order_id' => $order->id,
            'gateway' => 'stripe',
            'gateway_payment_id' => $paymentIntent->id,
            'amount' => $order->total,
            'currency' => 'USD',
            'status' => $this->mapStripeStatus($paymentIntent->status),
        ]);

        if ($paymentIntent->status === 'succeeded') {
            $payment->markAsCompleted();
        } elseif ($paymentIntent->status === 'requires_action') {
            // Payment requires further action (e.g. 3D Secure)
            throw new Exception('Payment requires additional authentication.', 402);
        } else {
            $payment->markAsFailed();
            throw new Exception('Payment failed.');
        }

        return $payment;
    }

    public function refundPayment(Order $order)
    {
        $payment = $order->payments()->where('status', Payment::STATUS_COMPLETED)->first();

        if (!$payment) {
            throw new Exception('No completed payment found for this order.');
        }

        $refund = $this->stripe->refunds->create([
            'payment_intent' => $payment->gateway_payment_id,
            'amount' => (int) ($payment->amount * 100),
        ]);

        $payment->update(['status' => Payment::STATUS_REFUNDED]);

        return $refund;
    }

    public function handleWebhook(string $payload, string $signature)
    {
        $endpointSecret = config('services.stripe.webhook_secret');

        try {
            $event = \Stripe\Webhook::constructEvent($payload, $signature, $endpointSecret);
        } catch (\UnexpectedValueException $e) {
            throw new Exception('Invalid payload.');
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            throw new Exception('Invalid signature.');
        }

        switch ($event->type) {
            case 'payment_intent.succeeded':
                $this->handlePaymentSucceeded($event->data->object);
                break;
            case 'payment_intent.payment_failed':
                $this->handlePaymentFailed($event->data->object);
                break;
            default:
                // Unhandled event type
                break;
        }
    }

    protected function handlePaymentSucceeded($paymentIntent)
    {
        $payment = Payment::where('gateway_payment_id', $paymentIntent->id)->first();

        if ($payment) {
            $payment->markAsCompleted();
            $payment->order->markAsPaid();
        }
    }

    protected function handlePaymentFailed($paymentIntent)
    {
        $payment = Payment::where('gateway_payment_id', $paymentIntent->id)->first();

        if ($payment) {
            $payment->markAsFailed();
        }
    }

    protected function mapStripeStatus(string $stripeStatus): string
    {
        return match ($stripeStatus) {
            'succeeded' => Payment::STATUS_COMPLETED,
            'requires_payment_method', 'requires_confirmation', 'requires_action', 'processing' => Payment::STATUS_PENDING,
            'canceled' => Payment::STATUS_FAILED,
            default => Payment::STATUS_PENDING,
        };
    }

    public function createCheckoutSession(Order $order)
    {
        \Stripe\Stripe::setApiKey(config('services.stripe.secret'));

        $lineItems = [];

        foreach ($order->items as $item) {
            $lineItems[] = [
                'price_data' => [
                    'currency' => 'eur',
                    'product_data' => [
                        'name' => $item->product_name,
                    ],
                    'unit_amount' => $item->price * 100, // Convert to cents
                ],
                'quantity' => $item->quantity,
            ];
        }

        return \Stripe\Checkout\Session::create([
            'payment_method_types' => ['card'],
            'line_items' => $lineItems,
            'mode' => 'payment',
            'success_url' => route('orders.success') . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('orders.cancel'),
            'metadata' => [
                'order_id' => $order->id,
                'is_donation' => $order->is_donation ? 'true' : 'false',
            ],
        ]);
    }

    public function getCheckoutSession($sessionId)
    {
        \Stripe\Stripe::setApiKey(config('services.stripe.secret'));
        return \Stripe\Checkout\Session::retrieve($sessionId);
    }

}
