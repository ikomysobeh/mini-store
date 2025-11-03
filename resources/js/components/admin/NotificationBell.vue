<script setup lang="ts">
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Bell } from 'lucide-vue-next';

// State
const notifications = ref([]);
const unreadCount = ref(0);
const showDropdown = ref(false);
const isLoading = ref(false);

// ADD: Get user data from Inertia
const page = usePage();
const user = computed(() => page.props.auth?.user || null);

// ADD: Only fetch notifications if user is authenticated and admin
const canAccessNotifications = computed(() => {
    return user.value && (user.value.is_admin === true || user.value.is_admin === 1);
});

// Fetch recent notifications
const fetchNotifications = async () => {
    // ADDED: Check if user can access notifications
    if (!canAccessNotifications.value) {
        return;
    }

    try {
        isLoading.value = true;
        const response = await fetch('/admin/notifications/recent', {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        });

        if (response.ok) {
            const data = await response.json();
            notifications.value = data.notifications || [];
            unreadCount.value = data.unread_count || 0;
        }
    } catch (error) {
        console.error('Failed to fetch notifications:', error);
        // Don't show error to user on auth pages
    } finally {
        isLoading.value = false;
    }
};

// Mark notification as read
const markAsRead = async (notificationId) => {
    if (!canAccessNotifications.value) return;

    try {
        await fetch(`/admin/notifications/${notificationId}/read`, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                'Accept': 'application/json'
            }
        });

        // Update local state
        const notification = notifications.value.find(n => n.id === notificationId);
        if (notification && !notification.read_at) {
            notification.read_at = new Date().toISOString();
            unreadCount.value = Math.max(0, unreadCount.value - 1);
        }
    } catch (error) {
        console.error('Failed to mark notification as read:', error);
    }
};

// Handle notification click
const handleNotificationClick = (notification) => {
    if (!notification.read_at) {
        markAsRead(notification.id);
    }

    showDropdown.value = false;

    // Navigate based on notification type
    if (notification.data?.order_id) {
        router.visit(`/admin/orders/${notification.data.order_id}`);
    }
};

// Auto-refresh notifications
let refreshInterval = null;

onMounted(() => {
    // Only start fetching if user can access notifications
    if (canAccessNotifications.value) {
        fetchNotifications();

        // Refresh every 30 seconds
        refreshInterval = setInterval(fetchNotifications, 30000);

        // Close dropdown when clicking outside
        document.addEventListener('click', closeDropdown);
    }
});

onUnmounted(() => {
    if (refreshInterval) {
        clearInterval(refreshInterval);
    }
    document.removeEventListener('click', closeDropdown);
});

const closeDropdown = (event) => {
    if (!event.target.closest('.notification-dropdown')) {
        showDropdown.value = false;
    }
};

const toggleDropdown = () => {
    if (canAccessNotifications.value) {
        showDropdown.value = !showDropdown.value;
    }
};

const formatTimeAgo = (date) => {
    const now = new Date();
    const notificationDate = new Date(date);
    const diffInSeconds = Math.floor((now - notificationDate) / 1000);

    if (diffInSeconds < 60) return 'Just now';
    if (diffInSeconds < 3600) return `${Math.floor(diffInSeconds / 60)}m ago`;
    if (diffInSeconds < 86400) return `${Math.floor(diffInSeconds / 3600)}h ago`;
    return `${Math.floor(diffInSeconds / 86400)}d ago`;
};
</script>

<template>
    <!-- ADDED: Only render if user can access notifications -->
    <div v-if="canAccessNotifications" class="relative notification-dropdown">
        <Button
            @click="toggleDropdown"
            variant="ghost"
            size="icon"
            class="relative"
        >
            <Bell class="h-5 w-5" />
            <span
                v-if="unreadCount > 0"
                class="absolute -top-1 -right-1 h-4 w-4 bg-red-500 text-white text-xs rounded-full flex items-center justify-center"
            >
                {{ unreadCount > 9 ? '9+' : unreadCount }}
            </span>
        </Button>

        <!-- Dropdown Menu -->
        <div
            v-if="showDropdown"
            class="absolute right-0 mt-2 w-80 bg-background border border-border rounded-lg shadow-lg z-50 max-h-96 overflow-hidden"
        >
            <!-- Header -->
            <div class="px-4 py-3 border-b border-border">
                <div class="flex items-center justify-between">
                    <h3 class="font-semibold">Notifications</h3>
                    <span
                        v-if="unreadCount > 0"
                        class="text-xs bg-red-100 text-red-800 px-2 py-1 rounded-full"
                    >
                        {{ unreadCount }} new
                    </span>
                </div>
            </div>

            <!-- Notifications List -->
            <div class="max-h-64 overflow-y-auto">
                <div v-if="isLoading" class="p-4 text-center text-muted-foreground">
                    Loading...
                </div>

                <div v-else-if="notifications.length === 0" class="p-4 text-center text-muted-foreground">
                    No notifications
                </div>

                <div v-else>
                    <button
                        v-for="notification in notifications"
                        :key="notification.id"
                        @click="handleNotificationClick(notification)"
                        class="w-full px-4 py-3 text-left hover:bg-muted border-b border-border last:border-b-0 transition-colors"
                        :class="{ 'bg-blue-50': !notification.read_at }"
                    >
                        <div class="flex items-start space-x-3">
                            <div class="flex-shrink-0 mt-1">
                                <div :class="[
                                    'w-2 h-2 rounded-full',
                                    notification.read_at ? 'bg-gray-300' : 'bg-blue-500'
                                ]"></div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-foreground">
                                    {{ notification.title }}
                                </p>
                                <p class="text-xs text-muted-foreground mt-1">
                                    {{ notification.message }}
                                </p>
                                <p class="text-xs text-muted-foreground mt-1">
                                    {{ formatTimeAgo(notification.created_at) }}
                                </p>
                            </div>
                        </div>
                    </button>
                </div>
            </div>

            <!-- Footer -->
            <div class="px-4 py-3 border-t border-border bg-muted/30">
                <a
                    href="/admin/notifications"
                    class="text-xs text-primary hover:underline"
                >
                    View all notifications
                </a>
            </div>
        </div>
    </div>
</template>
