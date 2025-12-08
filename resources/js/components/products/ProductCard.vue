<!-- resources/js/components/products/ProductCard.vue -->
<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Heart, ShoppingCart } from 'lucide-vue-next';
import { ref, computed } from 'vue';
import { useLocale } from '@/composables/useLocale';

const { localizedUrl, productUrl, cartAddUrl } = useLocale();

const { product, viewMode, user } = defineProps({
    product: { type: Object, required: true },
    viewMode: { type: String, default: 'grid' },
    user: { type: Object, default: null }
});

const isLoading = ref(false);

// Computed price display
const priceDisplay = computed(() => {
    if (product.has_variants && product.price_range) {
        if (product.price_range.min === product.price_range.max) {
            return formatCurrency(product.price_range.min);
        }
        return `${formatCurrency(product.price_range.min)} - ${formatCurrency(product.price_range.max)}`;
    }
    return formatCurrency(product.price);
});

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD'
    }).format(amount);
};

const goToProduct = () => {
    router.visit(productUrl(product.slug));
};

const addToCart = (event) => {
    event.stopPropagation();

    // If product has variants, redirect to product page for selection
    if (product.has_variants) {
        router.visit(productUrl(product.slug));
        return;
    }

    // For products without variants, add directly to cart
    isLoading.value = true;

    router.post(cartAddUrl(product.id), {
        quantity: 1
    }, {
        preserveScroll: true,
        onSuccess: () => {
            // Success handled by flash message
        },
        onError: () => {
            // Error handled by flash message
        },
        onFinish: () => {
            isLoading.value = false;
        }
    });
};
</script>

