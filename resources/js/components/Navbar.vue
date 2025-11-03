<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import {
    ShoppingCart,
    Heart,
    Star,
    User,
    LogOut,
    Settings,
    Package,
    Menu,
    X,
    History, // For My Orders icon
    ClipboardList
} from 'lucide-vue-next';
import { ref, computed, onMounted, onUnmounted } from 'vue';

const { categories, cartItems, user, siteName, settings } = defineProps({
    categories: { type: Array, default: () => [] },
    cartItems: { type: Array, default: () => [] },
    user: { type: Object, default: null },
    siteName: { type: String, default: 'Elegant Store' },
    settings: { type: Object, default: () => ({}) },
});

// Mobile menu state
const mobileMenuOpen = ref(false);
// Simple dropdown state control
const dropdownOpen = ref(false);
const dropdownRef = ref(null);
const isLoggingOut = ref(false);

// Computed properties for safe user data access
const userAvatar = computed(() => user?.avatar || null);
const userName = computed(() => user?.name || 'User');
const userEmail = computed(() => user?.email || '');

// Compute logo URL
const logoUrl = computed(() => {
    return settings?.logo_url || null;
});

// Total cart items count
const cartItemsCount = computed(() => {
    return cartItems.reduce((total, item) => total + (parseInt(item.quantity) || 0), 0);
});

// Single color for all category buttons
const getCategoryButtonClass = () => {
    return 'text-primary hover:bg-primary/10 hover:text-primary transition-all duration-300';
};

// Close dropdown when clicking outside
const handleClickOutside = (event) => {
    if (dropdownRef.value && !dropdownRef.value.contains(event.target)) {
        dropdownOpen.value = false;
    }
};

// User functions
const logout = () => {
    if (isLoggingOut.value) return;

    dropdownOpen.value = false;
    mobileMenuOpen.value = false;
    isLoggingOut.value = true;

    router.post('/logout', {}, {
        onSuccess: () => {
            window.location.href = '/';
        },
        onError: (error) => {
            console.error('Logout failed:', error);
            isLoggingOut.value = false;
        },
        onFinish: () => {
            isLoggingOut.value = false;
        }
    });
};

const getUserInitials = (userData) => {
    if (userData?.name) {
        return userData.name.split(' ')
            .map(n => n[0])
            .join('')
            .toUpperCase()
            .substring(0, 2);
    }
    return userData?.email?.charAt(0).toUpperCase() || 'U';
};

const toggleMobileMenu = () => {
    mobileMenuOpen.value = !mobileMenuOpen.value;
    if (mobileMenuOpen.value) {
        dropdownOpen.value = false;
    }
};

const toggleDropdown = () => {
    dropdownOpen.value = !dropdownOpen.value;
    if (dropdownOpen.value) {
        mobileMenuOpen.value = false;
    }
};

// Handle dropdown navigation
const handleDropdownItemClick = (href) => {
    dropdownOpen.value = false;
    if (href) {
        router.visit(href);
    }
};

// Close mobile menu when navigating
const closeMobileMenu = () => {
    mobileMenuOpen.value = false;
};

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});
</script>

