<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Order;
use App\Models\Customer;
use App\Models\OrderItem;
use App\Services\PaymentService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class OrderService
{
    protected $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    public function createFromCart(Cart $cart, Customer $customer, array $options = [])
    {
        if ($cart->isEmpty()) {
            throw new \Exception('Cart is empty');
        }

        DB::beginTransaction();
        try {
            // Calculate totals
            $subtotal = $cart->total;
            $shipping = $options['is_donation'] ? 0 : $this->calculateShipping($subtotal);
            $total = $subtotal + $shipping;

            // Create order
            $order = Order::create([
                'customer_id' => $customer->id,
                'total' => $total,
                'shipping' => $shipping,
                'status' => Order::STATUS_PENDING,
                'is_donation' => $options['is_donation'] ?? false,
                'notes' => $options['notes'] ?? null,
            ]);

            // Create order items and reduce stock
            foreach ($cart->items as $cartItem) {
                $this->createOrderItem($order, $cartItem, $options['is_donation'] ?? false);
            }

            // Process payment
            if (isset($options['payment_method_id'])) {
                $payment = $this->paymentService->processPayment($order, $options['payment_method_id']);

                $order->update([
                    'payment_method' => 'stripe',
                    'payment_id' => $payment->gateway_payment_id,
                    'paid_at' => now(),
                ]);
            }

            DB::commit();

            // Send confirmation email
            $this->sendOrderConfirmation($order);

            return $order;

        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    protected function createOrderItem(Order $order, $cartItem, $isDonation)
    {
        // Check stock availability
        if (!$cartItem->product->isInStock($cartItem->quantity)) {
            throw new \Exception("Product {$cartItem->product->name} is out of stock");
        }

        // Create order item
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $cartItem->product_id,
            'quantity' => $cartItem->quantity,
            'price' => $cartItem->price,
            'product_name' => $cartItem->product_name,
            'is_donation_item' => $isDonation,
        ]);

        // Reduce stock for purchases (not donations)
        if (!$isDonation) {
            $cartItem->product->decrementStock($cartItem->quantity);
        }
    }

    protected function calculateShipping($subtotal)
    {
        // Free shipping over $50
        if ($subtotal >= 50) {
            return 0;
        }

        // Flat rate shipping
        return 9.99;
    }

    public function updateOrderStatus(Order $order, $status)
    {
        $oldStatus = $order->status;
        $order->updateStatus($status);

        // Send status update email
        if ($oldStatus !== $status) {
            $this->sendStatusUpdateEmail($order, $oldStatus, $status);
        }

        return $order;
    }

    public function cancelOrder(Order $order, $reason = null)
    {
        if ($order->status === Order::STATUS_CANCELLED) {
            throw new \Exception('Order is already cancelled');
        }

        DB::beginTransaction();
        try {
            // Restore stock for cancelled orders
            foreach ($order->items as $orderItem) {
                if (!$orderItem->is_donation_item) {
                    $orderItem->product->incrementStock($orderItem->quantity);
                }
            }

            // Update order status
            $order->update([
                'status' => Order::STATUS_CANCELLED,
                'notes' => $reason,
            ]);

            // Process refund if payment was made
            if ($order->isPaid()) {
                $this->paymentService->refundPayment($order);
            }

            DB::commit();

            // Send cancellation email
            $this->sendCancellationEmail($order);

            return $order;

        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    protected function sendOrderConfirmation(Order $order)
    {
        if ($order->is_donation) {
            // Send donation thank you email
            Mail::send('emails.donation-thanks', ['order' => $order], function ($message) use ($order) {
                $message->to($order->customer->user->email)
                    ->subject('Thank you for your donation!');
            });
        } else {
            // Send order confirmation email
            Mail::send('emails.order-confirmation', ['order' => $order], function ($message) use ($order) {
                $message->to($order->customer->user->email)
                    ->subject('Order Confirmation #' . $order->id);
            });
        }
    }

    protected function sendStatusUpdateEmail(Order $order, $oldStatus, $newStatus)
    {
        Mail::send('emails.order-status-update', [
            'order' => $order,
            'oldStatus' => $oldStatus,
            'newStatus' => $newStatus
        ], function ($message) use ($order) {
            $message->to($order->customer->user->email)
                ->subject('Order Update #' . $order->id);
        });
    }

    protected function sendCancellationEmail(Order $order)
    {
        Mail::send('emails.order-cancelled', ['order' => $order], function ($message) use ($order) {
            $message->to($order->customer->user->email)
                ->subject('Order Cancelled #' . $order->id);
        });
    }
}
