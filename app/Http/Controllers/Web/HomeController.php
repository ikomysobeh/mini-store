<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use App\Models\Setting;
use App\Services\CartService;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class HomeController extends Controller
{
    protected $cartService;

    public function __construct(CartService $cartService)
    {
        $this->cartService = $cartService;
    }

    public function index()
    {
        // Get featured products
        $featuredProducts = Product::active()
            ->with('category')
            ->take(8)
            ->get()
            ->map(function ($product) {
                $productArray = $product->toArray();
                if ($product->image) {
                    $productArray['image'] = asset('storage/' . $product->image);
                }
                return $productArray;
            });

        // Get categories
        $categories = Category::active()->ordered()->get();

        // Get settings with hero background support
        $settings = Setting::public()->get()->pluck('value', 'key');

        // Add logo URL if logo setting exists
        if (isset($settings['logo']) && $settings['logo']) {
            $settings['logo_url'] = asset('storage/' . $settings['logo']);
        }

        // Convert boolean settings from string to actual boolean
        $settings['hero_use_background_image'] = filter_var($settings['hero_use_background_image'] ?? false, FILTER_VALIDATE_BOOLEAN);

        // Add hero background URL if it exists and is enabled
        if (isset($settings['hero_background_image']) && $settings['hero_background_image'] && $settings['hero_use_background_image']) {
            $settings['hero_background_url'] = asset('storage/' . $settings['hero_background_image']);
        }

        // Get user and cart
        $user = Auth::user();
        $cartItems = collect([]); // Initialize as collection

        if ($user) {
            $cart = $this->cartService->getCart($user);
            if ($cart) {
                $cart->load('items.product.category');

                $cartItems = $cart->items->map(function ($item) {
                    $image = null;
                    if ($item->product && $item->product->image) {
                        $image = asset('storage/' . $item->product->image);
                    }

                    return [
                        'id' => $item->id,
                        'name' => $item->product_name,
                        'quantity' => $item->quantity,
                        'price' => $item->price,
                        'image' => $image,
                        'category' => [
                            'id' => $item->product->category_id ?? null,
                            'name' => $item->product->category->name ?? 'Uncategorized',
                        ],
                    ];
                });
            }
        }

        return Inertia::render('Web/Home', [
            'featuredProducts' => $featuredProducts,
            'categories' => $categories,
            'settings' => $settings, // ✅ Already an array - don't call toArray()
            'user' => $user,
            'cartItems' => $cartItems, // ✅ Already an array - don't call toArray()
        ]);
    }
}
