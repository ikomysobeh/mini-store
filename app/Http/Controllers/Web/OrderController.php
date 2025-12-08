<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ProductVariant;
use App\Services\CartService;
use App\Services\StripeService;
use App\Services\PayPalService;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class OrderController extends Controller
{
    protected $cartService;
    protected $stripeService;
    protected $payPalService;

    public function __construct(CartService $cartService, StripeService $stripeService, PayPalService $payPalService)
    {
        $this->cartService = $cartService;
        $this->stripeService = $stripeService;
        $this->payPalService = $payPalService;
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
    $paypalToken = $request->get('token');

    if ($paypalToken) {
        $paypalOrder = $this->finalizePayPalOrder($paypalToken);

        if (!$paypalOrder) {
            return redirect()->route('payment.cancel')->with('error', 'Unable to confirm PayPal payment.');
        }

        return $this->renderSuccessPage($paypalOrder);
    }

    if (!$sessionId) {
        return redirect('/')->with('error', 'Invalid payment');
    }

    $order = Order::where('payment_id', $sessionId)->first();

    if (!$order) {
        return redirect('/')->with('error', 'Order not found');
    }

    $order->load([
        'items.product',
        'items.variant.color',
        'items.variant.size',
        'customer.user'
    ]);

    return $this->renderSuccessPage($order);
}

public function paymentCancel()
{
    return Inertia::render('Web/PaymentCancel', [
        // ✅ FIXED: Pass required navbar data
        'categories' => \App\Models\Category::withCount('products')->get(),
        'cartItems' => auth()->user() ? auth()->user()->cart?->items()->with('product')->get() ?? [] : [],
        'auth' => [
            'user' => auth()->user()
        ],
        'settings' => [
            'site_name' => config('app.name', 'Elegant Store'),
            'logo_url' => null,
        ],
    ]);
}

protected function finalizePayPalOrder(string $paypalToken): ?Order
{
    $payment = Payment::where('gateway_payment_id', $paypalToken)->first();

    if (!$payment) {
        return null;
    }

    try {
        $capture = $this->payPalService->captureOrder($paypalToken);
    } catch (\Exception $e) {
        Log::error('PayPal capture failed: ' . $e->getMessage());
        return null;
    }

    $status = strtoupper(data_get($capture, 'status', ''));

    if ($status !== 'COMPLETED') {
        $payment->markAsFailed();
        return null;
    }

    $payment->markAsCompleted();

    $order = $payment->order;

    if (!$order) {
        return null;
    }

    $order->markAsPaid();
    $order->update(['status' => Order::STATUS_SUCCESS]);

    $order->load([
        'items.product',
        'items.variant.color',
        'items.variant.size',
        'customer.user'
    ]);

    return $order;
}

protected function renderSuccessPage(Order $order)
{
    return Inertia::render('Web/OrderSuccess', [
        'order' => $order,
        'categories' => \App\Models\Category::withCount('products')->get(),
        'cartItems' => auth()->user() ? auth()->user()->cart?->items()->with('product')->get() ?? [] : [],
        'auth' => [
            'user' => auth()->user()
        ],
        'settings' => [
            'site_name' => config('app.name', 'Elegant Store'),
            'logo_url' => null,
        ],
    ]);
}

}
