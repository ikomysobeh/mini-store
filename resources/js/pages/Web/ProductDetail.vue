<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import Navbar from '@/components/Navbar.vue';
import Breadcrumb from '@/components/common/Breadcrumb.vue';
import BackButton from '@/components/common/BackButton.vue';
import SuccessMessage from '@/components/common/SuccessMessage.vue';
import ProductDetails from '@/components/product/ProductDetails.vue';
import ProductDescription from '@/components/product/ProductDescription.vue';
import RelatedProducts from '@/components/product/RelatedProducts.vue';

const { product, relatedProducts, categories, auth, cartItems, settings } = defineProps({
    product: { type: Object, required: true },
    relatedProducts: { type: Array, default: () => [] },
    categories: { type: Array, default: () => [] },
    auth: { type: Object, default: () => ({}) },
    cartItems: { type: Array, default: () => [] },
    settings: { type: Object, default: () => ({}) },
});

const siteName = settings.site_name || 'Elegant Store';
const user = auth.user;

// Breadcrumb items
const breadcrumbItems = [
    { label: 'Home', href: '/' },
    { label: 'Products', href: '/products' },
    {
        label: product.category?.name || 'Uncategorized',
        href: `/products?category=${product.category?.slug}`
    },
    { label: product.name, isActive: true }
];
</script>

<template>
    <div class="min-h-screen">
        <Head :title="product.name" />

        <!-- Navbar -->
        <Navbar
            :categories="categories"
            :cartItems="cartItems"
            :user="user"
            :siteName="siteName"
            :settings="settings"
        />

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

            <!-- Breadcrumb Component -->
            <Breadcrumb :items="breadcrumbItems" />

            <!-- Back Button Component -->
            <BackButton href="/products" label="Back to Products" />

            <!-- Success Message Component -->
            <SuccessMessage icon="cart" :dismissible="true" />

            <!-- Main Product Section Component -->
            <ProductDetails :product="product" :user="user" />

            <!-- Product Description Component -->
            <ProductDescription
                :description="product.description"
                :specifications="product.specifications"
                :features="product.features"
            />

            <!-- Related Products Component -->
            <RelatedProducts
                :products="relatedProducts"
                title="You might also like"
                :maxItems="4"
            />

        </div>
    </div>
</template>
