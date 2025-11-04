<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import Navbar from '@/components/Navbar.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { CheckCircle, Package, Heart, ArrowRight, Home, Eye } from 'lucide-vue-next';

// Props for navbar data and order
const { order, categories, cartItems, auth, settings } = defineProps({
    order: { type: Object, required: true },
    categories: { type: Array, default: () => [] },
    cartItems: { type: Array, default: () => [] },
    auth: { type: Object, default: () => ({}) },
    settings: { type: Object, default: () => ({}) },
});

const siteName = settings.site_name || 'Elegant Store';
const user = auth.user;

const formatPrice = (price) => {
    return parseFloat(price).toFixed(2);
};

// Navigation functions
const goHome = () => {
    router.visit('/');
};

// FIXED: Single viewOrders function that navigates to /my-orders
const viewOrders = () => {
    router.get('/my-orders');
};

const contactUs = () => {
    router.visit('/contact');
};
</script>

<template>
    <div class="min-h-screen bg-background">
        <Head title="Payment Successful" />

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

                    <!-- Success Icon -->
                    <div class="w-20 h-20 bg-accent/20 rounded-full flex items-center justify-center mx-auto mb-6">
                        <CheckCircle class="h-12 w-12 text-accent-foreground" />
                    </div>

                    <!-- Success Message -->
                    <h1 class="text-3xl font-bold text-foreground mb-3">
                        {{ order.is_donation ? '🎉 Thank You!' : '✅ Payment Successful!' }}
                    </h1>

                    <p class="text-muted-foreground mb-8 text-lg">
                        {{ order.is_donation
                        ? 'Your generous donation means the world to us!'
                        : 'Your order has been confirmed and payment processed.'
                        }}
                    </p>

                    <!-- Order Summary Card -->
                    <Card class="bg-muted/50 mb-8">
                        <CardContent class="py-6">
                            <div class="flex items-center justify-center mb-4">
                                <component :is="order.is_donation ? Heart : Package" class="h-5 w-5 text-muted-foreground mr-2" />
                                <h2 class="font-semibold text-lg">
                                    {{ order.is_donation ? 'Donation' : 'Order' }} Summary
                                </h2>
                            </div>

                            <div class="space-y-3">
                                <div class="flex justify-between items-center">
                                    <span class="text-muted-foreground">{{ order.is_donation ? 'Donation' : 'Order' }} #</span>
                                    <Badge variant="secondary" class="font-mono">{{ order.id }}</Badge>
                                </div>

                                <div class="flex justify-between items-center">
                                    <span class="text-muted-foreground">Amount</span>
                                    <span class="text-2xl font-bold text-green-600">${{ formatPrice(order.total) }}</span>
                                </div>

                                <div class="flex justify-between items-center">
                                    <span class="text-muted-foreground">Status</span>
                                    <Badge class="bg-green-100 text-green-700">Paid</Badge>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Action Buttons -->
                    <div class="space-y-4">
                        <Button
                            @click="goHome"
                            size="lg"
                            class="w-full bg-primary hover:bg-primary/90"
                        >
                            <Home class="h-4 w-4 mr-2" />
                            Continue Shopping
                        </Button>

                        <div class="flex gap-3">
                            <Button
                                @click="viewOrders"
                                variant="outline"
                                class="flex-1"
                            >
                                <Eye class="h-4 w-4 mr-2" />
                                View {{ order.is_donation ? 'Donations' : 'Orders' }}
                            </Button>


                        </div>
                    </div>

                    <!-- Thank You Message -->
                    <div class="mt-8 pt-6 border-t">
                        <p class="text-muted-foreground">
                            {{ order.is_donation
                            ? '🙏 Your support helps us make a difference!'
                            : '🎉 Thank you for choosing us!'
                            }}
                        </p>
                    </div>

                </CardContent>
            </Card>
        </div>
    </div>
</template>
