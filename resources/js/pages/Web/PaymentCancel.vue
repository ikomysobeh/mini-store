<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import Navbar from '@/components/Navbar.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import {
    XCircle,
    ShoppingCart,
    Home,
    RefreshCw
} from 'lucide-vue-next';

// Props for navbar data
const { categories, cartItems, auth, settings } = defineProps({
    categories: { type: Array, default: () => [] },
    cartItems: { type: Array, default: () => [] },
    auth: { type: Object, default: () => ({}) },
    settings: { type: Object, default: () => ({}) },
});

const siteName = settings.site_name || 'Elegant Store';
const user = auth.user;

// Navigation functions
const goToCart = () => {
    router.visit('/cart');
};

const goToCheckout = () => {
    router.visit('/checkout');
};

const goHome = () => {
    router.visit('/');
};
</script>

<template>
    <div class="min-h-screen bg-background">
        <Head title="Payment Cancelled" />

        <!-- Navbar -->
        <Navbar
            :categories="categories"
            :cartItems="cartItems"
            :user="user"
            :siteName="siteName"
            :settings="settings"
        />

        <div class="max-w-2xl mx-auto px-4 py-16">
            <Card class="text-center">
                <CardContent class="py-12">

                    <!-- Simple Icon -->
                    <div class="w-16 h-16 bg-orange-100 rounded-full flex items-center justify-center mx-auto mb-6">
                        <XCircle class="h-8 w-8 text-orange-600" />
                    </div>

                    <!-- Simple Message -->
                    <h1 class="text-2xl font-semibold text-foreground mb-3">
                        Payment Cancelled
                    </h1>

                    <p class="text-muted-foreground mb-8">
                        No worries! Your items are still in your cart and no payment was made.
                    </p>

                    <!-- Simple Action Buttons -->
                    <div class="space-y-3">
                        <Button
                            @click="goToCheckout"
                            size="lg"
                            class="w-full sm:w-auto"
                        >
                            <RefreshCw class="h-4 w-4 mr-2" />
                            Try Again
                        </Button>

                        <div class="flex flex-col sm:flex-row gap-2 justify-center">
                            <Button
                                @click="goToCart"
                                variant="outline"
                                class="sm:w-auto"
                            >
                                <ShoppingCart class="h-4 w-4 mr-2" />
                                View Cart
                            </Button>

                            <Button
                                @click="goHome"
                                variant="ghost"
                                class="sm:w-auto"
                            >
                                <Home class="h-4 w-4 mr-2" />
                                Continue Shopping
                            </Button>
                        </div>
                    </div>

                </CardContent>
            </Card>
        </div>
    </div>
</template>
