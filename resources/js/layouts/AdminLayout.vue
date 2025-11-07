<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import {
    Menu,
    Home,
    Package,
    ShoppingCart,
    Users,
    Settings,
    X,
    Bell,
    Eye,
    LogOut,
    Search,
    ChevronRight,
    Heart,
    BarChart3,
    User,
    ChevronDown,
    UserCheck,
    Shield,
    HelpCircle
} from 'lucide-vue-next';
import { ref, computed, onMounted, onUnmounted } from 'vue';

// Import NotificationBell component
import NotificationBell from '@/components/admin/NotificationBell.vue';

interface BreadcrumbItem {
    label: string;
    href?: string;
    isActive?: boolean;
}

interface Props {
    title?: string;
    breadcrumbs?: BreadcrumbItem[];
}

const props = withDefaults(defineProps<Props>(), {
    title: 'Admin Dashboard',
    breadcrumbs: () => []
});

// State for mobile sidebar and dropdown
const sidebarOpen = ref(false);
const userDropdownOpen = ref(false);

const navigationItems = [
    { name: 'Dashboard', href: '/admin/', icon: Home },
    { name: 'Orders', href: '/admin/orders', icon: ShoppingCart },
    { name: 'Products', href: '/admin/products', icon: Package },
    { name: 'Users', href: '/admin/users', icon: Users },
    { name: 'Category', href: '/admin/categories', icon: BarChart3 },
    { name: 'Notifications', href: '/admin/notifications', icon: Bell },
    { name: 'Settings', href: '/admin/settings', icon: Settings }
];

const currentPath = computed(() => window.location.pathname);

// FIXED: Proper active state logic
const isActive = (href: string) => {
    const current = currentPath.value;

    // Special case for dashboard - exact match only
    if (href === '/admin/') {
        return current === '/admin/' || current === '/admin';
    }

    // For other routes, check if current path starts with the href
    return current.startsWith(href);
};

// Logout function
const logout = () => {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '/logout';

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (csrfToken) {
        const tokenInput = document.createElement('input');
        tokenInput.type = 'hidden';
        tokenInput.name = '_token';
        tokenInput.value = csrfToken;
        form.appendChild(tokenInput);
    }

    document.body.appendChild(form);
    form.submit();
};

// Toggle dropdown function
const toggleUserDropdown = () => {
    userDropdownOpen.value = !userDropdownOpen.value;
};

// Close dropdown when clicking outside
const closeUserDropdown = (event) => {
    const dropdown = document.querySelector('.user-dropdown-container');
    if (dropdown && !dropdown.contains(event.target)) {
        userDropdownOpen.value = false;
    }
};

// Mock admin user data (you can pass this as props or get from auth)
const adminUser = {
    name: 'Admin User',
    email: 'admin',
    role: 'Administrator',
    avatar: null // You can add avatar URL here
};

// Lifecycle hooks
onMounted(() => {
    document.addEventListener('click', closeUserDropdown);
});

onUnmounted(() => {
    document.removeEventListener('click', closeUserDropdown);
});
</script>

