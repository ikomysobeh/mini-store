<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StripeWebhookEvent;
use App\Services\AdminNotificationService;
use App\Services\CartService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StripeWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $signature = $request->header('Stripe-Signature');
        $payload   = $request->getContent();

        $endpointSecret = config('services.stripe.webhook_secret') ?? env('STRIPE_WEBHOOK_SECRET');

        try {
            // Verify signature
            $event = \Stripe\Webhook::constructEvent(
                $payload,
                $signature,
                $endpointSecret
            );
        } catch (\UnexpectedValueException $e) {
            Log::warning('Stripe webhook: invalid payload - ' . $e->getMessage());
            return response('Invalid payload', 400);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            Log::warning('Stripe webhook: invalid signature - ' . $e->getMessage());
            return response('Invalid signature', 400);
        }

        // Idempotency: refuse re-processing an event id
        $already = StripeWebhookEvent::where('event_id', $event->id)->first();
        if ($already && $already->processed_at) {
            return response('Event already processed', 200);
        }

        // Store event row (or create it if not exists)
        $record = $already ?? StripeWebhookEvent::create([
            'event_id' => $event->id,
            'type'     => $event->type,
            'payload'  => json_encode($event->jsonSerialize()),
        ]);

        try {
            switch ($event->type) {
                case 'checkout.session.completed':
                    $this->handleCheckoutSessionCompleted($event->data->object);
                    break;

                case 'payment_intent.succeeded':
                    $this->handlePaymentIntentSucceeded($event->data->object);
                    break;

                case 'payment_intent.payment_failed':
                    $this->handlePaymentFailed($event->data->object);
                    break;

                default:
                    Log::info('Stripe webhook: unhandled event ' . $event->type);
                    break;
            }

            $record->update(['processed_at' => now()]);
        } catch (\Throwable $e) {
            Log::error('Stripe webhook handling error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response('Webhook handler error', 500);
        }

        return response('OK', 200);
    }

    /**
     * The "source of truth" that the order is paid.
     */
    /**
 * Handle checkout session completed event
 */
protected function handleCheckoutSessionCompleted($session)
{
    $sessionId = $session->id;
    
    // ✅ FIXED: Check metadata first to determine type
    $metadata = $session->metadata;
    
    // Log metadata for debugging
    Log::info('Stripe: checkout.session.completed received', [
        'session_id' => $sessionId,
        'metadata' => $metadata ? $metadata->toArray() : []
    ]);
    
    // ✅ NEW: Check if this is a donation
    if (isset($metadata->type) && $metadata->type === 'donation') {
        $this->handleDonationCheckout($session);
        return;
    }
    
    // ✅ NEW: Check if donation_id exists in metadata (alternative check)
    if (isset($metadata->donation_id)) {
        $this->handleDonationCheckout($session);
        return;
    }
    
    // Otherwise, handle as order
    $this->handleOrderCheckout($session);
}

/**
 * Handle donation checkout completion
 */
protected function handleDonationCheckout($session)
{
    $sessionId = $session->id;
    $metadata = $session->metadata;
    
    // Get donation ID from metadata
    $donationId = $metadata->donation_id ?? null;
    
    if (!$donationId) {
        Log::warning('Stripe: checkout.session.completed for donation but no donation_id in metadata', [
            'session_id' => $sessionId,
            'metadata' => $metadata ? $metadata->toArray() : []
        ]);
        return;
    }
    
    // Find donation
    $donation = \App\Models\Donation::find($donationId);
    
    if (!$donation) {
        Log::warning('Stripe: Donation not found', [
            'session_id' => $sessionId,
            'donation_id' => $donationId
        ]);
        return;
    }
    
    // Check if already processed
    if ($donation->status === \App\Models\Donation::STATUS_COMPLETED) {
        Log::info('Stripe: checkout.session.completed for donation already processed', [
            'donation_id' => $donationId
        ]);
        return;
    }
    
    // Update donation
    $donation->update([
        'status' => \App\Models\Donation::STATUS_COMPLETED,
        'payment_id' => $sessionId,
        'payment_intent_id' => $session->payment_intent ?? null,
        'paid_at' => now(),
    ]);
    
    Log::info('Stripe: Donation completed successfully', [
        'donation_id' => $donationId,
        'session_id' => $sessionId,
        'amount' => $donation->value
    ]);
    
    // Create admin notification
    
    
    Log::info('✅ Admin notification created for donation', [
        'donation_id' => $donation->id
    ]);
}

