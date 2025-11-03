<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import {
    ShoppingCart, Heart, Star, ArrowRight, Package, Gift
} from 'lucide-vue-next';
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

interface Props {
    featuredProducts: Product[];
    user?: any;
}

const { featuredProducts, user } = defineProps<Props>();

// State
const wishlistedItems = ref(new Set<number>());

// Helper functions
const formatPrice = (price: number) => {
    return parseFloat(price.toString()).toFixed(2);
};

const addToWishlist = (productId: number) => {
    if (!user) {
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
    router.post(`/cart/add/${productId}`, { quantity: 1 }, {
        preserveState: true,
        preserveScroll: true
    });
};
</script>

<template>
    <section id="featured" class="space-y-8">
        <div class="text-center">
            <h2 class="text-3xl font-bold mb-2">Featured Products</h2>
            <p class="text-muted-foreground">Handpicked items just for you</p>
        </div>

        <div v-if="featuredProducts.length" class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-6">
            <Card
                v-for="product in featuredProducts"
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

                    <!-- Action Buttons -->
                    <div class="absolute top-3 right-3 flex flex-col space-y-2 opacity-0 group-hover:opacity-100 transition-all duration-300">
                        <Button
                            size="icon"
                            variant="secondary"
                            class="hover:scale-110 shadow-lg"
                            @click.stop="addToWishlist(product.id)"
                        >
                            <Heart :class="{ 'fill-current text-red-500': wishlistedItems.has(product.id) }" class="h-4 w-4" />
                        </Button>
                        <Button
                            size="icon"
                            variant="secondary"
                            class="hover:scale-110 shadow-lg"
                            @click.stop="addToCart(product.id)"
                            v-if="product.stock > 0"
                        >
                            <ShoppingCart class="h-4 w-4" />
                        </Button>
                    </div>

                    <!-- Badges -->
                    <div class="absolute top-3 left-3 flex flex-col space-y-2">
                        <Badge v-if="product.is_donatable" class="bg-orange-500 text-white">
                            <Gift class="h-3 w-3 mr-1" />
                            Donation
                        </Badge>
                        <Badge v-if="product.is_featured" class="bg-blue-500 text-white">
                            <Star class="h-3 w-3 mr-1" />
                            Featured
                        </Badge>
                        <Badge v-if="product.stock < 10 && product.stock > 0" variant="destructive">
                            Only {{ product.stock }} left
                        </Badge>
                        <Badge v-if="product.original_price && product.original_price > product.price" class="bg-red-500 text-white">
                            {{ Math.round(((product.original_price - product.price) / product.original_price) * 100) }}% OFF
                        </Badge>
                    </div>
                </div>

                <CardContent class="p-4 space-y-3">
                    <div class="flex items-start justify-between">
                        <h3 class="font-semibold line-clamp-2 flex-1">{{ product.name }}</h3>
                    </div>

                    <Badge variant="secondary" class="text-xs capitalize">
                        {{ product.category?.name || 'Uncategorized' }}
                    </Badge>

                    <!-- Product Rating -->
                    <div class="flex items-center space-x-1" v-if="product.rating">
                        <div class="flex items-center">
                            <Star v-for="i in 5" :key="i"
                                  :class="i <= (product.rating || 0) ? 'fill-yellow-400 text-yellow-400' : 'text-gray-300'"
                                  class="h-3 w-3" />
                        </div>
                        <span class="text-xs text-muted-foreground">({{ product.reviews_count || 0 }})</span>
                    </div>

                    <div class="flex items-center justify-between pt-2">
                        <div class="flex flex-col">
                            <span class="text-2xl font-bold text-primary">
                                ${{ formatPrice(product.price) }}
                            </span>
                            <span v-if="product.original_price && product.original_price > product.price"
                                  class="text-sm text-muted-foreground line-through">
                                ${{ formatPrice(product.original_price) }}
                            </span>
                        </div>
                        <Button
                            size="sm"
                            variant="default"
                            class="hover:scale-105 transition-all duration-300"
                            as="a"
                            :href="`/products/${product.slug}`"
                        >
                            View Details
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Empty State -->
        <div v-else class="text-center py-16">
            <div class="w-24 h-24 bg-muted rounded-full flex items-center justify-center mx-auto mb-4">
                <Package class="h-12 w-12 text-muted-foreground" />
            </div>
            <h3 class="text-xl font-semibold mb-2">No Featured Products</h3>
            <p class="text-muted-foreground mb-6">Check back soon for amazing deals!</p>
            <Button variant="outline" as="a" href="/products">
                Browse All Products
            </Button>
        </div>

        <!-- View All Products Button -->
        <div class="text-center mt-8">
            <Button
                size="lg"
                variant="outline"
                class="transition-all duration-300 hover:scale-105"
                as="a"
                href="/products"
            >
                View All Products
                <ArrowRight class="h-4 w-4 ml-2" />
            </Button>
        </div>
    </section>
</template>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
