<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import {
    ShoppingCart, Eye, ArrowUpDown, Clock, Package, Truck,
    CheckCircle, XCircle, Heart
} from 'lucide-vue-next';
import { computed } from 'vue';

interface Order {
    id: number;
    total: number;
    status: string;
    payment_status: string;
    payment_method?: string;
    is_donation: boolean;
    created_at: string;
    discount?: number;
    customer?: {
        user?: {
            name: string;
            email: string;
        };
    };
    email?: string;
    items?: Array<{
        quantity: number;
    }>;
}

interface Props {
    orders: Order[];
    selectedOrders: number[];
    sortBy: string;
    sortDirection: string;
    currency?: string;
    locale?: string;
}

interface Emits {
    (e: 'update:selectedOrders', value: number[]): void;
    (e: 'toggleSort', column: string): void;
    (e: 'updateOrderStatus', orderId: number, status: string): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

// Helper functions
const formatPrice = (price: number) => {
    return new Intl.NumberFormat(props.locale || 'nl-NL', {
        style: 'currency',
        currency: props.currency || 'EUR'
    }).format(price || 0);
};

const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString(props.locale || 'nl-NL', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const getStatusColor = (status: string) => {
    const colors = {
        pending: 'bg-yellow-100 text-yellow-800 border-yellow-200',
        processing: 'bg-blue-100 text-blue-800 border-blue-200',
        shipped: 'bg-purple-100 text-purple-800 border-purple-200',
        delivered: 'bg-green-100 text-green-800 border-green-200',
        cancelled: 'bg-red-100 text-red-800 border-red-200'
    };
    return colors[status] || 'bg-gray-100 text-gray-800 border-gray-200';
};

const getStatusIcon = (status: string) => {
    const icons = {
        pending: Clock,
        processing: Package,
        shipped: Truck,
        delivered: CheckCircle,
        cancelled: XCircle
    };
    return icons[status] || Clock;
};

const getPaymentStatusColor = (status: string) => {
    const colors = {
        paid: 'bg-green-100 text-green-800',
        pending: 'bg-yellow-100 text-yellow-800',
        failed: 'bg-red-100 text-red-800',
        refunded: 'bg-gray-100 text-gray-800'
    };
    return colors[status] || 'bg-gray-100 text-gray-800';
};

const isAllSelected = computed(() => {
    return props.orders.length > 0 && props.selectedOrders.length === props.orders.length;
});

const toggleSelectAll = () => {
    if (isAllSelected.value) {
        emit('update:selectedOrders', []);
    } else {
        emit('update:selectedOrders', props.orders.map(order => order.id));
    }
};

const toggleOrder = (orderId: number) => {
    const currentSelected = [...props.selectedOrders];
    const index = currentSelected.indexOf(orderId);

    if (index > -1) {
        currentSelected.splice(index, 1);
    } else {
        currentSelected.push(orderId);
    }

    emit('update:selectedOrders', currentSelected);
};
</script>

<template>
    <Card class="shadow-sm">
        <CardContent class="p-0">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <!-- Table Header -->
                    <thead class="border-b bg-muted/30">
                    <tr>
                        <th class="p-4 text-left">
                            <input
                                type="checkbox"
                                :checked="isAllSelected"
                                @change="toggleSelectAll"
                                class="rounded border-border"
                            />
                        </th>
                        <th class="p-4 text-left">
                            <button
                                @click="emit('toggleSort', 'id')"
                                class="flex items-center space-x-1 hover:text-primary font-medium"
                            >
                                <span>Order</span>
                                <ArrowUpDown class="h-4 w-4" />
                            </button>
                        </th>
                        <th class="p-4 text-left font-medium">Customer</th>
                        <th class="p-4 text-left font-medium">Items</th>
                        <th class="p-4 text-left">
                            <button
                                @click="emit('toggleSort', 'total')"
                                class="flex items-center space-x-1 hover:text-primary font-medium"
                            >
                                <span>Total</span>
                                <ArrowUpDown class="h-4 w-4" />
                            </button>
                        </th>
                        <th class="p-4 text-left font-medium">Payment</th>
                        <th class="p-4 text-left font-medium">Status</th>
                        <th class="p-4 text-left">
                            <button
                                @click="emit('toggleSort', 'created_at')"
                                class="flex items-center space-x-1 hover:text-primary font-medium"
                            >
                                <span>Date</span>
                                <ArrowUpDown class="h-4 w-4" />
                            </button>
                        </th>
                        <th class="p-4 text-right font-medium">Actions</th>
                    </tr>
                    </thead>

                    <!-- Table Body -->
                    <tbody>
                    <tr
                        v-for="order in orders"
                        :key="order.id"
                        class="border-b hover:bg-muted/30 transition-colors"
                    >
                        <!-- Checkbox -->
                        <td class="p-4">
                            <input
                                type="checkbox"
                                :checked="selectedOrders.includes(order.id)"
                                @change="toggleOrder(order.id)"
                                class="rounded border-border"
                            />
                        </td>

                        <!-- Order Info -->
                        <td class="p-4">
                            <div class="space-y-1">
                                <p class="font-medium">#{{ order.id }}</p>
                                <div class="flex items-center space-x-2">
                                    <Badge v-if="order.is_donation" variant="outline" class="text-xs">
                                        <Heart class="h-3 w-3 mr-1 text-red-500" />
                                        Donation
                                    </Badge>
                                </div>
                            </div>
                        </td>

                        <!-- Customer -->
                        <td class="p-4">
                            <div class="flex items-center space-x-3">
                                <Avatar class="h-8 w-8">
                                    <AvatarFallback class="bg-primary/10 text-primary text-sm">
                                        {{ order.customer?.user?.name?.charAt(0) || 'G' }}
                                    </AvatarFallback>
                                </Avatar>
                                <div>
                                    <p class="font-medium text-sm">
                                        {{ order.customer?.user?.name || 'Guest Customer' }}
                                    </p>
                                    <p class="text-xs text-muted-foreground">
                                        {{ order.customer?.user?.email || order.email }}
                                    </p>
                                </div>
                            </div>
                        </td>

                        <!-- Items -->
                        <td class="p-4">
                            <div class="text-sm">
                                <p class="font-medium">{{ order.items?.length || 0 }} items</p>
                                <p class="text-muted-foreground">
                                    {{ order.items?.reduce((sum, item) => sum + parseInt(item.quantity.toString()), 0) || 0 }} qty
                                </p>
                            </div>
                        </td>

                        <!-- Total -->
                        <td class="p-4">
                            <div class="font-medium">{{ formatPrice(order.total) }}</div>
                            <div v-if="order.discount && order.discount > 0" class="text-xs text-green-600">
                                -{{ formatPrice(order.discount) }} discount
                            </div>
                        </td>

                        <!-- Payment Status -->
                        <td class="p-4">
                            <Badge
                                :class="getPaymentStatusColor(order.payment_status)"
                                class="text-xs px-2 py-1"
                            >
                                {{ order.payment_status || 'pending' }}
                            </Badge>
                            <div class="text-xs text-muted-foreground mt-1">
                                {{ order.payment_method || 'N/A' }}
                            </div>
                        </td>

                        <!-- Order Status -->
                        <td class="p-4">
                            <Badge
                                :class="getStatusColor(order.status)"
                                class="text-xs px-2 py-1 border flex items-center w-fit"
                            >
                                <component
                                    :is="getStatusIcon(order.status)"
                                    class="h-3 w-3 mr-1"
                                />
                                {{ order.status }}
                            </Badge>
                        </td>

                        <!-- Date -->
                        <td class="p-4">
                            <div class="text-sm">{{ formatDate(order.created_at) }}</div>
                        </td>

                        <!-- Actions -->
                        <td class="p-4 text-right">
                            <div class="flex items-center justify-end space-x-2">
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    as="a"
                                    :href="`/admin/orders/${order.id}`"
                                    title="View Order"
                                >
                                    <Eye class="h-4 w-4" />
                                </Button>

                            </div>
                        </td>
                    </tr>
                    </tbody>
                </table>

                <!-- Empty State -->
                <div v-if="orders.length === 0" class="text-center py-16">
                    <div class="w-24 h-24 bg-muted rounded-full flex items-center justify-center mx-auto mb-6">
                        <ShoppingCart class="h-12 w-12 text-muted-foreground" />
                    </div>
                    <h3 class="text-xl font-semibold mb-2">No orders found</h3>
                    <p class="text-muted-foreground mb-6">
                        Orders will appear here when customers place them
                    </p>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
