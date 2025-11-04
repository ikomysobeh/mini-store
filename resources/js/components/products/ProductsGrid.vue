<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Heart, Star, ShoppingCart, Search, Gift } from 'lucide-vue-next';
import { ref } from 'vue';

interface Product {
    id: number;
    name: string;
    slug: string;
    price: number;
    original_price?: number;
    image?: string;
    stock: number;
    is_donatable?: boolean;
    is_featured?: boolean;
    rating?: number;
    reviews_count?: number;
    category?: {
        name: string;
        slug: string;
    };
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface Props {
    products: Product[];
    paginationLinks?: PaginationLink[];
    hasActiveFilters: boolean;
    activeFilters: {
        search?: string;
        category?: string;
        donatable?: boolean;
    };
    categories: Array<{
        id: number;
        name: string;
        slug: string;
    }>;
    user?: any;
}

interface Emits {
    (e: 'clearFilters'): void;
    (e: 'goToPage', url: string): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

// State
const wishlistedItems = ref(new Set<number>());
const addingToCart = ref(new Set<number>());

// Helper functions
const formatPrice = (price: number) => {
    return parseFloat(price.toString()).toFixed(2);
};

const getCategoryName = (categorySlug: string) => {
    const category = props.categories.find(cat => cat.slug === categorySlug);
    return category ? category.name : categorySlug;
};

const addToWishlist = (productId: number) => {
    if (!props.user) {
        router.visit('/login');
        return;
    }

    if (wishlistedItems.value.has(productId)) {
        wishlistedItems.value.delete(productId);
    } else {
        wishlistedItems.value.add(productId);
    }

    // Send to backend
    router.post('/wishlist/toggle', { product_id: productId }, {
        preserveState: true,
        preserveScroll: true
    });
};

const addToCart = (productId: number) => {
    if (!props.user) {
        router.visit('/login');
        return;
    }

    addingToCart.value.add(productId);

    router.post(`/cart/add/${productId}`, { quantity: 1 }, {
        preserveState: true,
        preserveScroll: true,
        onFinish: () => {
            addingToCart.value.delete(productId);
        }
    });
};

const goToPage = (url: string) => {
    if (url) {
        emit('goToPage', url);
    }
};

const clearFilters = () => {
    emit('clearFilters');
};

const getDiscountPercentage = (original: number, current: number) => {
    return Math.round(((original - current) / original) * 100);
};
</script>

<template>
    <div class="space-y-6">

        <!-- Active Filters Display -->
        <div v-if="hasActiveFilters" class="flex flex-wrap gap-2">
            <Badge v-if="activeFilters.search" variant="secondary" class="px-3 py-1">
                <Search class="h-3 w-3 mr-1" />
                Search: {{ activeFilters.search }}
            </Badge>
            <Badge v-if="activeFilters.category" variant="secondary" class="px-3 py-1 capitalize">
                <span class="capitalize">{{ getCategoryName(activeFilters.category) }}</span>
            </Badge>
            <Badge v-if="activeFilters.donatable" variant="secondary" class="px-3 py-1">
                <Gift class="h-3 w-3 mr-1" />
                Donations Only
            </Badge>
            <Button variant="ghost" size="sm" @click="clearFilters" class="h-6 px-2 text-xs">
                Clear all
            </Button>
        </div>

        <!-- Products Grid -->
        <div v-if="products.length" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <Card
                v-for="product in products"
                :key="product.id"
                class="group overflow-hidden hover:shadow-xl hover:scale-[1.02] transition-all duration-500 cursor-pointer border-0 shadow-md"
            >
                <div class="relative overflow-hidden">
                    <img
                        :src="product.image || '/placeholder-product.jpg'"
                        :alt="product.name"
                        class="h-48 w-full object-full group-hover:scale-110 transition-transform duration-700"
                        loading="lazy"
                    />
                    <div class="absolute inset-0 bg-black/20 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>

                    <!-- Badges -->
                    <div class="absolute top-3 left-3 flex flex-col space-y-2">
                        <Badge v-if="product.is_donatable" class="bg-warning text-warning-foreground">
                            <Gift class="h-3 w-3 mr-1" />
                            Donation
                        </Badge>
                        <Badge v-if="product.is_featured" class="bg-info text-info-foreground">
                            <Star class="h-3 w-3 mr-1" />
                            Featured
                        </Badge>
                        <Badge v-if="product.stock < 10 && product.stock > 0" variant="destructive">
                            Only {{ product.stock }} left
                        </Badge>
                        <Badge v-if="product.stock === 0 && !product.is_donatable" variant="destructive">
                            Out of Stock
                        </Badge>
                        <Badge v-if="product.original_price && product.original_price > product.price" class="bg-destructive/80 text-destructive-foreground">
                            {{ getDiscountPercentage(product.original_price, product.price) }}% OFF
                        </Badge>
                    </div>

