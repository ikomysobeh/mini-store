<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3'
import Navbar from '@/components/Navbar.vue'
import EmptyCartState from '@/components/checkout/EmptyCartState.vue'
import CheckoutContent from '@/components/checkout/CheckoutContent.vue'
import { computed } from 'vue'

// ENHANCED: Add existingCustomerData prop
const { cartItems, categories, auth, settings, shippingMethods, paymentMethods, existingCustomerData } = defineProps<{
    cartItems: Array<any>
    categories?: Array<any>
    auth?: Object
    settings?: Object
    shippingMethods?: Array<any>
    paymentMethods?: Array<any>
    existingCustomerData?: Object | null // NEW: Add this prop
}>()

const siteName = settings?.site_name || 'Elegant Store'
const user = auth?.user

// Computed properties
const hasItems = computed(() => cartItems.length > 0)
const hasDonationItems = computed(() => cartItems.some((item: any) => item.is_donatable))

// Event handlers
const removeItem = (itemId: number) => {
    router.delete(`/cart/remove/${itemId}`, {
        preserveState: true
    })
}

const processPayment = () => {
    // This will be handled by the CheckoutContent component
    // The form submission logic is already there
    console.log('Processing payment...')
}
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
                :existingCustomerData="existingCustomerData"
                @removeItem="removeItem"
                @processPayment="processPayment"
            />
        </div>
    </div>
</template>