<template>
    <!-- Enhanced Navbar -->
    <nav class="sticky top-0 z-50 backdrop-blur-lg border-b bg-background/80">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <!-- Logo & Brand -->
                <a href="/" class="flex items-center space-x-3 group">
                    <!-- Logo Image or Fallback -->
                    <div v-if="logoUrl" class="w-10 h-10 rounded-xl overflow-hidden flex items-center justify-center bg-white border">
                        <img
                            :src="logoUrl"
                            :alt="siteName + ' Logo'"
                            class="w-full h-full object-contain"
                            @error="$event.target.style.display = 'none'"
                        />
                    </div>
                    <div v-else class="w-10 h-10 bg-primary rounded-xl flex items-center justify-center">
                        <span class="text-primary-foreground font-bold text-lg">{{ siteName.charAt(0) }}</span>
                    </div>
                    <span class="font-bold text-xl text-primary">{{ siteName }}</span>
                </a>

                <!-- Desktop Categories -->
                <div class="hidden lg:flex space-x-4">
                    <Button
                        v-for="category in categories"
                        :key="category.id"
                        variant="ghost"
                        :class="getCategoryButtonClass()"
                        as="a"
                        :href="`/products?category=${category.slug}`"
                    >
                        {{ category.name }}
                    </Button>
                </div>

                <!-- Desktop Right Side -->
                <div class="hidden md:flex items-center space-x-4">
                    <!-- Cart -->
                    <Button variant="outline" class="relative" as="a" href="/cart">
                        <ShoppingCart class="h-5 w-5" />
                        <Badge
                            v-if="cartItemsCount > 0"
                            class="absolute -top-2 -right-2 bg-primary text-primary-foreground min-w-5 h-5 flex items-center justify-center text-xs"
                        >
                            {{ cartItemsCount > 99 ? '99+' : cartItemsCount }}
                        </Badge>
                    </Button>

                    <!-- NEW: My Orders Icon (only for authenticated users) -->
                    <Button
                        v-if="user"
                        variant="outline"
                        class="relative"
                        as="a"
                        href="/my-orders"
                        title="My Orders"
                    >
                        <History class="h-5 w-5" />
                    </Button>

                    <!-- User Dropdown - Manual Implementation -->
                    <div v-if="user" class="relative" ref="dropdownRef">
                        <Button
                            variant="ghost"
                            class="relative h-10 w-10 rounded-full"
                            @click="toggleDropdown"
                            :disabled="isLoggingOut"
                        >
                            <Avatar class="h-9 w-9">
                                <AvatarImage
                                    v-if="userAvatar"
                                    :src="userAvatar"
                                    :alt="userName"
                                />
                                <AvatarFallback class="bg-primary/10 text-primary">
                                    {{ getUserInitials(user) }}
                                </AvatarFallback>
                            </Avatar>
                        </Button>

                        <!-- Dropdown Content -->
                        <Transition
                            enter-active-class="transition ease-out duration-100"
                            enter-from-class="transform opacity-0 scale-95"
                            enter-to-class="transform opacity-100 scale-100"
                            leave-active-class="transition ease-in duration-75"
                            leave-from-class="transform opacity-100 scale-100"
                            leave-to-class="transform opacity-0 scale-95"
                        >
                            <div
                                v-if="dropdownOpen"
                                class="absolute right-0 mt-2 w-56 bg-popover border border-border rounded-md shadow-lg z-50"
                            >
                                <!-- User Info -->
                                <div class="px-3 py-2 border-b border-border">
                                    <div class="flex flex-col space-y-1">
                                        <p class="text-sm font-medium leading-none">{{ userName }}</p>
                                        <p class="text-xs text-muted-foreground leading-none">{{ userEmail }}</p>
                                    </div>
                                </div>

                                <!-- Menu Items (removed My Orders from here) -->
                                <div class="py-1">
                                    <button
                                        @click="handleDropdownItemClick('/dashboard')"
                                        class="w-full px-3 py-2 text-left text-sm hover:bg-accent hover:text-accent-foreground flex items-center transition-colors"
                                        :disabled="isLoggingOut"
                                    >
                                        <User class="mr-2 h-4 w-4" />
                                        <span>Dashboard</span>
                                    </button>
                                    <button
                                        @click="handleDropdownItemClick('/user/profile')"
                                        class="w-full px-3 py-2 text-left text-sm hover:bg-accent hover:text-accent-foreground flex items-center transition-colors"
                                        :disabled="isLoggingOut"
                                    >
                                        <User class="mr-2 h-4 w-4" />
                                        <span>Profile</span>
                                    </button>
                                    <button
                                        @click="handleDropdownItemClick('/user/settings')"
                                        class="w-full px-3 py-2 text-left text-sm hover:bg-accent hover:text-accent-foreground flex items-center transition-colors"
                                        :disabled="isLoggingOut"
                                    >
                                        <Settings class="mr-2 h-4 w-4" />
                                        <span>Settings</span>
                                    </button>
                                </div>

                                <div class="border-t border-border py-1">
                                    <button
                                        @click="logout"
                                        :disabled="isLoggingOut"
                                        class="w-full px-3 py-2 text-left text-sm text-destructive hover:bg-destructive/10 flex items-center transition-colors disabled:opacity-50"
                                    >
                                        <LogOut class="mr-2 h-4 w-4" />
                                        <span>{{ isLoggingOut ? 'Logging out...' : 'Log out' }}</span>
                                    </button>
                                </div>
                            </div>
                        </Transition>
                    </div>

                    <!-- Guest Login/Register -->
                    <div v-else class="flex items-center space-x-2">
                        <Button variant="ghost" size="sm" as="a" href="/login">
                            Login
                        </Button>
                        <Button size="sm" as="a" href="/register">
                            Sign Up
                        </Button>
                    </div>
                </div>

                <!-- Mobile menu button -->
                <div class="md:hidden flex items-center space-x-2">
                    <!-- Mobile Cart -->
                    <Button variant="outline" size="sm" class="relative" as="a" href="/cart">
                        <ShoppingCart class="h-4 w-4" />
                        <Badge
                            v-if="cartItemsCount > 0"
                            class="absolute -top-1 -right-1 h-4 w-4 p-0 flex items-center justify-center text-xs bg-primary text-primary-foreground"
                        >
                            {{ cartItemsCount > 9 ? '9+' : cartItemsCount }}
                        </Badge>
                    </Button>

                    <!-- NEW: Mobile My Orders (only for authenticated users) -->
                    <Button
                        v-if="user"
                        variant="outline"
                        size="sm"
                        class="relative"
                        as="a"
                        href="/my-orders"
                    >
                        <History class="h-4 w-4" />
                    </Button>

                    <Button variant="ghost" size="sm" @click="toggleMobileMenu" :disabled="isLoggingOut">
                        <Menu v-if="!mobileMenuOpen" class="h-5 w-5" />
                        <X v-else class="h-5 w-5" />
                    </Button>
                </div>
            </div>

            <!-- Mobile Menu -->
            <Transition
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="transform scale-95 opacity-0"
                enter-to-class="transform scale-100 opacity-100"
                leave-active-class="transition duration-75 ease-in"
                leave-from-class="transform scale-100 opacity-100"
                leave-to-class="transform scale-95 opacity-0"
            >
                <div v-if="mobileMenuOpen" class="md:hidden border-t py-4 space-y-2">
                    <div class="flex flex-col space-y-2">
                        <!-- Mobile Categories -->
                        <div v-if="categories.length > 0" class="space-y-2">
                            <p class="px-3 text-xs font-semibold text-muted-foreground uppercase tracking-wider">Categories</p>
                            <Button
                                v-for="category in categories"
                                :key="category.id"
                                variant="ghost"
                                :class="getCategoryButtonClass() + ' justify-start'"
                                as="a"
                                :href="`/products?category=${category.slug}`"
                                @click="closeMobileMenu"
                            >
                                {{ category.name }}
                            </Button>
                            <Separator class="my-2" />
                        </div>

                        <!-- Mobile User Section -->
                        <div v-if="user" class="space-y-2">
                            <div class="px-3 py-2 bg-muted/30 rounded-lg mx-2">
                                <div class="flex items-center space-x-3">
                                    <Avatar class="h-8 w-8">
                                        <AvatarImage
                                            v-if="userAvatar"
                                            :src="userAvatar"
                                            :alt="userName"
                                        />
                                        <AvatarFallback class="bg-primary/10 text-primary text-sm">
                                            {{ getUserInitials(user) }}
                                        </AvatarFallback>
                                    </Avatar>
                                    <div>
                                        <p class="text-sm font-medium">{{ userName }}</p>
                                        <p class="text-xs text-muted-foreground">{{ userEmail }}</p>
                                    </div>
                                </div>
                            </div>

                            <Button
                                variant="ghost"
                                class="justify-start w-full"
                                as="a"
                                href="/dashboard"
                                @click="closeMobileMenu"
                                :disabled="isLoggingOut"
                            >
                                <User class="mr-2 h-4 w-4" />
                                Dashboard
                            </Button>
                            <Button
                                variant="ghost"
                                class="justify-start w-full"
                                as="a"
                                href="/user/profile"
                                @click="closeMobileMenu"
                                :disabled="isLoggingOut"
                            >
                                <User class="mr-2 h-4 w-4" />
                                Profile
                            </Button>
                            <Button
                                variant="ghost"
                                class="justify-start w-full"
                                as="a"
                                href="/user/settings"
                                @click="closeMobileMenu"
                                :disabled="isLoggingOut"
                            >
                                <Settings class="mr-2 h-4 w-4" />
                                Settings
                            </Button>

                            <Separator class="my-2" />

                            <Button
                                variant="ghost"
                                class="justify-start w-full text-destructive hover:text-destructive hover:bg-destructive/10"
                                @click="logout"
                                :disabled="isLoggingOut"
                            >
                                <LogOut class="mr-2 h-4 w-4" />
                                {{ isLoggingOut ? 'Logging out...' : 'Logout' }}
                            </Button>
                        </div>

                        <!-- Mobile Guest Section -->
                        <div v-else class="space-y-2">
                            <p class="px-3 text-xs font-semibold text-muted-foreground uppercase tracking-wider">Account</p>
                            <Button
                                variant="ghost"
                                class="justify-start w-full"
                                as="a"
                                href="/login"
                                @click="closeMobileMenu"
                            >
                                <User class="mr-2 h-4 w-4" />
                                Login
                            </Button>
                            <Button
                                variant="outline"
                                class="justify-start w-full"
                                as="a"
                                href="/register"
                                @click="closeMobileMenu"
                            >
                                <User class="mr-2 h-4 w-4" />
                                Sign Up
                            </Button>
                        </div>
                    </div>
                </div>
            </Transition>
        </div>
    </nav>
</template>

<style scoped>
/* Smooth transitions for mobile menu */
.transition {
    transition-property: all;
}

/* Ensure dropdown appears above other elements */
.z-50 {
    z-index: 50;
}

/* Mobile menu backdrop blur */
@supports (backdrop-filter: blur(8px)) {
    .backdrop-blur-lg {
        backdrop-filter: blur(8px);
    }
}
</style>
