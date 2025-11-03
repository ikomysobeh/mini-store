<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Separator } from '@/components/ui/separator';
import {
    LogOut, ChevronDown, Store, Crown
} from 'lucide-vue-next';
import { ref, computed } from 'vue';

interface NavigationItem {
    title: string;
    href: string;
    icon: any;
    isActive: (path: string) => boolean;
    badge?: string;
}

interface Props {
    navigationItems: NavigationItem[];
    user?: any;
}

const { navigationItems, user } = defineProps<Props>();

const currentPath = computed(() => {
    if (typeof window !== 'undefined') {
        return window.location.pathname;
    }
    return '';
});

const logout = () => {
    router.post('/logout', {}, {
        onSuccess: () => {
            window.location.href = '/';
        }
    });
};

const getUserInitials = (userData: any) => {
    if (userData?.name) {
        return userData.name.split(' ')
            .map((n: string) => n[0])
            .join('')
            .toUpperCase()
            .substring(0, 2);
    }
    return userData?.email?.charAt(0).toUpperCase() || 'A';
};
</script>

<template>
    <div class="fixed inset-y-0 left-0 z-50 w-72 bg-background border-r border-border">
        <!-- Logo & Brand -->
        <div class="flex h-16 items-center px-6 border-b">
            <div class="flex items-center space-x-3">
                <div class="w-8 h-8 bg-primary rounded-lg flex items-center justify-center">
                    <Store class="h-5 w-5 text-primary-foreground" />
                </div>
                <div>
                    <h2 class="font-bold text-lg">Admin Panel</h2>
                </div>
            </div>
        </div>

        <!-- User Info -->
        <div v-if="user" class="p-6 border-b">
            <div class="flex items-center space-x-3">
                <Avatar class="h-10 w-10">
                    <AvatarImage v-if="user.avatar" :src="user.avatar" />
                    <AvatarFallback class="bg-primary/10 text-primary">
                        {{ getUserInitials(user) }}
                    </AvatarFallback>
                </Avatar>
                <div class="flex-1 min-w-0">
                    <p class="font-medium truncate">{{ user.name }}</p>
                    <div class="flex items-center space-x-2">
                        <Badge class="text-xs bg-purple-100 text-purple-800">
                            <Crown class="h-3 w-3 mr-1" />
                            Administrator
                        </Badge>
                    </div>
                </div>
            </div>
        </div>

        <!-- Navigation -->
        <nav class="flex-1 px-6 py-6 space-y-2">
            <div v-for="item in navigationItems" :key="item.href">
                <Button
                    :variant="item.isActive(currentPath) ? 'default' : 'ghost'"
                    :class="[
                        'w-full justify-start h-12 text-left font-normal',
                        item.isActive(currentPath)
                            ? 'bg-primary text-primary-foreground shadow-sm'
                            : 'hover:bg-muted'
                    ]"
                    as="a"
                    :href="item.href"
                >
                    <component :is="item.icon" class="h-5 w-5 mr-3" />
                    <span class="flex-1">{{ item.title }}</span>
                    <Badge v-if="item.badge" variant="secondary" class="ml-auto text-xs">
                        {{ item.badge }}
                    </Badge>
                </Button>
            </div>
        </nav>

        <!-- Footer -->
        <div class="p-6 border-t">
            <Button
                variant="ghost"
                class="w-full justify-start text-destructive hover:text-destructive hover:bg-destructive/10"
                @click="logout"
            >
                <LogOut class="h-4 w-4 mr-3" />
                Sign Out
            </Button>
        </div>
    </div>
</template>
