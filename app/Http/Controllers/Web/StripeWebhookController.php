<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
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
                    // Optional: can be used for extra reconciliation if you wish
                    $this->handlePaymentIntentSucceeded($event->data->object);
                    break;

                case 'payment_intent.payment_failed':
                    $this->handlePaymentFailed($event->data->object);
                    break;

                default:
                    // Ignore other event types or log them
                    Log::info('Stripe webhook: unhandled event ' . $event->type);
                    break;
            }

            $record->update(['processed_at' => now()]);
        } catch (\Throwable $e) {
            Log::error('Stripe webhook handling error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            // Do not mark processed; let Stripe retry
            return response('Webhook handler error', 500);
        }

        return response('OK', 200);
    }

    /**
     * The “source of truth” that the order is paid.
     * We:
     *  - Find the order using session.id or metadata.order_id
     *  - Mark Paid/Processing
     *  - Decrement stock (variants or normal)
     *  - Clear the user’s cart
     *  - Notify admins
     */
    protected function handleCheckoutSessionCompleted($session)
    {
        // $session is a \Stripe\Checkout\Session object (stdClass here)
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
            Log::warning("Stripe: checkout.session.completed but order not found", ['session_id' => $sessionId, 'order_id' => $orderId]);
            return;
        }

        if ($order->paid_at) {
            // Already handled (idempotency guard on our side)
            return;
        }

        DB::transaction(function () use ($order, $session) {
            // Mark paid/processing + store Stripe refs
            $order->update([
                'paid_at'        => now(),
                'status'         => Order::STATUS_PROCESSING,
                'payment_method' => 'stripe',
                'payment_id'     => $session->id ?? $order->payment_id, // keep session id
                'payment_ref'    => $session->payment_intent ?? null,   // optional extra column if you have it
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

            // Clear user cart (if user is available via the order relation)
            $user = optional($order->customer)->user;
            if ($user) {
                app(CartService::class)->getCart($user)?->items()?->delete();
            }

            // Admin notification
            try {
                $order->load(['customer.user', 'items']);
                app(AdminNotificationService::class)->createOrderNotification($order);
            } catch (\Throwable $e) {
                Log::error("Failed to create admin notification for order {$order->id}: " . $e->getMessage());
            }
        });
    }

    protected function handlePaymentIntentSucceeded($paymentIntent)
    {
        // Optional: Map back to an order if you kept payment_intent id somewhere.
        // Good for reconciliation or if you don’t use checkout.session.completed.
        Log::info('Stripe: payment_intent.succeeded ' . ($paymentIntent->id ?? ''));
    }

    protected function handlePaymentFailed($paymentIntent)
    {
        // Optional: mark the order failed if you stored a correlation
        Log::warning('Stripe: payment_intent.payment_failed ' . ($paymentIntent->id ?? ''));
    }

}
