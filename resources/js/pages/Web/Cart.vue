<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import Navbar from '@/components/Navbar.vue';
import Breadcrumb from '@/components/common/Breadcrumb.vue';
import FlashMessages from '@/components/common/FlashMessages.vue';
import CartPageHeader from '@/components/cart/CartPageHeader.vue';
import EmptyCart from '@/components/cart/EmptyCart.vue';
import CartItemsList from '@/components/cart/CartItemsList.vue';
import OrderSummary from '@/components/cart/OrderSummary.vue';
import { ref, computed, watch } from 'vue';

const { cart, categories, auth, settings } = defineProps({
    cart: { type: Object, default: null },
    categories: { type: Array, default: () => [] },
    auth: { type: Object, default: () => ({}) },
    settings: { type: Object, default: () => ({}) },
});

const cartItems = computed(() => cart?.items || []);

const siteName = settings.site_name || 'Elegant Store';
const user = auth.user;

// State
const updatingItems = ref(new Set());
const quantities = ref({});

// Initialize quantities from cart items using watch
watch(cartItems, (items) => {
    items.forEach(item => {
        if (!quantities.value[item.id]) {
            quantities.value[item.id] = item.quantity;
        }
    });
}, { immediate: true });

// Computed properties
const subtotal = computed(() => {
    return cartItems.value.reduce((sum, item) => {
        const qty = quantities.value[item.id] || item.quantity;
        return sum + (parseFloat(item.price || 0) * qty);
    }, 0);
});

const shipping = computed(() => {
    return subtotal.value > 50 ? 0 : 9.99;
});

const total = computed(() => {
    return subtotal.value;
});

const isEmpty = computed(() => cartItems.value.length === 0);

// Breadcrumb items
const breadcrumbItems = [
    { label: 'Home', href: '/' },
    { label: 'Shopping Cart', isActive: true }
];

// Cart management functions
const updateQuantity = (itemId, newQuantity) => {
    if (newQuantity < 1) {
        removeItem(itemId);
        return;
    }

    if (newQuantity > 99) newQuantity = 99;

    quantities.value[itemId] = newQuantity;
    updatingItems.value.add(itemId);

    setTimeout(() => {
        router.patch(`/cart/update/${itemId}`, {
            quantity: newQuantity
        }, {
            preserveState: true,
            preserveScroll: true,
            onFinish: () => {
                updatingItems.value.delete(itemId);
            },
            onError: (errors) => {
                console.error('Failed to update quantity:', errors);
                quantities.value[itemId] = cartItems.value.find(item => item.id === itemId)?.quantity || 1;
                updatingItems.value.delete(itemId);
            }
        });
    }, 500);
};

const incrementQuantity = (itemId) => {
    const currentQty = quantities.value[itemId] || 1;
    updateQuantity(itemId, currentQty + 1);
};

const decrementQuantity = (itemId) => {
    const currentQty = quantities.value[itemId] || 1;
    updateQuantity(itemId, currentQty - 1);
};

const removeItem = (itemId) => {
    if (confirm('Remove this item from your cart?')) {
        router.delete(`/cart/remove/${itemId}`, {
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => {
                delete quantities.value[itemId];
            },
            onError: (errors) => {
                console.error('Failed to remove item:', errors);
            }
        });
    }
};

const clearCart = () => {
    if (confirm('Are you sure you want to clear your entire cart?')) {
        router.delete('/cart/clear', {
            preserveState: true,
            onSuccess: () => {
                quantities.value = {};
            },
            onError: (errors) => {
                console.error('Failed to clear cart:', errors);
            }
        });
    }
};

const proceedToCheckout = () => {
    if (user) {
        router.visit('/checkout');
    } else {
        router.visit('/login?redirect=checkout');
    }
};

const applyPromoCode = (code) => {
    router.post('/cart/apply-promo', {
        code: code
    }, {
        preserveState: true,
        preserveScroll: true
    });
};
</script>

<template>
    <div class="min-h-screen bg-background text-foreground">
        <Head title="Shopping Cart" />

        <Navbar
            :categories="categories"
            :cartItems="cartItems"
            :user="user"
            :siteName="siteName"
            :settings="settings"
        />

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

            <!-- Flash Messages Component -->
            <FlashMessages :dismissible="true" />

            <!-- Breadcrumb Component -->
            <div class="text-muted-foreground">
                <Breadcrumb :items="breadcrumbItems" />
            </div>

            <!-- Page Header Component -->
            <CartPageHeader :itemCount="cartItems.length" :isEmpty="isEmpty" />

            <!-- Empty Cart Component -->
            <EmptyCart v-if="isEmpty" />

            <!-- Cart Content -->
            <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-8">

                <!-- Cart Items List Component -->
                <div class="bg-card text-card-foreground p-6 rounded-lg border border-border lg:col-span-2">
                    <CartItemsList
                        :cartItems="cartItems"
                        :quantities="quantities"
                        :updatingItems="updatingItems"
                        @incrementQuantity="incrementQuantity"
                        @decrementQuantity="decrementQuantity"
                        @updateQuantity="updateQuantity"
                        @removeItem="removeItem"
                        @clearCart="clearCart"
                    />
                </div>

                <!-- Order Summary Component -->
                <div class="bg-card text-card-foreground p-6 rounded-lg border border-border">
                    <OrderSummary
                        :subtotal="subtotal"
                        :shipping="shipping"
                        :total="total"
                        :itemCount="cartItems.length"
                        :user="user"
                        :updatingItems="updatingItems"
                        @proceedToCheckout="proceedToCheckout"
                        @applyPromoCode="applyPromoCode"
                    />
                </div>
            </div>
        </div>
    </div>
</template>
