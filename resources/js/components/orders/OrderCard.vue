<script setup lang="ts">
import { Card } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Calendar, Package, Heart, Palette, Ruler, ShoppingCart, Eye, CreditCard } from 'lucide-vue-next';
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';

interface VariantDisplay {
    color?: {
        name: string;
        hex_code: string;
    };
    size?: {
        name: string;
        category: string;
    };
    id?: number;
    sku?: string;
}

interface OrderItem {
    id: number;
    product_name: string;
    quantity: number;
    price: number;
    is_donation_item: boolean;
    variant_display?: VariantDisplay;
    display_name?: string;
    product?: {
        image_url?: string;
    };
}

interface Order {
    id: number;
    status: string;
    is_donation: boolean;
    total: number;
    created_at: string;
    items: OrderItem[];
    paid_at?: string;
}

const { order } = defineProps<{
    order: Order;
}>();

const isRetryingPayment = ref(false);

const formatPrice = (price: number) => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD'
    }).format(price);
};

const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('en-US', {
        day: '2-digit',
        month: 'short',
        year: 'numeric'
    });
};

const getStatusColor = (status: string) => {
    const colors = {
        pending: 'bg-yellow-500/20 text-yellow-700 dark:text-yellow-400',
        processing: 'bg-blue-500/20 text-blue-700 dark:text-blue-400',
        failed: 'bg-red-500/20 text-red-700 dark:text-red-400',
        success: 'bg-green-500/20 text-green-700 dark:text-green-400',
        done: 'bg-gray-500/20 text-gray-700 dark:text-gray-400'
    };
    return colors[status] || 'bg-gray-100 text-gray-800';
};

const viewOrder = () => {
    router.get(`/my-orders/${order.id}`);
};

// ✅ FIXED: Redirect to cart after reorder
const reorderItems = () => {
    if (order.is_donation) {
        return;
    }

    router.post(`/my-orders/${order.id}/reorder`, {}, {
        preserveScroll: false, // ✅ CHANGED: Allow scroll to top
        onSuccess: () => {
            // ✅ FIXED: Navigate to cart to see added items and update navbar
            router.visit('/cart');
        },
        onError: (errors) => {
            console.error('Reorder failed:', errors);
        }
    });
};

const retryPayment = () => {
    isRetryingPayment.value = true;
    
    router.post(`/my-orders/${order.id}/retry-payment`, {}, {
        preserveScroll: true,
        onStart: () => {
            isRetryingPayment.value = true;
        },
        onFinish: () => {
            isRetryingPayment.value = false;
        },
        onError: () => {
            isRetryingPayment.value = false;
        }
    });
};

const canRetryPayment = () => {
    return order.status === 'pending' && !order.paid_at;
};

const variantItemsCount = order.items.filter(item => item.variant_display).length;
const totalQuantity = order.items.reduce((sum, item) => sum + item.quantity, 0);
</script>

