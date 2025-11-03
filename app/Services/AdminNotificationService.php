<?php

namespace App\Services;

use App\Models\AdminNotification;
use Illuminate\Support\Facades\Log;

class AdminNotificationService
{
    /**
     * Create a new admin notification
     */
    public function create($type, $title, $message, $data = [])
    {
        try {
            return AdminNotification::create([
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to create admin notification: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Create notification for new order - ENHANCED
     */
    public function createOrderNotification($order)
    {
        $type = $order->is_donation ? 'new_donation' : 'new_order';
        $title = $order->is_donation ? '🎁 New Donation Received' : '🛒 New Order Received';

        // ENHANCED: Get customer name with fallback
        $customerName = $this->getCustomerName($order);

        $message = sprintf(
            '%s #%s for $%s from %s',
            $order->is_donation ? 'Donation' : 'Order',
            $order->id,
            number_format($order->total, 2),
            $customerName
        );

        $data = [
            'order_id' => $order->id,
            'customer_name' => $customerName,
            'customer_id' => $order->customer_id,
            'total' => $order->total,
            'items_count' => $order->items->count(),
            'order_type' => $order->is_donation ? 'donation' : 'purchase',
            'status' => $order->status,
            'created_at' => $order->created_at->toDateTimeString(),
            'payment_method' => $order->payment_method,
        ];

        Log::info('Creating admin notification for order: ' . $order->id);

        return $this->create($type, $title, $message, $data);
    }

    /**
     * Get customer name with proper fallbacks
     */
    private function getCustomerName($order)
    {
        // Try different ways to get customer name
        if ($order->customer && $order->customer->user) {
            return $order->customer->user->name;
        }

        if ($order->customer && isset($order->customer->name)) {
            return $order->customer->name;
        }

        if (isset($order->customer_name)) {
            return $order->customer_name;
        }

        return 'Guest Customer';
    }

    /**
     * Create notification for payment received (optional)
     */
    public function createPaymentNotification($order)
    {
        $type = 'payment_received';
        $title = '💰 Payment Received';
        $customerName = $this->getCustomerName($order);

        $message = sprintf(
            'Payment of $%s received for Order #%s from %s',
            number_format($order->total, 2),
            $order->id,
            $customerName
        );

        $data = [
            'order_id' => $order->id,
            'customer_name' => $customerName,
            'total' => $order->total,
            'payment_method' => $order->payment_method,
            'paid_at' => $order->paid_at->toDateTimeString(),
        ];

        return $this->create($type, $title, $message, $data);
    }

    /**
     * Get unread notifications count
     */
    public function getUnreadCount()
    {
        return AdminNotification::unread()->count();
    }

    /**
     * Get recent notifications
     */
    public function getRecent($limit = 5)
    {
        return AdminNotification::recent($limit)->get();
    }

    /**
     * Mark notification as read
     */
    public function markAsRead($id)
    {
        $notification = AdminNotification::find($id);
        if ($notification) {
            $notification->markAsRead();
            return true;
        }
        return false;
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead()
    {
        return AdminNotification::unread()->update(['read_at' => now()]);
    }

    /**
     * Delete old notifications (cleanup)
     */
    public function cleanupOld($days = 30)
    {
        return AdminNotification::where('created_at', '<', now()->subDays($days))
            ->delete();
    }

    /**
     * Get notifications with pagination
     */
    public function getPaginated($perPage = 15, $type = null)
    {
        $query = AdminNotification::latest();

        if ($type) {
            $query->ofType($type);
        }

        return $query->paginate($perPage);
    }

    /**
     * Get notification statistics
     */
    public function getStats()
    {
        return [
            'total' => AdminNotification::count(),
            'unread' => AdminNotification::unread()->count(),
            'today' => AdminNotification::whereDate('created_at', today())->count(),
            'this_week' => AdminNotification::where('created_at', '>=', now()->startOfWeek())->count(),
        ];
    }

    // ADD these methods to your existing AdminNotificationService class

    /**
     * Get notifications with filters and pagination
     */
    public function getPaginatedWithFilters($perPage = 15, $type = null, $status = null)
    {
        $query = AdminNotification::latest();

        // Filter by type
        if ($type) {
            $query->ofType($type);
        }

        // Filter by status
        if ($status === 'read') {
            $query->read();
        } elseif ($status === 'unread') {
            $query->unread();
        }

        return $query->paginate($perPage);
    }

    /**
     * Delete notification
     */
    public function deleteNotification($id)
    {
        try {
            return AdminNotification::where('id', $id)->delete() > 0;
        } catch (\Exception $e) {
            Log::error('Failed to delete notification: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Bulk mark notifications as read
     */
    public function bulkMarkAsRead($ids)
    {
        try {
            return AdminNotification::whereIn('id', $ids)
                    ->whereNull('read_at')
                    ->update(['read_at' => now()]) > 0;
        } catch (\Exception $e) {
            Log::error('Failed to bulk mark notifications as read: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Bulk delete notifications
     */
    public function bulkDelete($ids)
    {
        try {
            return AdminNotification::whereIn('id', $ids)->delete() > 0;
        } catch (\Exception $e) {
            Log::error('Failed to bulk delete notifications: ' . $e->getMessage());
            return false;
        }
    }

}
