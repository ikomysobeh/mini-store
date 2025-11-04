<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import Navbar from '@/components/Navbar.vue';
import HeroSection from '@/components/home/HeroSection.vue';
import FeaturedProducts from '@/components/home/FeaturedProducts.vue';
import HomeSidebar from '@/components/home/HomeSidebar.vue';

const { featuredProducts, categories, settings, cartItems, auth } = defineProps({
    featuredProducts: { type: Array, required: true },
    categories: { type: Array, default: () => [] },
    settings: { type: Object, default: () => ({}) },
    cartItems: { type: Array, default: () => [] },
    auth: { type: Object, default: () => ({}) },
});

const siteName = settings.site_name || 'Elegant Store';
const heroTitle = settings.hero_title || 'Discover Amazing Products';
const heroSubtitle = settings.hero_subtitle || 'Premium quality items with modern design';
const user = auth.user;
</script>

<template>
    <div class="min-h-screen bg-background text-foreground">
        <Head :title="siteName" />

        <!-- Navbar -->
        <Navbar
            :categories="categories"
            :cartItems="cartItems"
            :user="user"
            :siteName="siteName"
            :settings="settings"
        />

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

            <!-- Success Message -->
            <div v-if="$page.props.flash?.success" class="mb-6">
                <div class="p-4 bg-accent text-accent-foreground border border-border rounded-lg">
                    <p class="text-sm font-medium">
                        {{ $page.props.flash.success }}
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">

                <!-- Main Content Area (3/4) -->
                <div class="lg:col-span-3 space-y-8">

                    <!-- Hero Section Component -->
                    <HeroSection
                        :siteName="siteName"
                        :heroTitle="heroTitle"
                        :heroSubtitle="heroSubtitle"
                    />

                    <!-- Featured Products Component -->
                    <FeaturedProducts
                        :featuredProducts="featuredProducts"
                        :user="user"
                    />

                </div>

                <!-- Sidebar Component (1/4) -->
                <div class="lg:col-span-1">
                    <HomeSidebar
                        :cartItems="cartItems"
                        :categories="categories"
                        :isProductPage="false"
                    />
                </div>
            </div>
        </div>
    </div>
</template>
