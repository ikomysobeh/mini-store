<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import Navbar from '@/components/Navbar.vue';
import { UserSidebar } from '@/components/user/UserSidebar.vue';
import { Breadcrumbs } from '@/components/ui/breadcrumbs';
import { Toaster } from '@/components/ui/toaster';
import {
    User, ShoppingBag, Heart, Settings,
    CreditCard, MapPin, Bell, Shield, HelpCircle
} from 'lucide-vue-next';
import type { BreadcrumbItemType } from '@/types';

interface Props {
    title?: string;
    breadcrumbs?: BreadcrumbItemType[];
    user?: any;
    categories?: any[];
    cartItems?: any[];
    settings?: any;
}

const {
    title = 'My Account',
    breadcrumbs = [],
    user,
    categories = [],
    cartItems = [],
    settings = {}
} = defineProps<Props>();

const siteName = settings.site_name || 'Elegant Store';

// User navigation items
const userNavigationItems = [
    {
        title: 'Dashboard',
        href: '/dashboard',
        icon: User,
        isActive: (path: string) => path === '/dashboard'
    },
    {
        title: 'My Orders',
        href: '/user/orders',
        icon: ShoppingBag,
        isActive: (path: string) => path.startsWith('/user/orders')
    },
    {
        title: 'Wishlist',
        href: '/user/wishlist',
        icon: Heart,
        isActive: (path: string) => path.startsWith('/user/wishlist')
    },
    {
        title: 'Addresses',
        href: '/user/addresses',
        icon: MapPin,
        isActive: (path: string) => path.startsWith('/user/addresses')
    },
    {
        title: 'Payment Methods',
        href: '/user/payment-methods',
        icon: CreditCard,
        isActive: (path: string) => path.startsWith('/user/payment-methods')
    },
    {
        title: 'Notifications',
        href: '/user/notifications',
        icon: Bell,
        isActive: (path: string) => path.startsWith('/user/notifications')
    },
    {
        title: 'Account Settings',
        href: '/user/profile',
        icon: Settings,
        isActive: (path: string) => path.startsWith('/user/profile') || path.startsWith('/user/settings')
    },
    {
        title: 'Privacy & Security',
        href: '/user/security',
        icon: Shield,
        isActive: (path: string) => path.startsWith('/user/security')
    },
    {
        title: 'Help & Support',
        href: '/user/support',
        icon: HelpCircle,
        isActive: (path: string) => path.startsWith('/user/support')
    }
];
</script>

<template>
    <div class="min-h-screen bg-background">
        <Head :title="title" />

        <!-- Main Navbar -->
        <Navbar
            :categories="categories"
            :cartItems="cartItems"
            :user="user"
            :siteName="siteName"
        />

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

            <!-- Breadcrumbs -->
            <div v-if="breadcrumbs.length > 0" class="mb-6">
                <Breadcrumbs :items="breadcrumbs" />
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">

                <!-- User Sidebar -->
                <div class="lg:col-span-1">
                    <UserSidebar :navigation-items="userNavigationItems" :user="user" />
                </div>

                <!-- Main Content -->
                <div class="lg:col-span-3">
                    <slot />
                </div>
            </div>
        </div>

        <!-- Toast Notifications -->
        <Toaster />
    </div>
</template>
