<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { ShoppingCart, ArrowRight, Heart, Star, Gift } from 'lucide-vue-next';

interface Props {
    className?: string;
    showDonationOption?: boolean;
}

const { className = '', showDonationOption = false } = defineProps<Props>();
</script>

<template>
    <div :class="`text-center py-20 ${className}`">
        <!-- Empty Cart Icon -->
        <div class="w-32 h-32 bg-gradient-to-br from-muted to-muted/60 rounded-full flex items-center justify-center mx-auto mb-8 shadow-inner">
            <ShoppingCart class="h-16 w-16 text-muted-foreground" />
        </div>

        <!-- Main Message -->
        <h2 class="text-3xl font-bold mb-3">Your cart is empty</h2>
        <p class="text-muted-foreground mb-8 max-w-md mx-auto">
            Add some items to your cart to proceed with checkout.
            Browse our collection of amazing products!
        </p>

        <!-- Action Buttons -->
        <div class="flex flex-col sm:flex-row gap-4 justify-center mb-12">
            <Button as="a" href="/products" size="lg" class="min-w-48">
                <ShoppingCart class="h-5 w-5 mr-2" />
                Start Shopping
            </Button>
            <Button variant="outline" as="a" href="/products?featured=true" size="lg">
                <Star class="h-5 w-5 mr-2" />
                View Featured Products
            </Button>
            <Button
                v-if="showDonationOption"
                variant="outline"
                as="a"
                href="/products?type=donation"
                size="lg"
                class="text-warning hover:bg-warning/10"
            >
                <Gift class="h-5 w-5 mr-2" />
                Make a Donation
            </Button>
        </div>

        <!-- Quick Info Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 max-w-3xl mx-auto">
            <div class="text-center p-6 bg-primary/10 rounded-xl">
                <div class="w-12 h-12 bg-primary/20 rounded-full flex items-center justify-center mx-auto mb-4">
                    <ShoppingCart class="h-6 w-6 text-primary" />
                </div>
                <h3 class="font-semibold mb-2">Browse Products</h3>
                <p class="text-sm text-muted-foreground">Discover our wide range of quality products and amazing deals</p>
            </div>

            <div class="text-center p-6 bg-destructive/10 rounded-xl">
                <div class="w-12 h-12 bg-destructive/20 rounded-full flex items-center justify-center mx-auto mb-4">
                    <Heart class="h-6 w-6 text-destructive" />
                </div>
                <h3 class="font-semibold mb-2">Check Wishlist</h3>
                <p class="text-sm text-muted-foreground">Items you've saved for later are waiting for you</p>
            </div>

            <div class="text-center p-6 bg-secondary/10 rounded-xl">
                <div class="w-12 h-12 bg-secondary/20 rounded-full flex items-center justify-center mx-auto mb-4">
                    <Star class="h-6 w-6 text-secondary" />
                </div>
                <h3 class="font-semibold mb-2">Featured Items</h3>
                <p class="text-sm text-muted-foreground">Our handpicked recommendations just for you</p>
            </div>
        </div>

        <!-- Additional Help -->
        <div class="mt-12 p-6 bg-muted/30 rounded-xl max-w-md mx-auto">
            <h4 class="font-semibold mb-2">Need Help?</h4>
            <p class="text-sm text-muted-foreground mb-4">
                Contact our customer support for assistance with your order
            </p>
            <div class="flex flex-col sm:flex-row gap-2 justify-center">
                <Button variant="outline" size="sm" as="a" href="/contact">
                    Contact Support
                </Button>
                <Button variant="outline" size="sm" as="a" href="/help">
                    View FAQ
                </Button>
            </div>
        </div>
    </div>
</template>
