<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ProductVariant;
use App\Services\CartService;
use App\Services\StripeService;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class OrderController extends Controller
{
    protected $cartService;
    protected $stripeService;

    public function __construct(CartService $cartService, StripeService $stripeService)
    {
        $this->cartService = $cartService;
        $this->stripeService = $stripeService;
    }

    public function store(Request $request)
    {
        $request->validate([
            'purchase_type' => 'required|in:purchase,donation',
            'customer_email' => 'required|email',
        ]);

        $user = auth()->user();
        $cart = $this->cartService->getCart($user);

        if (!$cart || $cart->items->isEmpty()) {
            return back()->with('error', 'Your cart is empty');
        }

        // Create order
        $order = Order::create([
            'customer_id' => $user->customer->id,
            'total' => $cart->items->sum(fn($item) => $item->quantity * $item->price),
            'shipping' => 0,
            'status' => Order::STATUS_PENDING,
            'is_donation' => $request->purchase_type === 'donation',
            'payment_method' => 'stripe',
        ]);

        // Create order items
        foreach ($cart->items as $cartItem) {
            $order->items()->create([
                'product_id' => $cartItem->product_id,
                'product_name' => $cartItem->product_name,
                'quantity' => $cartItem->quantity,
                'price' => $cartItem->price,
            ]);
        }

        // Create Stripe payment URL
        $stripeUrl = $this->stripeService->createPaymentUrl($order);

        // Redirect user to Stripe payment page
        return Inertia::location($stripeUrl);
    }

    // Handle successful payment
    // Handle successful payment - UPDATED with stock decrementing
    public function paymentSuccess(Request $request)
    {
        $sessionId = $request->get('session_id');

        if (!$sessionId) {
            return redirect('/')->with('error', 'Invalid payment');
        }

        $order = Order::where('payment_id', $sessionId)->first();

        if (!$order) {
            return redirect('/')->with('error', 'Order not found');
        }

        return Inertia::render('Web/OrderSuccess', [
                'order' => $order->load(['items.product', 'items.variant.color', 'items.variant.size'])
            ]);
    }

     public function paymentCancel()
    {
        return Inertia::render('Web/PaymentCancel');
    }
}