<template>
    <!-- Grid View -->
    <div
        v-if="viewMode === 'grid'"
        class="bg-white rounded-lg shadow-sm border hover:shadow-md transition-shadow cursor-pointer group"
        @click="goToProduct"
    >
        <!-- Product Image -->
        <div class="aspect-square overflow-hidden rounded-t-lg bg-gray-100">
            <img
                v-if="product.image"
                :src="product.image"
                :alt="product.name"
                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
            />
            <div v-else class="w-full h-full flex items-center justify-center">
                <Package class="h-16 w-16 text-gray-400" />
            </div>

            <!-- Badges -->
            <div class="absolute top-2 left-2 space-y-1">
                <span v-if="product.is_donatable" class="bg-info text-info-foreground px-2 py-1 rounded text-xs">
                    Donatable
                </span>
                <span v-if="product.total_stock === 0" class="bg-destructive text-destructive-foreground px-2 py-1 rounded text-xs">
                    Out of Stock
                </span>
                <span v-else-if="product.total_stock <= 10" class="bg-warning text-warning-foreground px-2 py-1 rounded text-xs">
                    Low Stock
                </span>
            </div>
        </div>

        <div class="p-4">
            <!-- Product Name -->
            <h3 class="font-medium text-gray-900 mb-1 line-clamp-2">{{ product.name }}</h3>

            <!-- Category -->
            <p class="text-sm text-gray-600 mb-2">{{ product.category?.name }}</p>

            <!-- Price -->
            <div class="flex items-center justify-between mb-3">
                <span class="text-lg font-bold text-primary">{{ priceDisplay }}</span>
                <span v-if="product.has_variants" class="text-xs text-gray-500">
                    {{ product.variants_count }} variants
                </span>
            </div>

            <!-- Colors & Sizes Preview -->
            <div v-if="product.has_variants" class="mb-3 space-y-2">
                <!-- Colors -->
                <div v-if="product.available_colors && product.available_colors.length > 0" class="flex items-center space-x-1">
                    <span class="text-xs text-muted-foreground">Colors:</span>
                    <div class="flex space-x-1">
                        <div
                            v-for="color in product.available_colors.slice(0, 4)"
                            :key="color.id"
                            :style="{ backgroundColor: color.hex_code }"
                            class="w-4 h-4 rounded-full border border-border"
                            :title="color.name"
                        />
                        <span v-if="product.available_colors.length > 4" class="text-xs text-gray-500">
                            +{{ product.available_colors.length - 4 }}
                        </span>
                    </div>
                </div>

                <!-- Sizes -->
                <div v-if="product.available_sizes && product.available_sizes.length > 0" class="flex items-center space-x-1">
                    <span class="text-xs text-gray-600">Sizes:</span>
                    <div class="flex flex-wrap gap-1">
                        <span
                            v-for="size in product.available_sizes.slice(0, 3)"
                            :key="size.name"
                            class="text-xs bg-gray-100 px-1 py-0.5 rounded"
                        >
                            {{ size.name }}
                        </span>
                        <span v-if="product.available_sizes.length > 3" class="text-xs text-gray-500">
                            +{{ product.available_sizes.length - 3 }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Stock Info -->
            <div class="text-sm text-gray-600 mb-3">
                <span v-if="product.total_stock > 0" class="text-success">
                    {{ product.total_stock }} in stock
                </span>
                <span v-else class="text-destructive">Out of stock</span>
            </div>

            <!-- Add to Cart Button -->
            <button
                @click="addToCart"
                :disabled="product.total_stock === 0 || isLoading"
                class="w-full bg-primary text-white py-2 px-4 rounded-md hover:bg-primary/90 disabled:bg-gray-300 disabled:cursor-not-allowed transition-colors flex items-center justify-center"
            >
                <ShoppingCart class="h-4 w-4 mr-2" />
                <span v-if="isLoading">Adding...</span>
                <span v-else-if="product.has_variants">Choose Options</span>
                <span v-else-if="product.total_stock === 0">Out of Stock</span>
                <span v-else>Add to Cart</span>
            </button>
        </div>
    </div>

    <!-- List View -->
    <div
        v-else
        class="bg-white rounded-lg shadow-sm border hover:shadow-md transition-shadow cursor-pointer group flex"
        @click="goToProduct"
    >
        <!-- Product Image -->
        <div class="w-32 h-32 flex-shrink-0 overflow-hidden rounded-l-lg bg-gray-100">
            <img
                v-if="product.image"
                :src="product.image"
                :alt="product.name"
                class="w-full h-full object-cover"
            />
            <div v-else class="w-full h-full flex items-center justify-center">
                <Package class="h-8 w-8 text-gray-400" />
            </div>
        </div>

        <div class="flex-1 p-4 flex justify-between">
            <div class="flex-1">
                <h3 class="font-medium text-gray-900 mb-1">{{ product.name }}</h3>
                <p class="text-sm text-gray-600 mb-2">{{ product.category?.name }}</p>

                <!-- Variants Info -->
                <div v-if="product.has_variants" class="text-sm text-gray-600 mb-2">
                    <span class="font-medium">{{ product.variants_count }} variants available</span>
                    <div class="flex items-center space-x-4 mt-1">
                        <div v-if="product.available_colors && product.available_colors.length > 0" class="flex items-center space-x-1">
                            <span>Colors:</span>
                            <div class="flex space-x-1">
                                <div
                                    v-for="color in product.available_colors.slice(0, 3)"
                                    :key="color.id"
                                    :style="{ backgroundColor: color.hex_code }"
                                    class="w-3 h-3 rounded-full border"
                                />
                            </div>
                        </div>
                        <div v-if="product.available_sizes && product.available_sizes.length > 0">
                            <span>Sizes: {{ product.available_sizes.map(s => s.name).slice(0, 3).join(', ') }}</span>
                        </div>
                    </div>
                </div>

                <div class="text-sm text-gray-600">
                    <span v-if="product.total_stock > 0">{{ product.total_stock }} in stock</span>
                    <span v-else class="text-red-600">Out of stock</span>
                </div>
            </div>

            <div class="flex flex-col items-end justify-between">
                <span class="text-lg font-bold text-primary">{{ priceDisplay }}</span>

                <button
                    @click="addToCart"
                    :disabled="product.total_stock === 0 || isLoading"
                    class="bg-primary text-white py-2 px-4 rounded-md hover:bg-primary/90 disabled:bg-gray-300 disabled:cursor-not-allowed transition-colors flex items-center"
                >
                    <ShoppingCart class="h-4 w-4 mr-2" />
                    <span v-if="isLoading">Adding...</span>
                    <span v-else-if="product.has_variants">Choose Options</span>
                    <span v-else-if="product.total_stock === 0">Out of Stock</span>
                    <span v-else>Add to Cart</span>
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
