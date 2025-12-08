<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use App\Services\CartService;
use Illuminate\Support\Facades\Session;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        $user = $request->user();

        // Get cart items for both guests and users
        $cartItems = $this->getCartItems($user);

        // Get settings and transform logo URL
        $settings = \App\Models\Setting::public()->get()->pluck('value', 'key');
        if (isset($settings['logo']) && $settings['logo']) {
            $settings['logo_url'] = asset('storage/' . $settings['logo']);
        }

        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'is_admin' => $user->is_admin ?? false,
                    'email_verified_at' => $user->email_verified_at,
                ] : null,
            ],
            'flash' => [
                'message' => fn () => $request->session()->get('message'),
                'error' => fn () => $request->session()->get('error'),
                'success' => fn () => $request->session()->get('success'),
            ],
            'locale' => app()->getLocale(),
            'cartItems' => $cartItems,
            'categories' => \App\Models\Category::active()->ordered()->get(),
            'settings' => $settings,
        ]);
    }

    private function getCartItems($user)
    {
        // Get cart based on user or session
        $customerId = $user?->customer?->id;
        $sessionId = Session::getId();

        $cart = null;
        if ($customerId) {
            $cart = \App\Models\Cart::where('customer_id', $customerId)->first();
        } else {
            $cart = \App\Models\Cart::where('session_id', $sessionId)->first();
        }

        if (!$cart) {
            return [];
        }

        return $cart->items()->with(['product.category'])->get()->map(function ($item) {
            return [
                'id' => $item->id,
                'name' => $item->product_name,
                'quantity' => $item->quantity,
                'price' => $item->price,
                'image' => $item->product->image ? asset('storage/' . $item->product->image) : null,
                'category' => [
                    'id' => $item->product->category_id,
                    'name' => $item->product->category->name ?? 'Uncategorized'
                ],
                'is_donatable' => $item->product->is_donatable ?? false,
            ];
        })->toArray();
    }
}