/**
 * Handle order checkout completion (existing logic)
 */
protected function handleOrderCheckout($session)
{
    $sessionId = $session->id;
    $metadata = $session->metadata;
    
    // Get order ID from metadata
    $orderId = $metadata->order_id ?? null;
    
    if (!$orderId) {
        Log::warning('Stripe: checkout.session.completed but no order_id in metadata', [
            'session_id' => $sessionId,
            'metadata' => $metadata ? $metadata->toArray() : []
        ]);
        return;
    }
    
    $order = \App\Models\Order::find($orderId);
    
    if (!$order) {
        Log::warning('Stripe: checkout.session.completed but order not found', [
            'session_id' => $sessionId,
            'order_id' => $orderId
        ]);
        return;
    }
    
    // Check if already processed
    if ($order->status === 'success' || $order->status === 'done') {
        Log::info('Stripe: checkout.session.completed for order already processed', [
            'order_id' => $orderId
        ]);
        return;
    }
    
    // Update order status
    $order->update([
        'status' => 'success',
        'paid_at' => now(),
        'payment_id' => $sessionId,
    ]);
    
    // Clear cart
    if ($order->customer_id) {
        \App\Models\Cart::where('customer_id', $order->customer_id)->delete();
        Log::info('Cart cleared for customer after order', [
            'customer_id' => $order->customer_id,
            'order_id' => $orderId
        ]);
    }
    
    // Decrement stock for each variant
    foreach ($order->variants as $variant) {
        if ($variant->pivot->quantity > 0) {
            $variant->decrement('quantity', $variant->pivot->quantity);
            Log::info('Variant stock decremented', [
                'variant_id' => $variant->id,
                'quantity' => $variant->pivot->quantity,
                'new_stock' => $variant->fresh()->quantity
            ]);
        }
    }
    
    Log::info('Stripe: checkout.session.completed - Order marked as success', [
        'order_id' => $orderId,
        'session_id' => $sessionId
    ]);
    
    
}


/**
 * ✅ NEW METHOD - Handle donation payment webhook
 */
private function handleDonationPayment($session, $metadata)
{
    $donationId = $metadata['donation_id'] ?? null;
    
    if (!$donationId) {
        Log::warning('Stripe: Donation ID missing in webhook metadata', [
            'session_id' => $session->id ?? null
        ]);
        return;
    }
    
    $donation = \App\Models\Donation::find($donationId);
    
    if (!$donation) {
        Log::warning('Stripe: Donation not found', [
            'donation_id' => $donationId,
            'session_id' => $session->id ?? null
        ]);
        return;
    }
    
    // Check if already processed (prevent duplicate webhook processing)
    if ($donation->status === \App\Models\Donation::STATUS_COMPLETED && $donation->paid_at) {
        Log::info("Stripe: Donation {$donation->id} already processed");
        return;
    }
    
    // Update donation as completed
    DB::transaction(function() use ($donation, $session) {
        $donation->update([
            'status' => \App\Models\Donation::STATUS_COMPLETED,
            'paid_at' => now(),
            'payment_id' => $session->id,
            'payment_intent_id' => $session->payment_intent ?? null,
        ]);
        
        // Send admin notification
        try {
            app(\App\Services\AdminNotificationService::class)->createDonationNotification($donation);
        } catch (\Throwable $e) {
            Log::error("Failed to create admin notification for donation {$donation->id}: " . $e->getMessage());
        }
    });
    
    Log::info("Stripe: Donation {$donation->id} completed successfully", [
        'amount' => $donation->value,
        'donor' => $donation->name,
        'payment_id' => $session->id
    ]);
}


    /**
     * Handle payment intent succeeded (backup/alternative event)
     */
    protected function handlePaymentIntentSucceeded($paymentIntent)
{
    $paymentIntentId = $paymentIntent->id;
    $metadata = $paymentIntent->metadata;
    
    // Log for debugging
    Log::info('Stripe: payment_intent.succeeded received', [
        'payment_intent_id' => $paymentIntentId,
        'metadata' => $metadata ? $metadata->toArray() : []
    ]);
    
    // ✅ NEW: Check if this is a donation
    if (isset($metadata->type) && $metadata->type === 'donation') {
        $this->handleDonationPaymentIntent($paymentIntent);
        return;
    }
    
    // ✅ NEW: Alternative check for donation_id
    if (isset($metadata->donation_id)) {
        $this->handleDonationPaymentIntent($paymentIntent);
        return;
    }
    
    // Otherwise, handle as order
    $this->handleOrderPaymentIntent($paymentIntent);
}

