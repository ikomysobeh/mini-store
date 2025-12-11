<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class CartController extends Controller
{
   public function index()
{
    $cart = $this->getCart();

    if ($cart) {
        // Load variant relationships
        $cart->load(['items.product', 'items.variant.color', 'items.variant.size']);

        // Transform cart items to include variant info and image URLs
        $cart->items->each(function ($item) {
            // Product image
            if ($item->product && $item->product->image) {
                $item->product->image = asset('storage/' . $item->product->image);
            }

            // ✅ ENHANCED: Add variant display info
            if ($item->variant) {
                $item->variant_display = [
                    'has_variant' => true,
                    'color_name' => $item->variant->color->name ?? null,
                    'color_hex' => $item->variant->color->hex_code ?? null,
                    'size_name' => $item->variant->size->name ?? null,
                    'sku' => $item->variant->sku ?? null,
                ];
            } else {
                $item->variant_display = [
                    'has_variant' => false,
                    'color_name' => null,
                    'color_hex' => null,
                    'size_name' => null,
                    'sku' => null,
                ];
            }
        });

        // Calculate totals (use effective price for variants)
        $subtotal = $cart->items->sum(function ($item) {
            return $item->quantity * $item->price;
        });

        $shipping = $subtotal >= 50 ? 0 : 5.99;
        $total = $subtotal + $shipping;

        $cart->subtotal = $subtotal;
        $cart->shipping = $shipping;
        $cart->total = $total;
        $cart->item_count = $cart->items->sum('quantity');
    }

    $settings = Setting::public()->get()->pluck('value', 'key');
    if (isset($settings['logo']) && $settings['logo']) {
        $settings['logo_url'] = asset('storage/' . $settings['logo']);
    }

    return Inertia::render('Web/Cart', [
        'settings' => $settings,
        'cart' => $cart,
    ]);
}

    public function add(Request $request, Product $product)
    {
        try {
            // Debug logging for CSRF issues in production
            Log::info('🛒 Cart Add Request', [
                'product_id' => $product->id,
                'user_id' => auth()->id(),
                'session_id' => Session::getId(),
                'is_inertia' => $request->header('X-Inertia') ? true : false,
                'csrf_token_present' => $request->header('X-CSRF-TOKEN') ? true : false,
            ]);

            $request->validate([
                'quantity' => 'required|integer|min:1',
                'variant_id' => 'nullable|exists:product_variants,id',
            ]);

            $user = auth()->user();
            if (!$user) {
                Log::warning('🛒 Cart Add - User not authenticated', [
                    'session_id' => Session::getId(),
                ]);
                // For Inertia requests, redirect to login
                if ($request->header('X-Inertia')) {
                    return back()->withErrors(['message' => 'Please log in to add items to cart.']);
                }
                return response()->json([
                    'success' => false,
                    'message' => 'Please log in to add items to cart.'
                ], 401);
            }

            $cart = $this->getOrCreateCart();
            $quantity = $request->quantity;

            // ENHANCED: Handle variant products
            if ($request->variant_id) {
                $variant = ProductVariant::with(['color', 'size'])->find($request->variant_id);

                if (!$variant || $variant->product_id !== $product->id) {
                    if ($request->header('X-Inertia')) {
                        return back()->withErrors(['message' => 'Invalid product variant selected.']);
                    }
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid product variant selected.'
                    ], 400);
                }

                $result = $this->addVariantToCart($cart, $variant, $quantity);

                if ($result['success']) {
                    if ($request->header('X-Inertia')) {
                        return back()->with('success', $result['message']);
                    }
                    return response()->json([
                        'success' => true,
                        'message' => $result['message'],
                        'cart_count' => $cart->items->sum('quantity')
                    ]);
                } else {
                    if ($request->header('X-Inertia')) {
                        return back()->withErrors(['message' => $result['message']]);
                    }
                    return response()->json([
                        'success' => false,
                        'message' => $result['message']
                    ], 400);
                }
            } else {
                // Handle regular products (no variants)
                $result = $this->addRegularToCart($cart, $product, $quantity);

                if ($result['success']) {
                    if ($request->header('X-Inertia')) {
                        return back()->with('success', $result['message']);
                    }
                    return response()->json([
                        'success' => true,
                        'message' => $result['message'],
                        'cart_count' => $cart->items->sum('quantity')
                    ]);
                } else {
                    if ($request->header('X-Inertia')) {
                        return back()->withErrors(['message' => $result['message']]);
                    }
                    return response()->json([
                        'success' => false,
                        'message' => $result['message']
                    ], 400);
                }
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->header('X-Inertia')) {
                return back()->withErrors($e->errors());
            }
            return response()->json([
                'success' => false,
                'message' => 'Invalid input data.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Cart add failed: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'product_id' => $product->id,
                'quantity' => $request->quantity,
                'variant_id' => $request->variant_id,
                'trace' => $e->getTraceAsString()
            ]);

            if ($request->header('X-Inertia')) {
                return back()->withErrors(['message' => 'Something went wrong. Please try again.']);
            }
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong. Please try again.'
            ], 500);
        }
    }

    // FIXED: Add variant to cart with proper variable assignment
    protected function addVariantToCart(Cart $cart, ProductVariant $variant, int $quantity)
    {
        try {
            // Check if variant is active and available
            if (!$variant->is_active) {
                return [
                    'success' => false,
                    'message' => 'This product variant is currently unavailable.'
                ];
            }

            // FIXED: Get variant names first to avoid complex expressions in strings
            $colorName = $variant->color ? $variant->color->name : 'Color';
            $sizeName = $variant->size ? $variant->size->name : 'Size';
            $variantDescription = "({$colorName}, {$sizeName})";

            // Check initial stock availability
            if ($variant->stock < $quantity) {
                $availableStock = $variant->stock;
                if ($availableStock === 0) {
                    return [
                        'success' => false,
                        'message' => "Sorry, this variant {$variantDescription} is out of stock."
                    ];
                } else {
                    return [
                        'success' => false,
                        'message' => "Only {$availableStock} items available for this variant {$variantDescription}."
                    ];
                }
            }

            // Check if item already exists in cart
            $existingItem = $cart->items()
                ->where('product_id', $variant->product_id)
                ->where('variant_id', $variant->id)
                ->first();

            if ($existingItem) {
                // Update existing item
                $newQuantity = $existingItem->quantity + $quantity;

                // Check if new total quantity is available
                if ($variant->stock < $newQuantity) {
                    $availableToAdd = $variant->stock - $existingItem->quantity;
                    if ($availableToAdd <= 0) {
                        return [
                            'success' => false,
                            'message' => "You already have the maximum available quantity of this variant in your cart."
                        ];
                    } else {
                        $currentQuantity = $existingItem->quantity;
                        return [
                            'success' => false,
                            'message' => "You can only add {$availableToAdd} more of this variant to your cart (you currently have {$currentQuantity})."
                        ];
                    }
                }

                $existingItem->update(['quantity' => $newQuantity]);

                return [
                    'success' => true,
                    'message' => "Updated cart! You now have {$newQuantity} of this item."
                ];
            }

            // FIXED: Get product name and calculate price properly
            $productName = $variant->product->name;
            $finalPrice = $variant->product->price + $variant->price_adjustment;

            // Create new cart item with variant
            $cart->items()->create([
                'product_id' => $variant->product_id,
                'variant_id' => $variant->id,
                'selected_color_id' => $variant->color_id,
                'selected_size_id' => $variant->size_id,
                'selected_color' => $colorName !== 'Color' ? $colorName : null,
                'selected_size' => $sizeName !== 'Size' ? $sizeName : null,
                'selected_color_hex' => $variant->color ? $variant->color->hex_code : null,
                'product_name' => $productName,
                'product_name_en' => $variant->product->name_en ?? $variant->product->name,
                'product_name_ar' => $variant->product->name_ar,
                'quantity' => $quantity,
                'price' => $finalPrice,
            ]);

            return [
                'success' => true,
                'message' => "Added {$quantity} item(s) to your cart!"
            ];

        } catch (\Exception $e) {
            Log::error('Add variant to cart failed: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to add item to cart. Please try again.'
            ];
        }
    }

    // FIXED: Add regular product to cart
    protected function addRegularToCart(Cart $cart, Product $product, int $quantity)
    {
        try {
            // Check if product is active
            if (!$product->is_active) {
                return [
                    'success' => false,
                    'message' => 'This product is currently unavailable.'
                ];
            }

            // FIXED: Get product name first
            $productName = $product->name;

            // Handle donation items (always available)
            if ($product->is_donatable) {
                $existingItem = $cart->items()
                    ->where('product_id', $product->id)
                    ->whereNull('variant_id')
                    ->first();

                if ($existingItem) {
                    $newQuantity = $existingItem->quantity + $quantity;
                    $existingItem->update(['quantity' => $newQuantity]);

                    return [
                        'success' => true,
                        'message' => "Updated donation! You now have {$newQuantity} of this item."
                    ];
                } else {
                    $cart->items()->create([
                        'product_id' => $product->id,
                        'product_name' => $productName,
                        'product_name_en' => $product->name_en ?? $product->name,
                        'product_name_ar' => $product->name_ar,
                        'quantity' => $quantity,
                        'price' => $product->price,
                        'is_donation_item' => true,
                    ]);

                    return [
                        'success' => true,
                        'message' => "Added {$quantity} donation item(s) to your cart!"
                    ];
                }
            }

            // Handle regular products with stock
            if ($product->stock < $quantity) {
                $availableStock = $product->stock;
                if ($availableStock === 0) {
                    return [
                        'success' => false,
                        'message' => "Sorry, '{$productName}' is out of stock."
                    ];
                } else {
                    return [
                        'success' => false,
                        'message' => "Only {$availableStock} items available for '{$productName}'."
                    ];
                }
            }

            // Check existing cart items
            $existingItem = $cart->items()
                ->where('product_id', $product->id)
                ->whereNull('variant_id')
                ->first();

            if ($existingItem) {
                $newQuantity = $existingItem->quantity + $quantity;

                if ($product->stock < $newQuantity) {
                    $availableToAdd = $product->stock - $existingItem->quantity;
                    $currentQuantity = $existingItem->quantity;

                    if ($availableToAdd <= 0) {
                        return [
                            'success' => false,
                            'message' => "You already have the maximum available quantity in your cart."
                        ];
                    } else {
                        return [
                            'success' => false,
                            'message' => "You can only add {$availableToAdd} more items to your cart (you currently have {$currentQuantity})."
                        ];
                    }
                }

                $existingItem->update(['quantity' => $newQuantity]);

                return [
                    'success' => true,
                    'message' => "Updated cart! You now have {$newQuantity} of '{$productName}'."
                ];
            }

            // Create new cart item
            $cart->items()->create([
                'product_id' => $product->id,
                'product_name' => $productName,
                'product_name_en' => $product->name_en ?? $product->name,
                'product_name_ar' => $product->name_ar,
                'quantity' => $quantity,
                'price' => $product->price,
            ]);

            return [
                'success' => true,
                'message' => "Added {$quantity} '{$productName}' to your cart!"
            ];

        } catch (\Exception $e) {
            Log::error('Add regular product to cart failed: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to add item to cart. Please try again.'
            ];
        }
    }

    public function update(Request $request, $cartItemId)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1'
        ]);

        $cart = $this->getCart();
        if (!$cart) {
            return redirect()->route('cart.index');
        }

        $cartItem = $cart->items()->findOrFail($cartItemId);

        // NEW: Check stock for variants
        if ($cartItem->variant) {
            if ($cartItem->variant->stock < $request->quantity) {
                return back()->withErrors(['quantity' => 'Insufficient stock for selected variant']);
            }
        } else {
            // Regular product stock check
            if ($cartItem->product->stock < $request->quantity) {
                return back()->withErrors(['quantity' => 'Insufficient stock']);
            }
        }

        $cartItem->update(['quantity' => $request->quantity]);

        return back()->with('success', 'Cart updated');
    }

    public function remove($cartItemId)
    {
        $cart = $this->getCart();
        if (!$cart) {
            return redirect()->route('cart.index');
        }

        $cart->items()->findOrFail($cartItemId)->delete();

        return back()->with('success', 'Item removed from cart');
    }

    public function clear()
    {
        $cart = $this->getCart();
        if ($cart) {
            $cart->items()->delete(); // Delete all items
        }

        return back()->with('success', 'Cart cleared');
    }

    /**
     * Get cart data for API/AJAX requests
     */
    public function getCartData()
    {
        $cart = $this->getCart();

        if (!$cart) {
            return response()->json([
                'items' => [],
                'subtotal' => 0,
                'shipping' => 0,
                'total' => 0,
                'item_count' => 0
            ]);
        }

        $cart->load('items.product');

        // Calculate totals
        $subtotal = $cart->items->sum(function ($item) {
            return $item->quantity * $item->price;
        });

        $shipping = $subtotal >= 50 ? 0 : 5.99;
        $total = $subtotal + $shipping;

        return response()->json([
            'items' => $cart->items,
            'subtotal' => $subtotal,
            'shipping' => $shipping,
            'total' => $total,
            'item_count' => $cart->items->sum('quantity')
        ]);
    }

    private function getCart()
    {
        $customerId = auth()->user()?->customer?->id;
        $sessionId = Session::getId();

        if ($customerId) {
            return Cart::where('customer_id', $customerId)->first();
        }

        return Cart::where('session_id', $sessionId)->first();
    }

    private function getOrCreateCart()
    {
        $cart = $this->getCart();

        if (!$cart) {
            $customerId = auth()->user()?->customer?->id;
            $sessionId = Session::getId();

            $cart = Cart::create([
                'customer_id' => $customerId,
                'session_id' => $customerId ? null : $sessionId,
            ]);
        }

        return $cart;
    }
}
