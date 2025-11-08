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
    protected function handleCheckoutSessionCompleted($session)
    {
        $sessionId  = $session->id ?? null;
        $metadata   = (array)($session->metadata ?? []);
        $orderId    = $metadata['order_id'] ?? null;

        // Prefer order_id from metadata if present
        $orderQuery = Order::query();
        if ($orderId) {
            $orderQuery->where('id', $orderId);
        } elseif ($sessionId) {
            $orderQuery->where('payment_id', $sessionId);
        }

        /** @var Order|null $order */
        $order = $orderQuery->first();

        if (!$order) {
            Log::warning("Stripe: checkout.session.completed but order not found", [
                'session_id' => $sessionId, 
                'order_id' => $orderId
            ]);
            return;
        }

        if ($order->paid_at) {
            Log::info("Stripe: checkout.session.completed for order {$order->id} already processed");
            return;
        }

        DB::transaction(function () use ($order, $session) {
            // ✅ Mark paid and set to SUCCESS status
            $order->update([
                'paid_at'        => now(),
                'status'         => Order::STATUS_SUCCESS,
                'payment_method' => 'stripe',
                'payment_id'     => $session->id ?? $order->payment_id,
                'payment_ref'    => $session->payment_intent ?? null,
                'currency'       => ($session->currency ?? 'usd'),
            ]);

            // Decrement stock for each order item (variant-aware)
            $order->loadMissing(['items.product', 'items.variant', 'items.variant.color', 'items.variant.size']);
            foreach ($order->items as $orderItem) {
                if ($orderItem->variant_id) {
                    $variant = ProductVariant::lockForUpdate()->find($orderItem->variant_id);
                    if ($variant && $variant->stock >= $orderItem->quantity) {
                        $variant->decrement('stock', $orderItem->quantity);
                        Log::info("Variant stock decremented: Variant {$variant->id}, -{$orderItem->quantity}, new stock: {$variant->stock}");
                    } else {
                        Log::warning("Insufficient variant stock: Variant {$orderItem->variant_id}");
                    }
                } elseif ($orderItem->product_id) {
                    $product = Product::lockForUpdate()->find($orderItem->product_id);
                    if ($product && $product->stock >= $orderItem->quantity) {
                        $product->decrement('stock', $orderItem->quantity);
                        Log::info("Product stock decremented: Product {$product->id}, -{$orderItem->quantity}");
                    }
                }
            }

            // ✅ Clear user cart
            if ($order->customer_id) {
                $cart = Cart::where('customer_id', $order->customer_id)->first();
                if ($cart) {
                    $cart->items()->delete();
                    Log::info("Cart cleared for customer {$order->customer_id} after order {$order->id}");
                }
            }

            // Admin notification
            try {
                $order->load(['customer.user', 'items']);
                app(AdminNotificationService::class)->createOrderNotification($order);
            } catch (\Throwable $e) {
                Log::error("Failed to create admin notification for order {$order->id}: " . $e->getMessage());
            }
        });

        Log::info("Stripe: checkout.session.completed - Order {$order->id} marked as success");
    }

    /**
     * Handle payment intent succeeded (backup/alternative event)
     */
    protected function handlePaymentIntentSucceeded($paymentIntent)
    {
        $paymentIntentId = $paymentIntent->id ?? null;
        
        if (!$paymentIntentId) {
            Log::warning('Stripe: payment_intent.succeeded but no ID found');
            return;
        }
        
        // Try to find by payment_intent_id first
        $order = Order::where('payment_intent_id', $paymentIntentId)->first();
        
        // ✅ If not found, try by metadata
        if (!$order && isset($paymentIntent->metadata->order_id)) {
            $order = Order::find($paymentIntent->metadata->order_id);
            
            if ($order) {
                $order->update(['payment_intent_id' => $paymentIntentId]);
            }
        }
        
        if (!$order) {
            Log::warning('Stripe: payment_intent.succeeded but order not found', [
                'payment_intent' => $paymentIntentId,
                'metadata' => $paymentIntent->metadata ?? null
            ]);
            return;
        }
        
        // Check if already processed
        if ($order->status === Order::STATUS_SUCCESS && $order->paid_at) {
            Log::info("Stripe: payment_intent.succeeded for order {$order->id} already processed");
            return;
        }
        
        // Update to SUCCESS status
        $order->update([
            'status' => Order::STATUS_SUCCESS,
            'paid_at' => now(),
            'payment_method' => 'stripe',
            'payment_intent_id' => $paymentIntentId,
        ]);
        
        // ✅ Clear cart here too (in case checkout.session.completed didn't fire)
        if ($order->customer_id) {
            $cart = Cart::where('customer_id', $order->customer_id)->first();
            if ($cart) {
                $cart->items()->delete();
                Log::info("Cart cleared for customer {$order->customer_id} after payment_intent.succeeded for order {$order->id}");
            }
        }
        
        Log::info("Stripe: payment_intent.succeeded - Order {$order->id} marked as success");
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
