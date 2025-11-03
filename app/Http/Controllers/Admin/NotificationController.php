<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminNotificationService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class NotificationController extends Controller
{
    protected $notificationService;

    public function __construct(AdminNotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * ADDED: Show notifications center page
     */
    public function index(Request $request)
    {
        $type = $request->get('type');
        $status = $request->get('status'); // 'read', 'unread'

        // Get paginated notifications
        $notifications = $this->notificationService->getPaginatedWithFilters(15, $type, $status);

        // Get stats
        $stats = $this->notificationService->getStats();

        return Inertia::render('Admin/Notifications/Index', [
            'notifications' => $notifications,
            'stats' => $stats,
            'filters' => [
                'type' => $type,
                'status' => $status,
            ]
        ]);
    }

    /**
     * Get unread count (for AJAX requests)
     */
    public function getUnreadCount()
    {
        return response()->json([
            'count' => $this->notificationService->getUnreadCount()
        ]);
    }

    /**
     * Get recent notifications (for notification bell dropdown)
     */
    public function getRecent()
    {
        $notifications = $this->notificationService->getRecent(5);

        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $this->notificationService->getUnreadCount()
        ]);
    }

    /**
     * Mark notification as read
     */
    public function markAsRead($id)
    {
        $success = $this->notificationService->markAsRead($id);

        return response()->json([
            'success' => $success,
            'unread_count' => $this->notificationService->getUnreadCount()
        ]);
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead()
    {
        $this->notificationService->markAllAsRead();

        return response()->json([
            'success' => true,
            'message' => 'All notifications marked as read'
        ]);
    }

    /**
     * ADDED: Delete notification
     */
    public function destroy($id)
    {
        $success = $this->notificationService->deleteNotification($id);

        if ($success) {
            return back()->with('success', 'Notification deleted successfully');
        }

        return back()->with('error', 'Failed to delete notification');
    }

    /**
     * ADDED: Bulk actions
     */
    public function bulkAction(Request $request)
    {
        $action = $request->get('action');
        $ids = $request->get('ids', []);

        $success = false;
        $message = '';

        switch ($action) {
            case 'mark_read':
                $success = $this->notificationService->bulkMarkAsRead($ids);
                $message = $success ? 'Notifications marked as read' : 'Failed to mark notifications as read';
                break;

            case 'delete':
                $success = $this->notificationService->bulkDelete($ids);
                $message = $success ? 'Notifications deleted successfully' : 'Failed to delete notifications';
                break;
        }

        return response()->json([
            'success' => $success,
            'message' => $message
        ]);
    }
}
