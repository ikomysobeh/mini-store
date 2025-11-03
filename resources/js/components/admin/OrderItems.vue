<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Package, Heart, Palette, Ruler } from 'lucide-vue-next';

interface VariantDetails {
    id: number | string;
    sku?: string | null;
    color?: {
        name: string;
        hex_code: string;
    } | null;
    size?: {
        name: string;
        category_type: string;
    } | null;
    price_adjustment: number;
}

interface OrderItem {
    id: number;
    quantity: number;
    price: number;
    product_name: string;
    is_donation_item: boolean;
    variant_id?: number | null;
    selected_color?: string | null;
    selected_size?: string | null;
    selected_color_hex?: string | null;
    variant_details?: VariantDetails | null;
    display_name?: string;
    display_sku?: string;
    has_variant?: boolean;
    product?: {
        id: number;
        name: string;
        image_url?: string;
        sku?: string;
        is_donatable?: boolean;
        price: number;
    };
}

interface Props {
    items: OrderItem[];
    currency?: string;
    locale?: string;
}

const { items, currency = 'EUR', locale = 'nl-NL' } = defineProps<Props>();

const formatPrice = (price: number) => {
    return new Intl.NumberFormat(locale, {
        style: 'currency',
        currency: currency
    }).format(price || 0);
};

// Check if item has variant data
const hasVariantData = (item: OrderItem) => {
    return item.variant_details || item.selected_color || item.selected_size || item.variant_id;
};

// Get color info (from variant_details or fallback to selected fields)
const getColorInfo = (item: OrderItem) => {
    if (item.variant_details?.color) {
        return item.variant_details.color;
    }
    if (item.selected_color) {
        return {
            name: item.selected_color,
            hex_code: item.selected_color_hex || '#000000'
        };
    }
    return null;
};

// Get size info (from variant_details or fallback to selected fields)
const getSizeInfo = (item: OrderItem) => {
    if (item.variant_details?.size) {
        return item.variant_details.size;
    }
    if (item.selected_size) {
        return {
            name: item.selected_size,
            category_type: 'general'
        };
    }
    return null;
};

