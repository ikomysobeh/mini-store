<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\DonationBeneficiary;
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

        // ENHANCED: Load existing customer data for pre-population
        $existingCustomer = $user->customer;
        $customerData = null;

        if ($existingCustomer) {
            $customerData = [
                'first_name' => $existingCustomer->first_name,
                'last_name' => $existingCustomer->last_name,
                'phone' => $existingCustomer->phone,
                'address' => $existingCustomer->address,
                'has_existing_data' => true,
            ];
        }

        $settings = Setting::public()->get()->pluck('value', 'key');
        if (isset($settings['logo']) && $settings['logo']) {
            $settings['logo_url'] = asset('storage/' . $settings['logo']);
        }

        return Inertia::render('Web/Checkout', [
            'settings' => $settings,
            'cartItems' => $cartItems,
            'customer' => $user->customer,
            'user' => $user,
            'existingCustomerData' => $customerData, // NEW: Pass existing customer data
        ]);
    }
    // UPDATED: Store method with notification
// In App/Http/Controllers/Web/CheckoutController.php
// REPLACE the existing store method

    public function store(Request $request)
    {
        // SIMPLIFIED: Updated validation rules (removed unwanted fields)
        $validated = $request->validate([
            'is_donation' => 'required|boolean',
            // Customer/Donor information (always required)
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'address' => 'nullable|string|max:500', // Make address optional for donations
            'notes' => 'nullable|string|max:1000',

            // SIMPLIFIED: Beneficiary information (removed unwanted fields)
            'has_beneficiary' => 'nullable|boolean',
            'beneficiary_first_name' => 'nullable|string|max:255',
            'beneficiary_last_name' => 'nullable|string|max:255',
            'beneficiary_phone' => 'nullable|string|max:20',
            // REMOVED: 'beneficiary_email' validation
            'beneficiary_organization_name' => 'nullable|string|max:255',
            // REMOVED: 'beneficiary_relationship' validation
            'beneficiary_special_instructions' => 'nullable|string|max:1000',
            'beneficiary_is_organization' => 'nullable|boolean',
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
                $itemPrice = $item->variant->final_price;
            } else {
                $itemPrice = $item->product->price;
            }
            $serverTotal += ($itemPrice * $item->quantity);
        }

        // Always update/create customer (for data persistence)
        $customer = $user->customer ?? new Customer();
        $customer->fill([
            'user_id' => $user->id,
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'phone' => $validated['phone'],
            'address' => $validated['address'] ?? $customer->address ?? 'N/A', // Keep existing or set default
        ]);
        $customer->save();

        // SIMPLIFIED: Handle optional beneficiary creation (with removed fields)
        $beneficiaryId = null;
        if ($validated['is_donation'] && $validated['has_beneficiary'] ?? false) {
            // Check if we have meaningful beneficiary data
            $hasBeneficiaryData = false;

            if ($validated['beneficiary_is_organization'] ?? false) {
                // ENHANCED: Auto-set organization name if it's an organization
                $validated['beneficiary_organization_name'] = '3lmni al 9aid initiative';
                $hasBeneficiaryData = true;
            } else {
                $hasBeneficiaryData = !empty($validated['beneficiary_first_name']) || !empty($validated['beneficiary_last_name']);
            }

            if ($hasBeneficiaryData) {
                $beneficiary = DonationBeneficiary::create([
                    'first_name' => $validated['beneficiary_first_name'],
                    'last_name' => $validated['beneficiary_last_name'],
                    'phone' => $validated['beneficiary_phone'],
                    // REMOVED: email field
                    'organization_name' => $validated['beneficiary_organization_name'],
                    // REMOVED: relationship_to_donor field
                    'special_instructions' => $validated['beneficiary_special_instructions'],
                    'is_organization' => $validated['beneficiary_is_organization'] ?? false,
                ]);

                $beneficiaryId = $beneficiary->id;
            }
        }

        // Create order with optional beneficiary
        $order = Order::create([
            'customer_id' => $customer->id,
            'beneficiary_id' => $beneficiaryId, // Link beneficiary if exists
            'total' => $serverTotal,
            'shipping' => 0,
            'status' => Order::STATUS_PENDING,
            'is_donation' => $validated['is_donation'],
            'payment_method' => 'stripe',
            'notes' => $validated['notes'],
        ]);

        // Create order items with variant support (unchanged)
        foreach ($cart->items as $cartItem) {
            $orderItemData = [
                'product_id' => $cartItem->product_id,
                'product_name' => $cartItem->product->name,
                'quantity' => $cartItem->quantity,
                'price' => $cartItem->variant ? $cartItem->variant->final_price : $cartItem->product->price,
            ];

            if ($cartItem->variant) {
                $orderItemData['variant_id'] = $cartItem->variant_id;
                $orderItemData['selected_color'] = $cartItem->variant->color->name;
                $orderItemData['selected_size'] = $cartItem->variant->size->name;
                $orderItemData['selected_color_hex'] = $cartItem->variant->color->hex_code;
            }

            $order->items()->create($orderItemData);
        }

        // Create admin notification
        try {
            $order->load(['customer.user', 'items', 'beneficiary']); // Load beneficiary too

            $this->notificationService->createOrderNotification($order);

            Log::info('✅ Admin notification created for order: ' . $order->id);
        } catch (\Exception $e) {
            Log::error('❌ Failed to create admin notification for order ' . $order->id . ': ' . $e->getMessage());
        }

        // Stripe checkout
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
