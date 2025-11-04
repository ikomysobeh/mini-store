<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import Navbar from '@/components/Navbar.vue';
import EmptyCartState from '@/components/checkout/EmptyCartState.vue';
import CheckoutContent from '@/components/checkout/CheckoutContent.vue';
import { computed } from 'vue';

const { cartItems, categories, auth, settings, shippingMethods, paymentMethods } = defineProps({
    cartItems: { type: Array, required: true },
    categories: { type: Array, default: () => [] },
    auth: { type: Object, default: () => ({}) },
    settings: { type: Object, default: () => ({}) },
    shippingMethods: { type: Array, default: () => [
            { id: 'standard', name: 'Standard Shipping', price: 0, days: '3-5 business days', description: 'Free on orders over $50' },
            { id: 'express', name: 'Express Shipping', price: 9.99, days: '1-2 business days', description: 'Fast delivery' }
        ] },
    paymentMethods: { type: Array, default: () => ['card', 'paypal', 'apple_pay'] }
});

const siteName = settings.site_name || 'Elegant Store';
const user = auth.user;

// Computed properties
const hasItems = computed(() => cartItems.length > 0);
const hasDonationItems = computed(() => cartItems.some(item => item.is_donatable));

// Event handlers
const removeItem = (itemId) => {
    router.delete(`/cart/remove/${itemId}`, {
        preserveState: true
    });
};

const processPayment = () => {
    // This will be handled by the CheckoutContent component
    // The form submission logic is already there
    console.log('Processing payment...');
};
</script>

<template>
    <div class="min-h-screen bg-background text-foreground">
        <Head title="Checkout" />

        <!-- Navbar -->
        <Navbar
            :categories="categories"
            :cartItems="cartItems"
            :user="user"
            :siteName="siteName"
            :settings="settings"
        />


        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

            <!-- Empty Cart State Component -->
            <EmptyCartState
                v-if="!hasItems"
                :showDonationOption="hasDonationItems"
            />

            <!-- Checkout Content Component -->
            <CheckoutContent
                v-else
                :cartItems="cartItems"
                :user="user"
                :shippingMethods="shippingMethods"
                :paymentMethods="paymentMethods"
                @removeItem="removeItem"
                @processPayment="processPayment"
            />

        </div>
    </div>
</template>

<style scoped>
/* Custom scrollbar for mobile */
.sticky::-webkit-scrollbar {
    width: 4px;
}

.sticky::-webkit-scrollbar-track {
    background: transparent;
}

.sticky::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 2px;
}
</style>
