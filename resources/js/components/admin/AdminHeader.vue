<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Link } from '@inertiajs/vue3';
import { Menu, Bell, Search, User, LogOut, Eye } from 'lucide-vue-next';

interface Emits {
    (e: 'toggleSidebar'): void;
}

const emit = defineEmits<Emits>();

const logout = () => {
    if (confirm('Are you sure you want to logout?')) {
        // Handle logout logic
        window.location.href = '/logout';
    }
};

const viewSite = () => {
    window.open('/', '_blank');
};
</script>

<template>
    <header class="bg-white shadow-sm border-b border-gray-200">
        <div class="px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">

                <!-- Left side -->
                <div class="flex items-center space-x-4">
                    <!-- Mobile menu button -->
                    <button
                        @click="emit('toggleSidebar')"
                        class="lg:hidden p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100"
                    >
                        <Menu class="h-5 w-5" />
                    </button>

                    <!-- Search (optional) -->
                    <div class="hidden md:block">
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <Search class="h-4 w-4 text-gray-400" />
                            </div>
                            <input
                                type="search"
                                placeholder="Search..."
                                class="block w-64 pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-blue-500 focus:border-blue-500 text-sm"
                            />
                        </div>
                    </div>
                </div>

                <!-- Right side -->
                <div class="flex items-center space-x-4">

                    <!-- Notifications -->
                    <button class="p-2 text-gray-400 hover:text-gray-500 hover:bg-gray-100 rounded-md relative">
                        <Bell class="h-5 w-5" />
                        <!-- Notification badge -->
                        <span class="absolute top-0 right-0 block h-2 w-2 rounded-full bg-red-400 ring-2 ring-white"></span>
                    </button>

                    <!-- View Site -->
                    <Button @click="viewSite" variant="outline" size="sm">
                        <Eye class="h-4 w-4 mr-2" />
                        View Site
                    </Button>

                    <!-- User menu -->
                    <div class="relative">
                        <div class="flex items-center space-x-3">
                            <div class="hidden md:block text-right">
                                <p class="text-sm font-medium text-gray-900">Admin User</p>
                                <p class="text-xs text-gray-500">administrator@example.com</p>
                            </div>

                            <div class="flex items-center space-x-2">
                                <button class="p-2 text-gray-400 hover:text-gray-500 hover:bg-gray-100 rounded-md">
                                    <User class="h-5 w-5" />
                                </button>

                                <button @click="logout" class="p-2 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-md">
                                    <LogOut class="h-5 w-5" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>
</template>