/**
 * Handle donation payment intent
 */
protected function handleDonationPaymentIntent($paymentIntent)
{
    $metadata = $paymentIntent->metadata;
    $donationId = $metadata->donation_id ?? null;
    
    if (!$donationId) {
        Log::warning('Stripe: payment_intent.succeeded for donation but no donation_id', [
            'payment_intent_id' => $paymentIntent->id
        ]);
        return;
    }
    
    $donation = \App\Models\Donation::find($donationId);
    
    if (!$donation) {
        Log::warning('Stripe: Donation not found for payment_intent', [
            'donation_id' => $donationId
        ]);
        return;
    }
    
    // Check if already processed
    if ($donation->status === \App\Models\Donation::STATUS_COMPLETED) {
        Log::info('Stripe: payment_intent.succeeded for donation already processed', [
            'donation_id' => $donationId
        ]);
        return;
    }
    
    // Update donation
    $donation->update([
        'status' => \App\Models\Donation::STATUS_COMPLETED,
        'payment_intent_id' => $paymentIntent->id,
        'paid_at' => now(),
    ]);
    
    Log::info('Stripe: payment_intent.succeeded - Donation marked as completed', [
        'donation_id' => $donationId
    ]);
}

/**
 * Handle order payment intent (existing logic)
 */
protected function handleOrderPaymentIntent($paymentIntent)
{
    $metadata = $paymentIntent->metadata;
    $orderId = $metadata->order_id ?? null;
    
    if (!$orderId) {
        Log::warning('Stripe: payment_intent.succeeded but order not found', [
            'payment_intent' => $paymentIntent->id,
            'metadata' => $metadata ? $metadata->toArray() : []
        ]);
        return;
    }
    
    $order = \App\Models\Order::find($orderId);
    
    if (!$order) {
        Log::warning('Stripe: Order not found for payment_intent', [
            'order_id' => $orderId
        ]);
        return;
    }
    
    // Check if already processed
    if ($order->status === 'success' || $order->status === 'done') {
        Log::info('Stripe: payment_intent.succeeded for order already processed', [
            'order_id' => $orderId
        ]);
        return;
    }
    
    // Update order
    $order->update([
        'status' => 'success',
        'paid_at' => now(),
    ]);
    
    // Clear cart
    if ($order->customer_id) {
        \App\Models\Cart::where('customer_id', $order->customer_id)->delete();
        Log::info('Cart cleared after payment_intent.succeeded', [
            'customer_id' => $order->customer_id,
            'order_id' => $orderId
        ]);
    }
    
    Log::info('Stripe: payment_intent.succeeded - Order marked as success', [
        'order_id' => $orderId
    ]);
}

    /**
     * Handle payment failure
     */
    protected function handlePaymentFailed($paymentIntent)
    {
        $paymentIntentId = $paymentIntent->id ?? null;
        
        if (!$paymentIntentId) {
            Log::warning('Stripe: payment_intent.payment_failed but no ID found');
            return;
        }
        
        // Find the order by payment_id
        $order = Order::where('payment_id', $paymentIntentId)->first();
        
        if (!$order) {
            Log::warning("Stripe: payment_intent.payment_failed but order not found for payment_intent: {$paymentIntentId}");
            return;
        }
        
        // Extract error details
        $errorMessage = null;
        
        if (isset($paymentIntent->last_payment_error)) {
            $errorMessage = $paymentIntent->last_payment_error->message ?? 'Payment failed';
        }
        
        // ✅ Update to FAILED status
        $order->update([
            'status' => Order::STATUS_FAILED,
            'payment_error_message' => $errorMessage,
            'payment_failed_at' => now(),
        ]);
        
        // DO NOT decrement stock - payment never succeeded
        // DO NOT clear cart - customer needs to retry
        
        Log::warning("Stripe: payment_intent.payment_failed - Order {$order->id} marked as failed. Reason: {$errorMessage}");
    }
}
