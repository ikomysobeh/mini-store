<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    Home,
    Package,
    ShoppingCart,
    Users,
    Settings,
    BarChart3,
    Heart,
    X,
    ChevronDown
} from 'lucide-vue-next';
import { ref, computed } from 'vue';

interface Props {
    isOpen: boolean;
}

interface Emits {
    (e: 'close'): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

// Get current route
const currentRoute = computed(() => {
    return window.location.pathname;
});

// Sidebar navigation items
const navigationItems = [
    {
        name: 'Dashboard',
        href: '/admin/dashboard',
        icon: Home,
        current: computed(() => currentRoute.value === '/admin/dashboard')
    },
    {
        name: 'Orders',
        href: '/admin/orders',
        icon: ShoppingCart,
        current: computed(() => currentRoute.value.startsWith('/admin/orders')),
        children: [
            { name: 'All Orders', href: '/admin/orders' },
            { name: 'Donations', href: '/admin/orders?type=donation' },
            { name: 'Purchases', href: '/admin/orders?type=purchase' }
        ]
    },
    {
        name: 'Products',
        href: '/admin/products',
        icon: Package,
        current: computed(() => currentRoute.value.startsWith('/admin/products')),
        children: [
            { name: 'All Products', href: '/admin/products' },
            { name: 'Add Product', href: '/admin/products/create' },
            { name: 'Categories', href: '/admin/categories' }
        ]
    },
    {
        name: 'Users',
        href: '/admin/users',
        icon: Users,
        current: computed(() => currentRoute.value.startsWith('/admin/users'))
    },
    {
        name: 'Analytics',
        href: '/admin/analytics',
        icon: BarChart3,
        current: computed(() => currentRoute.value.startsWith('/admin/analytics'))
    },
    {
        name: 'Settings',
        href: '/admin/settings',
        icon: Settings,
        current: computed(() => currentRoute.value.startsWith('/admin/settings'))
    }
];

// Expandable menu state
const expandedMenus = ref(new Set());

const toggleMenu = (itemName: string) => {
    if (expandedMenus.value.has(itemName)) {
        expandedMenus.value.delete(itemName);
    } else {
        expandedMenus.value.add(itemName);
    }
};

const isMenuExpanded = (itemName: string) => {
    return expandedMenus.value.has(itemName);
};
</script>

<template>
    <!-- Sidebar -->
    <div :class="[
        'fixed inset-y-0 left-0 z-50 w-64 bg-background shadow-lg transform transition-transform duration-300 ease-in-out lg:translate-x-0 lg:static lg:inset-0',
        isOpen ? 'translate-x-0' : '-translate-x-full'
    ]">
        <div class="flex flex-col h-full">

            <!-- Logo/Brand -->
            <div class="flex items-center justify-between h-16 px-4 border-b">
                <div class="flex items-center space-x-3">
                    <div class="w-8 h-8 bg-primary rounded-lg flex items-center justify-center">
                        <Heart class="h-5 w-5 text-primary-foreground" />
                    </div>
                    <h1 class="text-xl font-bold text-foreground">Admin Panel</h1>
                </div>

                <!-- Close button (mobile only) -->
                <button
                    @click="emit('close')"
                    class="lg:hidden p-2 rounded-md text-muted-foreground hover:text-foreground hover:bg-accent/50"
                >
                    <X class="h-5 w-5" />
                </button>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 px-4 py-4 space-y-2 overflow-y-auto">
                <div v-for="item in navigationItems" :key="item.name">

                    <!-- Main navigation item -->
                    <div v-if="!item.children">
                        <Link
                            :href="item.href"
                            :class="[
                                'group flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors',
                                item.current.value
                                    ? 'bg-primary/10 text-primary'
                                    : 'text-muted-foreground hover:text-foreground hover:bg-accent/50'
                            ]"
                            @click="emit('close')"
                        >
                            <component
                                :is="item.icon"
                                :class="[
                                    'mr-3 h-5 w-5',
                                    item.current.value ? 'text-primary' : 'text-muted-foreground group-hover:text-foreground'
                                ]"
                            />
                            {{ item.name }}
                        </Link>
                    </div>

                    <!-- Expandable navigation item -->
                    <div v-else>
                        <button
                            @click="toggleMenu(item.name)"
                            :class="[
                                'group w-full flex items-center justify-between px-3 py-2 text-sm font-medium rounded-md transition-colors',
                                item.current.value
                                    ? 'bg-primary/10 text-primary'
                                    : 'text-muted-foreground hover:text-foreground hover:bg-accent/50'
                            ]"
                        >
                            <div class="flex items-center">
                                <component
                                    :is="item.icon"
                                    :class="[
                                        'mr-3 h-5 w-5',
                                        item.current.value ? 'text-blue-500' : 'text-gray-400 group-hover:text-gray-500'
                                    ]"
                                />
                                {{ item.name }}
                            </div>
                            <ChevronDown
                                :class="[
                                    'h-4 w-4 transition-transform',
                                    isMenuExpanded(item.name) ? 'transform rotate-180' : ''
                                ]"
                            />
                        </button>

                        <!-- Submenu -->
                        <div v-if="isMenuExpanded(item.name)" class="mt-1 ml-8 space-y-1">
                            <Link
                                v-for="subItem in item.children"
                                :key="subItem.name"
                                :href="subItem.href"
                                class="block px-3 py-2 text-sm text-muted-foreground hover:text-foreground hover:bg-accent/50 rounded-md transition-colors"
                                @click="emit('close')"
                            >
                                {{ subItem.name }}
                            </Link>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- User info -->
            <div class="p-4 border-t border-gray-200">
                <div class="flex items-center space-x-3">
                    <div class="w-8 h-8 bg-muted rounded-full flex items-center justify-center">
                        <Users class="h-4 w-4 text-muted-foreground" />
                    </div>
                    <div>
                        <p class="text-sm font-medium text-foreground">Admin User</p>
                        <p class="text-xs text-muted-foreground">Administrator</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
