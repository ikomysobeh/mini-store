<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Product;
use App\Models\Customer;
use Illuminate\Support\Facades\Session;

class CartService
{
    public function getCart($user = null)
    {
        $user = $user ?: auth()->user();
        $customerId = $user?->customer?->id;
        $sessionId = Session::getId();

        if ($customerId) {
            return Cart::where('customer_id', $customerId)->first();
        }

        return Cart::where('session_id', $sessionId)->first();
    }

    public function getOrCreateCart($user = null)
    {
        $cart = $this->getCart($user);

        if (!$cart) {
            $user = $user ?: auth()->user();
            $customerId = $user?->customer?->id;
            $sessionId = Session::getId();

            $cart = Cart::create([
                'customer_id' => $customerId,
                'session_id' => $customerId ? null : $sessionId,
                'total' => 0,
            ]);
        }

        return $cart;
    }

    public function addItem(Product $product, $quantity = 1, $user = null)
    {
        if (!$product->isInStock($quantity)) {
            throw new \Exception('Product is out of stock');
        }

        $cart = $this->getOrCreateCart($user);
        $cartItem = $cart->addItem($product, $quantity);

        return $cartItem;
    }

    public function updateItem($cartItemId, $quantity)
    {
        $cart = $this->getCart();

        if (!$cart) {
            throw new \Exception('Cart not found');
        }

        $cartItem = $cart->items()->findOrFail($cartItemId);

        if (!$cartItem->product->isInStock($quantity)) {
            throw new \Exception('Insufficient stock');
        }

        $cartItem->update(['quantity' => $quantity]);

        return $cartItem;
    }

    public function removeItem($cartItemId)
    {
        $cart = $this->getCart();

        if (!$cart) {
            throw new \Exception('Cart not found');
        }

        $cartItem = $cart->items()->findOrFail($cartItemId);
        $cartItem->delete();

        return true;
    }

    public function clearCart($user = null)
    {
        $cart = $this->getCart($user);

        if ($cart) {
            $cart->clear();
        }

        return true;
    }

    public function mergeGuestCart($user)
    {
        $sessionId = Session::getId();
        $guestCart = Cart::where('session_id', $sessionId)->first();

        if (!$guestCart || $guestCart->isEmpty()) {
            return;
        }

        $customer = $user->customer;
        if (!$customer) {
            return;
        }

        $userCart = Cart::where('customer_id', $customer->id)->first();

        if (!$userCart) {
            // Transfer guest cart to user
            $guestCart->update([
                'customer_id' => $customer->id,
                'session_id' => null,
            ]);
        } else {
            // Merge guest cart items into user cart
            foreach ($guestCart->items as $guestItem) {
                $existingItem = $userCart->items()
                    ->where('product_id', $guestItem->product_id)
                    ->first();

                if ($existingItem) {
                    $existingItem->increment('quantity', $guestItem->quantity);
                } else {
                    $userCart->items()->create([
                        'product_id' => $guestItem->product_id,
                        'quantity' => $guestItem->quantity,
                        'price' => $guestItem->price,
                        'product_name' => $guestItem->product_name,
                    ]);
                }
            }

            // Delete guest cart
            $guestCart->delete();
        }
    }

    public function getCartCount($user = null)
    {
        $cart = $this->getCart($user);
        return $cart ? $cart->total_items : 0;
    }

    public function getCartTotal($user = null)
    {
        $cart = $this->getCart($user);
        return $cart ? $cart->total : 0;
    }
}
