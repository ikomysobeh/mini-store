<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\AdminNotificationService;
use App\Services\CartService;
use App\Services\StripeService;
use App\Models\Order;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class CheckoutController extends Controller
{
    protected $cartService;
    protected $stripeService;
    protected $notificationService;

    public function __construct(CartService $cartService , AdminNotificationService $notificationService)
    {
        $this->cartService = $cartService;
        $this->stripeService = new StripeService();
        $this->notificationService = $notificationService;

    }
    public function index()
    {
        $user = auth()->user();
        $cart = $this->cartService->getCart($user);

        // Redirect to cart if empty
        if (!$cart || $cart->items->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Your cart is empty');
        }

        // Format cart items for frontend
        $cartItems = $cart->items()->with(['product.category'])->get()->map(function ($item) {
            return [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'product_name' => $item->product_name,
                'quantity' => $item->quantity,
                'price' => $item->price,
                'product' => [
                    'id' => $item->product->id,
                    'name' => $item->product->name,
                    'price' => $item->product->price,
                    'image' => $item->product->image_url ?? null,
                ]
            ];
        });

        $settings = Setting::public()->get()->pluck('value', 'key');
        if (isset($settings['logo']) && $settings['logo']) {
            $settings['logo_url'] = asset('storage/' . $settings['logo']);
        }

        return Inertia::render('Web/Checkout', [
            'settings' => $settings,
            'cartItems' => $cartItems,
            'customer' => $user->customer,
            'user' => $user,
        ]);
    }

    // UPDATED: Store method with notification
    public function store(Request $request)
    {
        $validated = $request->validate([
            'is_donation' => 'required|boolean',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'notes' => 'nullable|string|max:1000',
        ]);

        $user = auth()->user();
        $cart = $this->cartService->getCart($user);

        if (!$cart || $cart->items->isEmpty()) {
            return response()->json(['error' => 'Cart is empty'], 400);
        }

        // Calculate total server-side considering variants
        $serverTotal = 0;
        foreach ($cart->items as $item) {
            if ($item->variant) {
                // Use variant final price
                $itemPrice = $item->variant->final_price;
            } else {
                // Use product base price
                $itemPrice = $item->product->price;
            }
            $serverTotal += ($itemPrice * $item->quantity);
        }

        // Update/create customer
        $customer = $user->customer ?? new Customer();
        $customer->fill([
            'user_id' => $user->id,
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'phone' => $validated['phone'],
            'address' => 'test',
        ]);
        $customer->save();

        // Create order
        $order = Order::create([
            'customer_id' => $customer->id,
            'total' => $serverTotal,
            'shipping' => 0,
            'status' => Order::STATUS_PENDING,
            'is_donation' => $validated['is_donation'],
            'payment_method' => 'stripe',
            'notes' => $validated['notes'],
        ]);

        // Create order items with variant support
        foreach ($cart->items as $cartItem) {
            $orderItemData = [
                'product_id' => $cartItem->product_id,
                'product_name' => $cartItem->product->name,
                'quantity' => $cartItem->quantity,
                'price' => $cartItem->variant ? $cartItem->variant->final_price : $cartItem->product->price,
            ];

            // Add variant info if exists
            if ($cartItem->variant) {
                $orderItemData['variant_id'] = $cartItem->variant_id;
                $orderItemData['selected_color'] = $cartItem->variant->color->name;
                $orderItemData['selected_size'] = $cartItem->variant->size->name;
                $orderItemData['selected_color_hex'] = $cartItem->variant->color->hex_code;
            }

            $order->items()->create($orderItemData);
        }

        // ADD: Create admin notification AFTER order is completely saved
        try {
            // Load relationships needed for notification
            $order->load(['customer.user', 'items']);

            // Create the notification
            $this->notificationService->createOrderNotification($order);

            Log::info('✅ Admin notification created for order: ' . $order->id);
        } catch (\Exception $e) {
            // Log the error but don't fail the order
            Log::error('❌ Failed to create admin notification for order ' . $order->id . ': ' . $e->getMessage());
        }

        try {
            $checkoutUrl = $this->stripeService->createCheckoutSession($order);

            return response()->json([
                'success' => true,
                'url' => $checkoutUrl,
                'order_id' => $order->id
            ]);

        } catch (\Exception $e) {
            $order->delete();
            Log::error('Stripe session creation failed: ' . $e->getMessage());

            return response()->json([
                'error' => 'Payment processing failed. Please try again.'
            ], 500);
        }
    }


}
