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

        $categories = Category::active()
            ->ordered()
            ->get();

        $settings = Setting::public()->get()->pluck('value', 'key');

        // Add logo URL if logo setting exists
        if (isset($settings['logo']) && $settings['logo']) {
            $settings['logo_url'] = asset('storage/' . $settings['logo']);
        }

        $user = Auth::user();

        $cartItems = [];
        if ($user) {
            $cart = $this->cartService->getCart($user);
            if ($cart) {
                $cartItems = $cart->items()->with('product')->get()->map(function ($item) {
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
                            'id' => $item->product->category_id,
                            'name' => $item->product->category->name ?? 'Uncategorized'
                        ],
                    ];
                });
            }
        }

        return Inertia::render('Web/Home', [
            'featuredProducts' => $featuredProducts,
            'categories' => $categories,
            'settings' => $settings,
            'user' => $user,
            'cartItems' => $cartItems,
        ]);
    }
}