                    <!-- Action Buttons -->
                    <div class="absolute top-3 right-3 flex flex-col space-y-2 opacity-0 group-hover:opacity-100 transition-all duration-300">
                        <Button
                            size="icon"
                            variant="secondary"
                            class="hover:scale-110 shadow-lg"
                            @click.stop="addToWishlist(product.id)"
                            :title="wishlistedItems.has(product.id) ? 'Remove from wishlist' : 'Add to wishlist'"
                        >
                            <Heart :class="{ 'fill-current text-red-500': wishlistedItems.has(product.id) }" class="h-4 w-4" />
                        </Button>
                        <Button
                            v-if="product.stock > 0 || product.is_donatable"
                            size="icon"
                            variant="secondary"
                            class="hover:scale-110 shadow-lg"
                            @click.stop="addToCart(product.id)"
                            :disabled="addingToCart.has(product.id)"
                            title="Add to cart"
                        >
                            <ShoppingCart class="h-4 w-4" />
                        </Button>
                    </div>

                    <!-- Quick View Button -->
                    <div class="absolute bottom-3 left-1/2 transform -translate-x-1/2 opacity-0 group-hover:opacity-100 transition-all duration-300">
                        <Button
                            size="sm"
                            variant="secondary"
                            class="shadow-lg"
                            as="a"
                            :href="`/products/${product.slug}`"
                        >
                            Quick View
                        </Button>
                    </div>
                </div>

                <CardContent class="p-4 space-y-3">
                    <div class="flex items-start justify-between">
                        <h3 class="font-semibold line-clamp-2 group-hover:text-primary transition-colors flex-1">
                            <a :href="`/products/${product.slug}`" class="hover:underline">
                                {{ product.name }}
                            </a>
                        </h3>
                        <div class="flex items-center space-x-1 ml-2" v-if="product.rating">
                            <Star class="h-3 w-3 fill-warning text-warning" />
                            <span class="text-xs text-muted-foreground">{{ product.rating }}</span>
                        </div>
                    </div>

                    <Badge variant="outline" class="text-xs capitalize">
                        {{ product.category?.name || 'Uncategorized' }}
                    </Badge>

                    <!-- Stock Status -->
                    <div class="text-xs">
                        <span v-if="product.stock > 0" class="text-success font-medium">
                            {{ product.stock }} in stock
                        </span>
                        <span v-else-if="!product.is_donatable" class="text-destructive font-medium">
                            Out of stock
                        </span>
                        <span v-else class="text-info font-medium">
                            Available for donation
                        </span>
                    </div>

                    <!-- Price and Actions -->
                    <div class="flex items-center justify-between pt-2">
                        <div class="flex flex-col">
                            <span class="text-xl font-bold text-primary">
                                ${{ formatPrice(product.price) }}
                            </span>
                            <span v-if="product.original_price && product.original_price > product.price"
                                  class="text-sm text-muted-foreground line-through">
                                ${{ formatPrice(product.original_price) }}
                            </span>
                        </div>
                        <div class="flex space-x-2">
                            <Button
                                size="sm"
                                variant="outline"
                                class="hover:scale-105 transition-all duration-300"
                                as="a"
                                :href="`/products/${product.slug}`"
                            >
                                View
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- No Products -->
        <div v-else class="text-center py-16">
            <div class="w-24 h-24 bg-muted rounded-full flex items-center justify-center mx-auto mb-6">
                <Search class="h-12 w-12 text-muted-foreground" />
            </div>
            <h3 class="text-2xl font-semibold mb-2">No products found</h3>
            <p class="text-muted-foreground mb-6">
                {{ hasActiveFilters
                ? 'Try adjusting your search or filter criteria'
                : 'No products are available at the moment'
                }}
            </p>
            <div class="flex flex-col sm:flex-row gap-3 justify-center">
                <Button variant="outline" @click="clearFilters" v-if="hasActiveFilters">
                    Clear Filters
                </Button>
                <Button as="a" href="/products">
                    Browse All Products
                </Button>
            </div>
        </div>

        <!-- Pagination -->
        <div v-if="paginationLinks && paginationLinks.length > 3" class="flex justify-center mt-8">
            <div class="flex space-x-2">
                <Button
                    v-for="link in paginationLinks"
                    :key="link.label"
                    :variant="link.active ? 'default' : 'outline'"
                    :disabled="!link.url"
                    @click="goToPage(link.url)"
                    size="sm"
                    v-html="link.label"
                    class="min-w-10"
                />
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
