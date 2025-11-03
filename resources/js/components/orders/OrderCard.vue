<script setup lang="ts">
import { Card } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Calendar, Package, Heart, Palette, Ruler, ShoppingCart, Eye } from 'lucide-vue-next';
import { router } from '@inertiajs/vue3';

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
}

const { order } = defineProps<{
    order: Order;
}>();

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
        pending: 'bg-yellow-100 text-yellow-800',
        processing: 'bg-blue-100 text-blue-800',
        shipped: 'bg-purple-100 text-purple-800',
        delivered: 'bg-green-100 text-green-800',
        cancelled: 'bg-red-100 text-red-800'
    };
    return colors[status] || 'bg-gray-100 text-gray-800';
};

const viewOrder = () => {
    router.get(`/my-orders/${order.id}`);
};

const reorderItems = () => {
    if (order.is_donation) {
        return;
    }

    router.post(`/my-orders/${order.id}/reorder`, {}, {
        preserveScroll: true,
        onSuccess: () => {
            // Success message handled by controller
        },
        onError: () => {
            // Error message handled by controller
        }
    });
};

// Count items with variants
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
                        <Badge v-if="order.is_donation" class="bg-red-100 text-red-800">
                            <Heart class="h-3 w-3 mr-1" />
                            Donation
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
            <div v-if="variantItemsCount > 0" class="mb-4 p-3 bg-blue-50 rounded-lg">
                <h5 class="text-sm font-medium text-blue-900 mb-2">Variant Summary:</h5>
                <div class="flex flex-wrap gap-2">
                    <div
                        v-for="item in order.items.filter(i => i.variant_display)"
                        :key="item.id"
                        class="flex items-center space-x-2 bg-white px-2 py-1 rounded text-xs"
                    >
                        <!-- Color dot -->
                        <div
                            v-if="item.variant_display?.color"
                            class="w-3 h-3 rounded-full border"
                            :style="{ backgroundColor: item.variant_display.color.hex_code }"
                        ></div>
                        <!-- Variant text -->
                        <span class="text-blue-800">
                            {{ item.variant_display?.color?.name || 'No Color' }} /
                            {{ item.variant_display?.size?.name || 'No Size' }}
                        </span>
                        <span class="text-blue-600">(×{{ item.quantity }})</span>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-between pt-4 border-t border-border">
                <div class="text-sm text-muted-foreground">
                    {{ totalQuantity }} items • {{ variantItemsCount }} with variants
                </div>
                <div class="flex space-x-3">

                    <Button
                        v-if="!order.is_donation"
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
