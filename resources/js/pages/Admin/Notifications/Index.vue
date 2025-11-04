<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Bell,
    ShoppingCart,
    Heart,
    AlertTriangle,
    XCircle,
    Check,
    CheckCheck,
    Trash2,
    Filter,
    Search,
    Eye,
    Calendar
} from 'lucide-vue-next';
import { ref, computed, watch } from 'vue';

const props = defineProps({
    notifications: { type: Object, required: true },
    stats: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) }
});

// State
const selectedNotifications = ref([]);
const searchTerm = ref('');
const selectedType = ref(props.filters.type || '');
const selectedStatus = ref(props.filters.status || '');
const showBulkActions = ref(false);

// Computed
const allSelected = computed(() => {
    return props.notifications.data.length > 0 &&
        selectedNotifications.value.length === props.notifications.data.length;
});

const someSelected = computed(() => {
    return selectedNotifications.value.length > 0 &&
        selectedNotifications.value.length < props.notifications.data.length;
});

const hasSelected = computed(() => selectedNotifications.value.length > 0);

// Methods
const toggleSelectAll = () => {
    if (allSelected.value) {
        selectedNotifications.value = [];
    } else {
        selectedNotifications.value = props.notifications.data.map(n => n.id);
    }
};

const toggleSelect = (id) => {
    const index = selectedNotifications.value.indexOf(id);
    if (index === -1) {
        selectedNotifications.value.push(id);
    } else {
        selectedNotifications.value.splice(index, 1);
    }
};

const applyFilters = () => {
    const filters = {};
    if (selectedType.value) filters.type = selectedType.value;
    if (selectedStatus.value) filters.status = selectedStatus.value;

    router.get('/admin/notifications', filters, {
        preserveState: true,
        preserveScroll: true
    });
};

const clearFilters = () => {
    selectedType.value = '';
    selectedStatus.value = '';
    router.get('/admin/notifications', {}, {
        preserveState: true,
        preserveScroll: true
    });
};

const bulkMarkAsRead = async () => {
    try {
        const response = await fetch('/admin/notifications/bulk-action', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            },
            body: JSON.stringify({
                action: 'mark_read',
                ids: selectedNotifications.value
            })
        });

        if (response.ok) {
            selectedNotifications.value = [];
            router.reload();
        }
    } catch (error) {
        console.error('Failed to mark notifications as read:', error);
    }
};

const bulkDelete = async () => {
    if (confirm('Are you sure you want to delete the selected notifications?')) {
        try {
            const response = await fetch('/admin/notifications/bulk-action', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                },
                body: JSON.stringify({
                    action: 'delete',
                    ids: selectedNotifications.value
                })
            });

            if (response.ok) {
                selectedNotifications.value = [];
                router.reload();
            }
        } catch (error) {
            console.error('Failed to delete notifications:', error);
        }
    }
};

const markAsRead = async (id) => {
    try {
        await fetch(`/admin/notifications/${id}/read`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            }
        });

        router.reload();
    } catch (error) {
        console.error('Failed to mark notification as read:', error);
    }
};

const deleteNotification = (id) => {
    if (confirm('Are you sure you want to delete this notification?')) {
        router.delete(`/admin/notifications/${id}`);
    }
};

const viewOrder = (notification) => {
    if (notification.data && notification.data.order_id) {
        router.get(`/admin/orders/${notification.data.order_id}`);
    }
};

const getNotificationIcon = (type) => {
    switch (type) {
        case 'new_order': return ShoppingCart;
        case 'new_donation': return Heart;
        case 'low_stock': return AlertTriangle;
        case 'order_cancelled': return XCircle;
        default: return Bell;
    }
};

const getNotificationColor = (type) => {
    switch (type) {
        case 'new_order': return 'bg-primary/20 text-primary';
        case 'new_donation': return 'bg-accent/20 text-accent-foreground';
        case 'low_stock': return 'bg-warning/20 text-warning-foreground';
        case 'order_cancelled': return 'bg-destructive/20 text-destructive';
        default: return 'bg-muted/50 text-muted-foreground';
    }
};

const formatDate = (timestamp) => {
    return new Date(timestamp).toLocaleString();
};