// Get display SKU
const getDisplaySku = (item: OrderItem) => {
    return item.display_sku ||
        item.variant_details?.sku ||
        item.product?.sku ||
        (item.variant_id ? `VAR-${item.variant_id}` : `PROD-${item.product?.id || 'N/A'}`);
};
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle class="flex items-center space-x-2">
                <Package class="h-5 w-5" />
                <span>Order Items</span>
                <Badge variant="outline" class="ml-2">{{ items.length }} items</Badge>
            </CardTitle>
        </CardHeader>
        <CardContent class="space-y-4">
            <div
                v-for="item in items"
                :key="item.id"
                class="flex items-start space-x-4 p-4 border rounded-lg hover:bg-muted/30 transition-colors"
            >
                <!-- Product Image -->
                <div class="flex-shrink-0">
                    <img
                        :src="item.product?.image_url || '/placeholder-product.jpg'"
                        :alt="item.product_name || item.product?.name || 'Product'"
                        class="w-20 h-20 object-cover rounded border"
                    />
                </div>

                <!-- Product Details -->
                <div class="flex-1 min-w-0">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <!-- Product Name -->
                            <h4 class="font-semibold text-lg truncate mb-1">
                                {{ item.display_name || item.product_name || item.product?.name || 'Product' }}
                            </h4>

                            <!-- SKU -->
                            <p class="text-sm text-muted-foreground mb-2">
                                SKU: {{ getDisplaySku(item) }}
                            </p>

                            <!-- VARIANT DETAILS -->
                            <div v-if="hasVariantData(item)" class="space-y-2 mb-3">
                                <!-- Color Information -->
                                <div v-if="getColorInfo(item)" class="flex items-center space-x-2">
                                    <Palette class="h-4 w-4 text-gray-500" />
                                    <span class="text-sm font-medium">Color:</span>
                                    <div class="flex items-center space-x-2">
                                        <div
                                            class="w-4 h-4 rounded-full border border-gray-300"
                                            :style="{ backgroundColor: getColorInfo(item)?.hex_code }"
                                        ></div>
                                        <span class="text-sm">{{ getColorInfo(item)?.name }}</span>
                                    </div>
                                </div>

                                <!-- Size Information -->
                                <div v-if="getSizeInfo(item)" class="flex items-center space-x-2">
                                    <Ruler class="h-4 w-4 text-gray-500" />
                                    <span class="text-sm font-medium">Size:</span>
                                    <div class="flex items-center space-x-2">
                                        <Badge variant="outline" class="text-xs">
                                            {{ getSizeInfo(item)?.name }}
                                        </Badge>
                                        <span class="text-xs text-muted-foreground">
                                            ({{ getSizeInfo(item)?.category_type || 'general' }})
                                        </span>
                                    </div>
                                </div>

                                <!-- Variant ID -->
                                <div v-if="item.variant_id" class="flex items-center space-x-2">
                                    <span class="text-sm font-medium">Variant ID:</span>
                                    <Badge variant="secondary" class="text-xs">
                                        #{{ item.variant_id }}
                                    </Badge>
                                </div>
                            </div>

                            <!-- No Variant Badge -->
                            <div v-else class="mb-3">
                                <Badge variant="outline" class="text-xs text-gray-500">
                                    Standard Product (No Variants)
                                </Badge>
                            </div>

                            <!-- Quantity and Price Info -->
                            <div class="flex items-center space-x-4">
                                <span class="text-sm font-medium bg-muted px-3 py-1 rounded-full">
                                    Qty: {{ item.quantity }}
                                </span>
                                <div class="text-sm text-muted-foreground">
                                    <span>{{ formatPrice(item.price) }} each</span>
                                </div>
                            </div>
                        </div>

                        <!-- Price and Badges -->
                        <div class="text-right ml-4">
                            <p class="text-xl font-bold text-primary mb-2">
                                {{ formatPrice(item.price * item.quantity) }}
                            </p>

                            <div class="space-y-1">
                                <!-- Donation Badge -->
                                <Badge v-if="item.is_donation_item" variant="outline" class="text-xs">
                                    <Heart class="h-3 w-3 mr-1 text-red-500" />
                                    Donation Item
                                </Badge>

                                <!-- Variant Badge -->
                                <Badge v-if="hasVariantData(item)" variant="secondary" class="text-xs block">
                                    <Palette class="h-3 w-3 mr-1" />
                                    Has Variants
                                </Badge>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Enhanced Items Summary -->
            <div class="pt-4 border-t bg-muted/20 p-4 rounded-lg">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-center">
                    <div>
                        <p class="text-sm text-muted-foreground">Total Items</p>
                        <p class="text-lg font-semibold">{{ items.length }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-muted-foreground">Total Quantity</p>
                        <p class="text-lg font-semibold">
                            {{ items.reduce((sum, item) => sum + item.quantity, 0) }}
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-muted-foreground">Variant Items</p>
                        <p class="text-lg font-semibold text-blue-600">
                            {{ items.filter(item => hasVariantData(item)).length }}
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-muted-foreground">Donation Items</p>
                        <p class="text-lg font-semibold text-red-600">
                            {{ items.filter(item => item.is_donation_item).length }}
                        </p>
                    </div>
                </div>

                <!-- Variant Summary -->
                <div v-if="items.some(item => hasVariantData(item))" class="mt-4 pt-4 border-t">
                    <h4 class="text-sm font-medium text-muted-foreground mb-2">Variant Summary:</h4>
                    <div class="flex flex-wrap gap-2">
                        <template v-for="item in items.filter(i => hasVariantData(i))" :key="item.id">
                            <div class="flex items-center space-x-2 bg-dark px-3 py-1 rounded-full border text-sm">
                                <!-- Color dot -->
                                <div
                                    v-if="getColorInfo(item)"
                                    class="w-3 h-3 rounded-full border"
                                    :style="{ backgroundColor: getColorInfo(item)?.hex_code }"
                                ></div>
                                <!-- Variant info -->
                                <span class="font-medium">
                                    {{ getColorInfo(item)?.name || 'No Color' }} /
                                    {{ getSizeInfo(item)?.name || 'No Size' }}
                                </span>
                                <Badge variant="outline" class="text-xs">×{{ item.quantity }}</Badge>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