<template>
    <Card class="overflow-hidden hover:shadow-lg transition-shadow duration-200">
        <div class="p-6">
            <!-- Order Header -->
            <div class="flex items-start justify-between mb-4">
                <div>
                    <div class="flex items-center space-x-3 mb-2">
                        <h3 class="text-lg font-semibold text-foreground">
                            Order #{{ order.id }}
                        </h3>
                        <Badge :class="getStatusColor(order.status)">
                            {{ order.status.charAt(0).toUpperCase() + order.status.slice(1) }}
                        </Badge>
                        <Badge v-if="order.is_donation" class="bg-warning/20 text-warning">
                            <Heart class="h-3 w-3 mr-1" />
                            Donation
                        </Badge>
                        <Badge v-if="canRetryPayment()" variant="destructive">
                            Unpaid
                        </Badge>
                    </div>
                    <div class="flex items-center text-sm text-muted-foreground space-x-4">
                        <div class="flex items-center">
                            <Calendar class="h-4 w-4 mr-1" />
                            {{ formatDate(order.created_at) }}
                        </div>
                        <div class="flex items-center">
                            <Package class="h-4 w-4 mr-1" />
                            {{ order.items.length }} items ({{ totalQuantity }} qty)
                        </div>
                        <div v-if="variantItemsCount > 0" class="flex items-center">
                            <Palette class="h-4 w-4 mr-1" />
                            {{ variantItemsCount }} variants
                        </div>
                    </div>
                </div>
                <div class="text-right">
                    <div class="text-2xl font-bold text-foreground">
                        {{ formatPrice(order.total) }}
                    </div>
                </div>
            </div>

            <!-- Payment reminder for pending orders -->
            <div v-if="canRetryPayment()" class="mb-4 p-3 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg">
                <div class="flex items-start space-x-2">
                    <CreditCard class="h-5 w-5 text-yellow-600 dark:text-yellow-400 mt-0.5" />
                    <div class="flex-1">
                        <h4 class="text-sm font-medium text-yellow-800 dark:text-yellow-300">
                            Payment Required
                        </h4>
                        <p class="text-sm text-yellow-700 dark:text-yellow-400 mt-1">
                            This order is awaiting payment. Complete your payment to process this order.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Order Items Preview -->
            <div class="space-y-3 mb-6">
                <div
                    v-for="(item, index) in order.items.slice(0, 3)"
                    :key="item.id"
                    class="flex items-center space-x-3 p-3 bg-muted/30 rounded-lg"
                >
                    <!-- Product Image -->
                    <div class="flex-shrink-0">
                        <img
                            :src="item.product?.image_url || '/placeholder-product.jpg'"
                            :alt="item.display_name || item.product_name"
                            class="w-12 h-12 object-cover rounded border"
                        />
                    </div>

                    <!-- Product Details -->
                    <div class="flex-1 min-w-0">
                        <h4 class="font-medium text-foreground truncate">
                            {{ item.display_name || item.product_name }}
                        </h4>

                        <!-- Variant Information -->
                        <div v-if="item.variant_display" class="flex items-center space-x-3 mt-1">
                            <!-- Color -->
                            <div v-if="item.variant_display.color" class="flex items-center space-x-1">
                                <div
                                    class="w-3 h-3 rounded-full border border-gray-300"
                                    :style="{ backgroundColor: item.variant_display.color.hex_code }"
                                ></div>
                                <span class="text-xs text-muted-foreground">
                                    {{ item.variant_display.color.name }}
                                </span>
                            </div>

                            <!-- Size -->
                            <div v-if="item.variant_display.size" class="flex items-center space-x-1">
                                <Ruler class="h-3 w-3 text-muted-foreground" />
                                <span class="text-xs text-muted-foreground">
                                    {{ item.variant_display.size.name }}
                                </span>
                            </div>
                        </div>

                        <!-- No Variant Badge -->
                        <div v-else class="mt-1">
                            <Badge variant="outline" class="text-xs">
                                Standard Product
                            </Badge>
                        </div>

                        <div class="flex items-center space-x-2 mt-1">
                            <span class="text-sm text-muted-foreground">
                                Qty: {{ item.quantity }}
                            </span>
                            <span class="text-sm font-medium text-foreground">
                                {{ formatPrice(item.price) }} each
                            </span>
                            <Badge v-if="item.is_donation_item" variant="outline" class="text-xs">
                                <Heart class="h-3 w-3 mr-1 text-red-500" />
                                Donation
                            </Badge>
                        </div>
                    </div>

                    <!-- Item Total -->
                    <div class="text-right">
                        <div class="font-semibold text-foreground">
                            {{ formatPrice(item.price * item.quantity) }}
                        </div>
                    </div>
                </div>

                <!-- Show more items indicator -->
                <div v-if="order.items.length > 3" class="text-center py-2">
                    <span class="text-sm text-muted-foreground">
                        + {{ order.items.length - 3 }} more items
                    </span>
                </div>
            </div>

            <!-- Variant Summary (if order has variants) -->
            <div v-if="variantItemsCount > 0" class="mb-4 p-3 bg-info/10 rounded-lg">
                <h5 class="text-sm font-medium text-info mb-2">Variant Summary:</h5>
                <div class="flex flex-wrap gap-2">
                    <div
                        v-for="item in order.items.filter(i => i.variant_display)"
                        :key="item.id"
                        class="flex items-center space-x-2 bg-card px-2 py-1 rounded text-xs"
                    >
                        <!-- Color dot -->
                        <div
                            v-if="item.variant_display?.color"
                            class="w-3 h-3 rounded-full border"
                            :style="{ backgroundColor: item.variant_display.color.hex_code }"
                        ></div>
                        <!-- Variant text -->
                        <span class="text-info">
                            {{ item.variant_display?.color?.name || 'No Color' }} /
                            {{ item.variant_display?.size?.name || 'No Size' }}
                        </span>
                        <span class="text-info/80">(×{{ item.quantity }})</span>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-between pt-4 border-t border-border">
                <div class="text-sm text-muted-foreground">
                    {{ totalQuantity }} items • {{ variantItemsCount }} with variants
                </div>
                <div class="flex space-x-3">
                    <!-- Pay Now button for pending orders -->
                    <Button
                        v-if="canRetryPayment()"
                        variant="default"
                        size="sm"
                        @click="retryPayment"
                        :disabled="isRetryingPayment"
                        class="bg-green-600 hover:bg-green-700"
                    >
                        <CreditCard class="h-4 w-4 mr-2" />
                        {{ isRetryingPayment ? 'Processing...' : 'Pay Now' }}
                    </Button>

                    

                    <!-- Reorder button -->
                    <Button
                        v-if="!order.is_donation && !canRetryPayment()"
                        variant="default"
                        size="sm"
                        @click="reorderItems"
                    >
                        <ShoppingCart class="h-4 w-4 mr-2" />
                        Reorder
                    </Button>
                </div>
            </div>
        </div>
    </Card>
</template>
