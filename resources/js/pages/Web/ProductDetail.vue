<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import Navbar from '@/components/Navbar.vue';
import Breadcrumb from '@/components/common/Breadcrumb.vue';
import BackButton from '@/components/common/BackButton.vue';
import SuccessMessage from '@/components/common/SuccessMessage.vue';
import ProductDetails from '@/components/product/ProductDetails.vue';
import ProductDescription from '@/components/product/ProductDescription.vue';
import RelatedProducts from '@/components/product/RelatedProducts.vue';
import { useI18n } from 'vue-i18n';
import { computed } from 'vue';
import { useLocale } from '@/composables/useLocale';

const { t } = useI18n();
const { localizedUrl } = useLocale();

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
const breadcrumbItems = computed(() => [
    { label: t('nav.home'), href: localizedUrl('/') },
    { label: t('nav.products'), href: localizedUrl('/products') },
    {
        label: product.category?.name || 'Uncategorized',
        href: localizedUrl(`/products?category=${product.category?.slug}`)
    },
    { label: product.name, isActive: true }
]);
</script>

<template>
    <div class="min-h-screen bg-background text-foreground">
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
            <BackButton :href="localizedUrl('/products')" :label="t('common.back')" />

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
                :title="t('home.featuredProducts')"
                :maxItems="4"
            />

        </div>
    </div>
</template>