<template>
    <div class="flex h-screen bg-background">
        <Head :title="title" />

        <!-- Mobile sidebar overlay -->
        <div
            v-if="sidebarOpen"
            class="fixed inset-0 z-40 lg:hidden"
            @click="sidebarOpen = false"
        >
            <div class="absolute inset-0 bg-black/50"></div>
        </div>

        <!-- Sidebar -->
        <div :class="[
            'fixed inset-y-0 left-0 z-50 w-64 bg-background border-r shadow-lg transform transition-transform duration-300 ease-in-out lg:translate-x-0 lg:static lg:inset-0',
            sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'
        ]">
            <div class="flex flex-col h-full">

                <!-- Logo -->
                <div class="flex items-center justify-between h-16 px-6 border-b">
                    <div class="flex items-center space-x-3">
                        <div class="w-8 h-8 bg-primary rounded-lg flex items-center justify-center">
                            <Heart class="h-5 w-5 text-primary-foreground" />
                        </div>
                        <h1 class="text-xl font-bold">Admin Panel</h1>
                    </div>
                    <Button
                        @click="sidebarOpen = false"
                        variant="ghost"
                        size="icon"
                        class="lg:hidden"
                    >
                        <X class="h-5 w-5" />
                    </Button>
                </div>

                <!-- Navigation -->
                <nav class="flex-1 px-4 py-6 space-y-1 overflow-y-auto">
                    <a
                        v-for="item in navigationItems"
                        :key="item.name"
                        :href="item.href"
                        :class="[
                            'group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-all duration-200',
                            isActive(item.href)
                                ? 'bg-primary text-primary-foreground shadow-sm'
                                : 'text-muted-foreground hover:text-foreground hover:bg-accent'
                        ]"
                    >
                        <component
                            :is="item.icon"
                            :class="[
                                'mr-3 h-5 w-5',
                                isActive(item.href) ? 'text-primary-foreground' : 'text-muted-foreground group-hover:text-foreground'
                            ]"
                        />
                        {{ item.name }}
                    </a>
                </nav>

                <Separator />

                <!-- ENHANCED: User Section with Dropdown Trigger -->
                <div class="p-4 bg-muted/30">
                    <button
                        @click="toggleUserDropdown"
                        class="w-full flex items-center justify-between px-3 py-2 rounded-lg hover:bg-muted/50 transition-colors"
                    >
                        <div class="flex items-center space-x-3">
                            <div class="w-8 h-8 bg-primary/10 rounded-full flex items-center justify-center">
                                <User class="h-4 w-4 text-primary" />
                            </div>
                            <div class="text-left">
                                <p class="text-sm font-medium">{{ adminUser.name }}</p>
                                <p class="text-xs text-muted-foreground">{{ adminUser.role }}</p>
                            </div>
                        </div>
                        <ChevronDown :class="[
                            'h-4 w-4 transition-transform duration-200',
                            userDropdownOpen ? 'rotate-180' : ''
                        ]" />
                    </button>
                </div>
            </div>
        </div>

        <!-- Main content area -->
        <div class="flex flex-col flex-1 overflow-hidden">

            <!-- Header -->
            <header class="bg-background border-b shadow-sm">
                <div class="px-4 sm:px-6 lg:px-8">
                    <div class="flex items-center justify-between h-16">

                        <!-- Left side -->
                        <div class="flex items-center space-x-4">
                            <Button
                                @click="sidebarOpen = true"
                                variant="ghost"
                                size="icon"
                                class="lg:hidden"
                            >
                                <Menu class="h-5 w-5" />
                            </Button>
                            <h2 class="text-xl font-semibold">{{ title }}</h2>
                        </div>

                        <!-- Search (middle) -->
                        <div class="hidden md:block flex-1 max-w-md mx-8">
                            <div class="relative">
                                <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                                <Input
                                    type="search"
                                    placeholder="Search orders, products, users..."
                                    class="pl-9 w-full"
                                />
                            </div>
                        </div>

                        <!-- ENHANCED: Right side with dropdown -->
                        <div class="flex items-center space-x-2">
                            <!-- Notification Bell -->
                            <NotificationBell />

                            <!-- View Site -->
                            <Button variant="outline" size="sm" as="a" href="/" target="_blank">
                                <Eye class="h-4 w-4 mr-2" />
                                View Site
                            </Button>

                            <!-- ENHANCED: User Dropdown -->
                            <div class="relative user-dropdown-container">
                                <Button
                                    @click="toggleUserDropdown"
                                    variant="ghost"
                                    size="sm"
                                    class="flex items-center space-x-2 px-3"
                                >
                                    <div class="w-6 h-6 bg-primary/10 rounded-full flex items-center justify-center">
                                        <User class="h-3 w-3 text-primary" />
                                    </div>
                                    <span class="hidden sm:block text-sm font-medium">{{ adminUser.name }}</span>
                                    <ChevronDown :class="[
                                        'h-4 w-4 transition-transform duration-200',
                                        userDropdownOpen ? 'rotate-180' : ''
                                    ]" />
                                </Button>

                                <!-- Dropdown Menu -->
                                <div
                                    v-if="userDropdownOpen"
                                    class="absolute right-0 mt-2 w-56 bg-background rounded-lg shadow-lg border border-border z-50"
                                >
                                    <!-- User Info -->
                                    <div class="px-4 py-3 border-b border-border">
                                        <div class="flex items-center space-x-3">
                                            <div class="w-10 h-10 bg-primary/10 rounded-full flex items-center justify-center">
                                                <User class="h-5 w-5 text-primary" />
                                            </div>
                                            <div>
                                                <p class="text-sm font-medium">{{ adminUser.name }}</p>
                                                <p class="text-xs text-muted-foreground">{{ adminUser.email }}</p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Menu Items -->
                                    <div class="py-2">
                                        <a
                                            href="/admin/settings"
                                            class="flex items-center px-4 py-2 text-sm hover:bg-muted transition-colors"
                                        >
                                            <Settings class="h-4 w-4 mr-3 text-muted-foreground" />
                                            Settings
                                        </a>
                                        <a
                                            href="/"
                                            target="_blank"
                                            class="flex items-center px-4 py-2 text-sm hover:bg-muted transition-colors"
                                        >
                                            <Eye class="h-4 w-4 mr-3 text-muted-foreground" />
                                            View Website
                                        </a>

                                        <Separator class="my-2" />

                                        <!-- Logout -->
                                        <button
                                            @click="logout"
                                            class="w-full flex items-center px-4 py-2 text-sm text-destructive hover:bg-destructive/10 transition-colors"
                                        >
                                            <LogOut class="h-4 w-4 mr-3" />
                                            Sign Out
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Breadcrumbs -->
            <div v-if="breadcrumbs.length > 0" class="bg-background border-b px-4 sm:px-6 lg:px-8 py-3">
                <nav class="flex items-center space-x-1 text-sm text-muted-foreground">
                    <a href="/admin/dashboard" class="hover:text-foreground transition-colors">
                        <Home class="h-4 w-4" />
                    </a>
                    <span v-for="(item, index) in breadcrumbs" :key="index" class="flex items-center">
                        <ChevronRight class="h-4 w-4 mx-2" />
                        <a
                            v-if="item.href && !item.isActive"
                            :href="item.href"
                            class="hover:text-foreground transition-colors"
                        >
                            {{ item.label }}
                        </a>
                        <span v-else class="text-foreground font-medium">{{ item.label }}</span>
                    </span>
                </nav>
            </div>

            <!-- Main content -->
            <main class="flex-1 overflow-y-auto bg-muted/20">
                <div class="p-6">
                    <slot />
                </div>
            </main>
        </div>
    </div>
</template>
