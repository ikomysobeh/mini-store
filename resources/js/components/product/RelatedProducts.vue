<script setup lang="ts">
import { Card, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { ShoppingCart, Heart } from 'lucide-vue-next';

interface Product {
    id: number;
    name: string;
    slug: string;
    price: number;
    original_price?: number;
    image?: string;
    stock: number;
    is_donatable?: boolean;
    category?: {
        name: string;
        slug: string;
    };
}

interface Props {
    products: Product[];
    title?: string;
    maxItems?: number;
    className?: string;
}

const {
    products,
    title = 'Related Products',
    maxItems = 4,
    className = ''
} = defineProps<Props>();

// Helper functions
const formatPrice = (price: number) => {
    return parseFloat(price.toString()).toFixed(2);
};

const visibleProducts = products.slice(0, maxItems);
</script>

<template>
    <div v-if="products.length" :class="`space-y-6 ${className}`">

        <!-- Section Header -->
        <div class="flex items-center justify-between">
            <h2 class="text-2xl font-bold">{{ title }}</h2>
            <Button
                v-if="products.length > maxItems"
                variant="outline"
                size="sm"
                as="a"
                href="/products"
            >
                View All
            </Button>
        </div>

        <!-- Products Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <Card
                v-for="product in visibleProducts"
                :key="product.id"
                class="group overflow-hidden hover:shadow-xl hover:scale-[1.02] transition-all duration-500 cursor-pointer border-0 shadow-md"
            >
                <div class="relative overflow-hidden">
                    <img
                        :src="product.image || '/placeholder-product.jpg'"
                        :alt="product.name"
                        class="h-48 w-full object-cover group-hover:scale-110 transition-transform duration-700"
                        loading="lazy"
                    />

                    <!-- Badges -->
                    <div class="absolute top-3 left-3">
                        <Badge v-if="product.is_donatable" class="bg-warning text-warning-foreground">
                            Donation
                        </Badge>
                        <Badge v-else-if="product.stock < 10 && product.stock > 0" variant="destructive">
                            Only {{ product.stock }} left
                        </Badge>
                        <Badge v-else-if="product.stock === 0" variant="destructive">
                            Out of Stock
                        </Badge>
                        <Badge v-else-if="product.original_price && product.original_price > product.price" class="bg-destructive/80 text-destructive-foreground">
                            {{ Math.round(((product.original_price - product.price) / product.original_price) * 100) }}% OFF
                        </Badge>
                    </div>

                    <!-- Quick Actions -->
                    <div class="absolute top-3 right-3 opacity-0 group-hover:opacity-100 transition-all duration-300">
                        <Button
                            size="icon"
                            variant="secondary"
                            class="shadow-lg mb-2"
                            title="Add to wishlist"
                        >
                            <Heart class="h-4 w-4" />
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
                    <h3 class="font-semibold line-clamp-2 group-hover:text-primary transition-colors">
                        <a :href="`/products/${product.slug}`" class="hover:underline">
                            {{ product.name }}
                        </a>
                    </h3>

                    <Badge variant="outline" class="text-xs capitalize">
                        {{ product.category?.name || 'Uncategorized' }}
                    </Badge>

                    <div class="flex items-center justify-between">
                        <div class="flex flex-col">
                            <span class="text-lg font-bold text-primary">
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
                                as="a"
                                :href="`/products/${product.slug}`"
                                class="hover:scale-105 transition-all duration-300"
                            >
                                View
                            </Button>
                    
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- View More Button -->
        <div v-if="products.length > maxItems" class="text-center">
            <Button variant="outline" as="a" href="/products">
                View More Products ({{ products.length - maxItems }} more)
            </Button>
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
