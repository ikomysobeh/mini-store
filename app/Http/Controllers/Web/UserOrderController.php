<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class UserOrderController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        // DEBUG: Log request data
        Log::info('🔍 UserOrderController::index - Debug Info', [
            'user_id' => $user?->id,
            'customer_id' => $user?->customer?->id,
            'request_params' => $request->all(),
            'query_string' => $request->getQueryString(),
        ]);

        // ✅ FIX: Handle user without customer profile
        if (!$user || !$user->customer) {
            Log::warning('❌ User has no customer profile', ['user_id' => $user?->id]);
            
            // Return empty state instead of redirecting
            return Inertia::render('Web/Orders/Index', [
                'orders' => [
                    'data' => [],
                    'links' => [],
                    'meta' => [
                        'current_page' => 1,
                        'last_page' => 1,
                        'per_page' => 10,
                        'total' => 0,
                    ],
                ],
                'stats' => $this->getEmptyStats(),
                'filters' => [],
            ]);
        }

        // ENHANCED: Load variant data with colors and sizes
        $query = Order::with([
            'items.product',
            'items.variant.color',
            'items.variant.size',
            'customer'
        ])->where('customer_id', $user->customer->id);

        // DEBUG: Log base query
        Log::info('🎯 Base query customer_id', ['customer_id' => $user->customer->id]);

        // Apply filters with debugging
        if ($request->status) {
            Log::info('🔍 Applying status filter', ['status' => $request->status]);
            $query->where('status', $request->status);
        }

        if ($request->type) {
            Log::info('🔍 Applying type filter', ['type' => $request->type]);
            if ($request->type === 'donation') {
                $query->where('is_donation', true);
            } elseif ($request->type === 'purchase') {
                $query->where('is_donation', false);
            }
        }

        if ($request->search) {
            Log::info('🔍 Applying search filter', ['search' => $request->search]);
            $query->where(function($q) use ($request) {
                $q->where('id', 'like', '%' . $request->search . '%')
                    ->orWhereHas('items', function($itemQuery) use ($request) {
                        $itemQuery->where('product_name', 'like', '%' . $request->search . '%')
                            ->orWhere('selected_color', 'like', '%' . $request->search . '%')
                            ->orWhere('selected_size', 'like', '%' . $request->search . '%');
                    });
            });
        }

        // Date range filtering with debugging
        if ($request->date_range) {
            Log::info('🔍 Applying date range filter', ['date_range' => $request->date_range]);
            switch ($request->date_range) {
                case 'last_month':
                    $query->where('created_at', '>=', now()->subMonth());
                    break;
                case 'last_3_months':
                    $query->where('created_at', '>=', now()->subMonths(3));
                    break;
                case 'last_year':
                    $query->where('created_at', '>=', now()->subYear());
                    break;
                case 'this_year':
                    $query->whereYear('created_at', now()->year);
                    break;
            }
        }

        // DEBUG: Log final SQL query
        $sqlQuery = $query->toSql();
        $bindings = $query->getBindings();
        Log::info('📋 Final SQL Query', [
            'sql' => $sqlQuery,
            'bindings' => $bindings
        ]);

        $orders = $query->latest()->paginate(10);

        // DEBUG: Log results
        Log::info('📊 Query Results', [
            'total_orders' => $orders->total(),
            'current_page_count' => $orders->count(),
            'first_order_id' => $orders->first()?->id,
        ]);

        // ENHANCED: Transform order data to include image URLs and variant details
        $orders->getCollection()->transform(function ($order) {
            $order->items->each(function ($item) {
                // Add product image URL
                if ($item->product && $item->product->image) {
                    $item->product->image_url = asset('storage/' . $item->product->image);
                }

                // Add enhanced variant display data
                $item->variant_display = $this->getVariantDisplayData($item);
                $item->display_name = $this->getItemDisplayName($item);
            });
            return $order;
        });

        // ENHANCED: Calculate user stats with variant information
        $stats = $this->calculateUserStats($user->customer->id);

        // DEBUG: Log final response data
        Log::info('📤 Response Data', [
            'orders_count' => $orders->count(),
            'stats' => $stats,
            'filters' => $request->only(['status', 'type', 'search', 'date_range']),
        ]);

        return Inertia::render('Web/Orders/Index', [
            'orders' => $orders,
            'stats' => $stats,
            'filters' => $request->only(['status', 'type', 'search', 'date_range']),
        ]);
    }

    public function show(Order $order)
    {
        $user = auth()->user();

        // Check if order belongs to the authenticated user
        if ($order->customer_id !== $user->customer->id) {
            abort(403, 'Unauthorized access to order');
        }

        // ENHANCED: Load relationships including variant data
        $order->load([
            'items.product',
            'items.variant.color',
            'items.variant.size',
            'customer.user'
        ]);

        // ENHANCED: Transform data with variant information
        $order->items->each(function ($item) {
            // Add product image URL
            if ($item->product && $item->product->image) {
                $item->product->image_url = asset('storage/' . $item->product->image);
            }

            // Add enhanced variant display data
            $item->variant_display = $this->getVariantDisplayData($item);
            $item->display_name = $this->getItemDisplayName($item);
        });

        return Inertia::render('Web/Orders/Show', [
            'order' => $order,
        ]);
    }

    public function reorder(Order $order)
    {
        $user = auth()->user();

        // Check if order belongs to the authenticated user
        if ($order->customer_id !== $user->customer->id) {
            abort(403, 'Unauthorized access to order');
        }

        // Don't allow reordering donations
        if ($order->is_donation) {
            return back()->with('error', 'Cannot reorder donations');
        }

        // ENHANCED: Add items to cart with variant information
        $cart = app(\App\Services\CartService::class)->getOrCreateCart($user);
        $addedItems = 0;
        $unavailableItems = [];

        foreach ($order->items as $item) {
            if ($item->product) {
                // Check stock considering variant
                $availableStock = $item->variant_id && $item->variant
                    ? $item->variant->stock
                    : $item->product->stock;

                if ($availableStock >= $item->quantity) {
                    // Add to cart with variant information if available
                    if ($item->variant_id && $item->variant) {
                        $cart->addItem($item->product, $item->quantity, [
                            'variant_id' => $item->variant_id,
                            'color' => $item->selected_color,
                            'size' => $item->selected_size,
                            'color_hex' => $item->selected_color_hex,
                        ]);
                    } else {
                        $cart->addItem($item->product, $item->quantity);
                    }
                    $addedItems++;
                } else {
                    $unavailableItems[] = [
                        'name' => $item->display_name ?? $item->product_name,
                        'requested' => $item->quantity,
                        'available' => $availableStock
                    ];
                }
            }
        }

        if ($addedItems > 0) {
            $message = "Added {$addedItems} items to your cart";
            if (!empty($unavailableItems)) {
                $message .= ". Some items were not available in the requested quantities.";
            }
            return redirect()->route('cart.index')->with('success', $message);
        } else {
            return back()->with('error', 'No items could be added to cart (out of stock)');
        }
    }

    // ✅ NEW: Return empty stats structure
    private function getEmptyStats()
    {
        return [
            'total_orders' => 0,
            'total_purchases' => 0,
            'total_donations' => 0,
            'total_spent' => 0,
            'this_year_spent' => 0,
            'recent_orders' => 0,
            'orders_with_variants' => 0,
            'favorite_colors' => [],
        ];
    }

    // ENHANCED: Get variant display data
    private function getVariantDisplayData($item)
    {
        $variantData = [];

        // Color information
        if ($item->selected_color) {
            $variantData['color'] = [
                'name' => $item->selected_color,
                'hex_code' => $item->selected_color_hex ?? '#000000'
            ];
        } elseif ($item->variant && $item->variant->color) {
            $variantData['color'] = [
                'name' => $item->variant->color->name,
                'hex_code' => $item->variant->color->hex_code
            ];
        }

        // Size information
        if ($item->selected_size) {
            $variantData['size'] = [
                'name' => $item->selected_size,
                'category' => 'general'
            ];
        } elseif ($item->variant && $item->variant->size) {
            $variantData['size'] = [
                'name' => $item->variant->size->name,
                'category' => $item->variant->size->category_type ?? 'general'
            ];
        }

        // Additional variant info
        if ($item->variant_id) {
            $variantData['id'] = $item->variant_id;
            if ($item->variant && $item->variant->sku) {
                $variantData['sku'] = $item->variant->sku;
            }
        }

        return !empty($variantData) ? $variantData : null;
    }

    // ENHANCED: Get item display name with variant info
    private function getItemDisplayName($item)
    {
        $name = $item->product_name ?? ($item->product->name ?? 'Unknown Product');

        $variantParts = [];
        if ($item->selected_color) {
            $variantParts[] = $item->selected_color;
        }
        if ($item->selected_size) {
            $variantParts[] = $item->selected_size;
        }

        if (!empty($variantParts)) {
            $name .= ' (' . implode(', ', $variantParts) . ')';
        }

        return $name;
    }

    // ENHANCED: Calculate user stats with variant breakdown
    private function calculateUserStats($customerId)
    {
        $baseQuery = Order::where('customer_id', $customerId);

        $totalOrders = (clone $baseQuery)->count();
        $totalPurchases = (clone $baseQuery)->where('is_donation', false)->count();
        $totalDonations = (clone $baseQuery)->where('is_donation', true)->count();
        $totalSpent = (clone $baseQuery)->sum('total');
        $thisYearSpent = (clone $baseQuery)->whereYear('created_at', now()->year)->sum('total');
        $recentOrders = (clone $baseQuery)->where('created_at', '>=', now()->subDays(30))->count();

        // NEW: Variant-specific stats
        $ordersWithVariants = (clone $baseQuery)->whereHas('items', function($q) {
            $q->where(function($query) {
                $query->whereNotNull('variant_id')
                    ->orWhereNotNull('selected_color')
                    ->orWhereNotNull('selected_size');
            });
        })->count();

        $favoriteColors = Order::where('customer_id', $customerId)
            ->with('items')
            ->get()
            ->pluck('items')
            ->flatten()
            ->filter(function($item) {
                return $item->selected_color;
            })
            ->countBy('selected_color')
            ->sortDesc()
            ->take(3)
            ->toArray();

        return [
            'total_orders' => $totalOrders,
            'total_purchases' => $totalPurchases,
            'total_donations' => $totalDonations,
            'total_spent' => $totalSpent,
            'this_year_spent' => $thisYearSpent,
            'recent_orders' => $recentOrders,
            'orders_with_variants' => $ordersWithVariants,
            'favorite_colors' => $favoriteColors,
        ];
    }

    public function retryPayment(Order $order)
{
    $user = auth()->user();

    // Check if order belongs to the authenticated user
    if ($order->customer_id !== $user->customer->id) {
        abort(403, 'Unauthorized access to order');
    }

    // Only allow retry for pending orders
    if ($order->status !== Order::STATUS_PENDING) {
        return back()->with('error', 'This order cannot be paid again. Status: ' . $order->status);
    }

    // Check if order is already paid
    if ($order->paid_at) {
        return back()->with('error', 'This order has already been paid');
    }

    try {
        // Create new Stripe payment URL for this order
        $stripeService = app(\App\Services\StripeService::class);
        $stripeUrl = $stripeService->createPaymentUrl($order);

        // Redirect user to Stripe payment page
        return Inertia::location($stripeUrl);
    } catch (\Exception $e) {
        Log::error('Retry payment failed', [
            'order_id' => $order->id,
            'error' => $e->getMessage()
        ]);
        
        return back()->with('error', 'Failed to create payment link. Please try again.');
    }
}

}
