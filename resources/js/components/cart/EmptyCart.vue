<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { ShoppingCart, ArrowRight, Heart, Star } from 'lucide-vue-next';

interface Props {
    className?: string;
}

const { className = '' } = defineProps<Props>();
</script>

<template>
    <div :class="`text-center py-16 ${className}`">
        <div class="w-32 h-32 bg-gradient-to-br from-muted to-muted/60 rounded-full flex items-center justify-center mx-auto mb-8 shadow-inner">
            <ShoppingCart class="h-16 w-16 text-muted-foreground" />
        </div>

        <h2 class="text-3xl font-bold mb-3">Your cart is empty</h2>
        <p class="text-muted-foreground mb-8 max-w-md mx-auto">
            Looks like you haven't added anything to your cart yet.
            Start exploring our amazing products!
        </p>

        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <Button as="a" href="/products" size="lg" class="min-w-48">
                <ShoppingCart class="h-5 w-5 mr-2" />
                Start Shopping
            </Button>
            <Button variant="outline" as="a" href="/products?featured=true" size="lg">
                <Star class="h-5 w-5 mr-2" />
                View Featured Products
            </Button>
        </div>

        <!-- Quick Actions -->
        <div class="mt-12 grid grid-cols-1 md:grid-cols-3 gap-6 max-w-2xl mx-auto">
            <div class="text-center p-4">
                <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-3">
                    <ShoppingCart class="h-6 w-6 text-blue-600" />
                </div>
                <h3 class="font-semibold mb-1">Browse Products</h3>
                <p class="text-sm text-muted-foreground">Discover our wide range of quality products</p>
            </div>

            <div class="text-center p-4">
                <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-3">
                    <Heart class="h-6 w-6 text-red-600" />
                </div>
                <h3 class="font-semibold mb-1">Check Wishlist</h3>
                <p class="text-sm text-muted-foreground">Items you've saved for later</p>
            </div>

            <div class="text-center p-4">
                <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-3">
                    <Star class="h-6 w-6 text-green-600" />
                </div>
                <h3 class="font-semibold mb-1">Featured Items</h3>
                <p class="text-sm text-muted-foreground">Our handpicked recommendations</p>
            </div>
        </div>
    </div>
</template>