const formatTime = (timestamp) => {
    const now = new Date();
    const time = new Date(timestamp);
    const diff = now.getTime() - time.getTime();

    const minutes = Math.floor(diff / 60000);
    const hours = Math.floor(diff / 3600000);
    const days = Math.floor(diff / 86400000);

    if (minutes < 1) return 'Just now';
    if (minutes < 60) return `${minutes}m ago`;
    if (hours < 24) return `${hours}h ago`;
    return `${days}d ago`;
};

// Watch for bulk actions
watch(hasSelected, (value) => {
    showBulkActions.value = value;
});
</script>

<template>
    <AdminLayout title="Notifications">
        <Head title="Notifications" />

        <div class="space-y-6">

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <Card>
                    <CardContent class="p-4">
                        <div class="flex items-center space-x-2">
                            <Bell class="h-5 w-5 text-primary" />
                            <div>
                                <p class="text-2xl font-bold text-foreground">{{ stats.total }}</p>
                                <p class="text-sm text-muted-foreground">Total</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent class="p-4">
                        <div class="flex items-center space-x-2">
                            <div class="w-3 h-3 bg-destructive rounded-full"></div>
                            <div>
                                <p class="text-2xl font-bold text-foreground">{{ stats.unread }}</p>
                                <p class="text-sm text-muted-foreground">Unread</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent class="p-4">
                        <div class="flex items-center space-x-2">
                            <Calendar class="h-5 w-5 text-accent" />
                            <div>
                                <p class="text-2xl font-bold text-foreground">{{ stats.today }}</p>
                                <p class="text-sm text-muted-foreground">Today</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent class="p-4">
                        <div class="flex items-center space-x-2">
                            <Calendar class="h-5 w-5 text-secondary" />
                            <div>
                                <p class="text-2xl font-bold text-foreground">{{ stats.this_week }}</p>
                                <p class="text-sm text-muted-foreground">This Week</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Filters and Actions -->
            <Card>
                <CardHeader>
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between space-y-4 sm:space-y-0">
                        <CardTitle class="flex items-center space-x-2">
                            <Bell class="h-5 w-5" />
                            <span>All Notifications</span>
                        </CardTitle>

                        <!-- Bulk Actions -->
                        <div v-if="showBulkActions" class="flex items-center space-x-2">
                            <span class="text-sm text-muted-foreground">
                                {{ selectedNotifications.length }} selected
                            </span>
                            <Button @click="bulkMarkAsRead" size="sm" variant="outline">
                                <CheckCheck class="h-4 w-4 mr-2" />
                                Mark Read
                            </Button>
                            <Button @click="bulkDelete" size="sm" variant="outline">
                                <Trash2 class="h-4 w-4 mr-2" />
                                Delete
                            </Button>
                        </div>
                    </div>
                </CardHeader>

                <CardContent class="space-y-4">

                    <!-- Filters -->
                    <div class="flex flex-col sm:flex-row gap-4">
                        <div class="flex-1">
                            <div class="relative">
                                <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                                <Input
                                    v-model="searchTerm"
                                    placeholder="Search notifications..."
                                    class="pl-9"
                                />
                            </div>
                        </div>

                        <div class="flex gap-2">
                            <select
                                v-model="selectedType"
                                class="px-3 py-2 border border-input bg-background rounded-md text-sm"
                            >
                                <option value="">All Types</option>
                                <option value="new_order">Orders</option>
                                <option value="new_donation">Donations</option>
                                <option value="low_stock">Low Stock</option>
                                <option value="order_cancelled">Cancelled</option>
                            </select>

                            <select
                                v-model="selectedStatus"
                                class="px-3 py-2 border border-input bg-background rounded-md text-sm"
                            >
                                <option value="">All Status</option>
                                <option value="unread">Unread</option>
                                <option value="read">Read</option>
                            </select>

                            <Button @click="applyFilters" variant="outline" size="sm">
                                <Filter class="h-4 w-4 mr-2" />
                                Filter
                            </Button>

                            <Button @click="clearFilters" variant="ghost" size="sm">
                                Clear
                            </Button>
                        </div>
                    </div>

                    <!-- Select All -->
                    <div class="flex items-center space-x-2 py-2 border-b">
                        <Checkbox
                            :checked="allSelected"
                            :indeterminate="someSelected"
                            @click="toggleSelectAll"
                        />
                        <span class="text-sm text-muted-foreground">Select all notifications</span>
                    </div>

                    <!-- Notifications List -->
                    <div class="space-y-1">
                        <div
                            v-for="notification in notifications.data"
                            :key="notification.id"
                            :class="[
                                'flex items-start space-x-3 p-4 rounded-lg hover:bg-muted/50 transition-colors',
                                !notification.read_at ? 'bg-card border-l-4 border-primary' : ''
                            ]"
                        >
                            <!-- Checkbox -->
                            <Checkbox
                                :checked="selectedNotifications.includes(notification.id)"
                                @click="toggleSelect(notification.id)"
                                class="mt-1"
                            />

                            <!-- Icon -->
                            <div :class="['p-2 rounded-full', getNotificationColor(notification.type)]">
                                <component :is="getNotificationIcon(notification.type)" class="h-4 w-4" />
                            </div>

                            <!-- Content -->
                            <div class="flex-1 min-w-0">
                                <div class="flex items-start justify-between">
                                    <div>
                                        <p :class="[
                                            'font-medium',
                                            !notification.read_at ? 'text-foreground' : 'text-muted-foreground'
                                        ]">
                                            {{ notification.title }}
                                        </p>
                                        <p :class="[
                                            'text-sm mt-1',
                                            !notification.read_at ? 'text-foreground' : 'text-muted-foreground'
                                        ]">
                                            {{ notification.message }}
                                        </p>
                                        <div class="flex items-center space-x-4 mt-2 text-xs text-muted-foreground">
                                            <span>{{ formatTime(notification.created_at) }}</span>
                                            <span>•</span>
                                            <span>{{ formatDate(notification.created_at) }}</span>
                                        </div>
                                    </div>

                                    <!-- Unread Badge -->
                                    <div v-if="!notification.read_at" class="w-2 h-2 bg-primary rounded-full flex-shrink-0"></div>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="flex items-center space-x-1">
                                <Button
                                    v-if="notification.data && notification.data.order_id"
                                    @click="viewOrder(notification)"
                                    size="sm"
                                    variant="ghost"
                                    title="View Order"
                                >
                                    <Eye class="h-4 w-4" />
                                </Button>

                                <Button
                                    v-if="!notification.read_at"
                                    @click="markAsRead(notification.id)"
                                    size="sm"
                                    variant="ghost"
                                    title="Mark as Read"
                                >
                                    <Check class="h-4 w-4" />
                                </Button>

                                <Button
                                    @click="deleteNotification(notification.id)"
                                    size="sm"
                                    variant="ghost"
                                    title="Delete"
                                >
                                    <Trash2 class="h-4 w-4" />
                                </Button>
                            </div>
                        </div>
                    </div>

                    <!-- Empty State -->
                    <div v-if="notifications.data.length === 0" class="text-center py-12">
                        <Bell class="h-12 w-12 mx-auto mb-4 text-muted-foreground/50" />
                        <h3 class="text-lg font-medium mb-2">No notifications found</h3>
                        <p class="text-muted-foreground">
                            {{ filters.type || filters.status ? 'Try adjusting your filters' : 'You\'re all caught up!' }}
                        </p>
                    </div>

                    <!-- Pagination -->
                    <div v-if="notifications.data.length > 0" class="flex items-center justify-between pt-4 border-t">
                        <div class="text-sm text-muted-foreground">
                            Showing {{ notifications.from }} to {{ notifications.to }} of {{ notifications.total }} notifications
                        </div>

                        <div class="flex space-x-1">
                            <Button
                                v-for="link in notifications.links"
                                :key="link.label"
                                @click="router.get(link.url)"
                                :disabled="!link.url"
                                :variant="link.active ? 'default' : 'outline'"
                                size="sm"
                                v-html="link.label"
                                class="min-w-[2.5rem]"
                            />
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AdminLayout>
</template>
