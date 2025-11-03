<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ShoppingCart, Package, Eye } from 'lucide-vue-next';

interface Order {
    id: number;
    total: number;
    status: string;
    created_at: string;
}

interface Props {
    orders: Order[];
    hasCustomer: boolean;
    currency?: string;
    locale?: string;
}

const props = withDefaults(defineProps<Props>(), {
    currency: 'EUR',
    locale: 'nl-NL'
});

const formatPrice = (price: number) => {
    return new Intl.NumberFormat(props.locale, {
        style: 'currency',
        currency: props.currency
    }).format(price || 0);
};

const formatDateShort = (date: string) => {
    return new Date(date).toLocaleDateString(props.locale, {
        day: '2-digit',
        month: 'short',
        year: 'numeric'
    });
};

const getOrderStatusColor = (status: string) => {
    const colors = {
        pending: 'bg-yellow-100 text-yellow-800',
        processing: 'bg-blue-100 text-blue-800',
        shipped: 'bg-purple-100 text-purple-800',
        delivered: 'bg-green-100 text-green-800',
        cancelled: 'bg-red-100 text-red-800'
    };
    return colors[status] || 'bg-gray-100 text-gray-800';
};
</script>

<template>
    <!-- Recent Orders -->
    <Card v-if="orders.length > 0">
        <CardHeader>
            <CardTitle class="flex items-center space-x-2">
                <ShoppingCart class="h-5 w-5" />
                <span>Recent Orders</span>
                <Badge variant="outline">{{ orders.length }}</Badge>
            </CardTitle>
        </CardHeader>
        <CardContent class="space-y-4">

            <div
                v-for="order in orders"
                :key="order.id"
                class="flex items-center justify-between p-4 border rounded-lg hover:bg-muted/30 transition-colors"
            >
                <div class="flex items-center space-x-4">
                    <div class="p-2 bg-blue-100 rounded-lg">
                        <Package class="h-4 w-4 text-blue-600" />
                    </div>
                    <div>
                        <p class="font-medium">Order #{{ order.id }}</p>
                        <p class="text-sm text-muted-foreground">{{ formatDateShort(order.created_at) }}</p>
                    </div>
                </div>
                <div class="text-right">
                    <p class="font-medium">{{ formatPrice(order.total) }}</p>
                    <Badge :class="getOrderStatusColor(order.status)" class="text-xs">
                        {{ order.status }}
                    </Badge>
                </div>
                <Button variant="ghost" size="sm" as="a" :href="`/admin/orders/${order.id}`">
                    <Eye class="h-4 w-4" />
                </Button>
            </div>

            <div class="text-center pt-4 border-t">
                <Button variant="outline" as="a" href="/admin/orders" class="w-full">
                    View All Orders
                </Button>
            </div>

        </CardContent>
    </Card>

    <!-- No Orders -->
    <Card v-else-if="hasCustomer">
        <CardHeader>
            <CardTitle class="flex items-center space-x-2">
                <ShoppingCart class="h-5 w-5" />
                <span>Orders</span>
            </CardTitle>
        </CardHeader>
        <CardContent class="text-center py-8">
            <div class="w-16 h-16 bg-muted rounded-full flex items-center justify-center mx-auto mb-4">
                <ShoppingCart class="h-8 w-8 text-muted-foreground" />
            </div>
            <h3 class="text-lg font-semibold mb-2">No orders yet</h3>
            <p class="text-muted-foreground">This user hasn't placed any orders.</p>
        </CardContent>
    </Card>
</template>
