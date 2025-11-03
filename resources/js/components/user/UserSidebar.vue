<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import { LogOut, Crown } from 'lucide-vue-next';
import { computed } from 'vue';

interface NavigationItem {
    title: string;
    href: string;
    icon: any;
    isActive: (path: string) => boolean;
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
    return userData?.email?.charAt(0).toUpperCase() || 'U';
};
</script>

<template>
    <div class="space-y-6">

        <!-- User Profile Card -->
        <Card>
            <CardContent class="p-6">
                <div class="flex items-center space-x-3 mb-4">
                    <Avatar class="h-12 w-12">
                        <AvatarImage v-if="user?.avatar" :src="user.avatar" />
                        <AvatarFallback class="bg-primary/10 text-primary">
                            {{ getUserInitials(user) }}
                        </AvatarFallback>
                    </Avatar>
                    <div class="flex-1 min-w-0">
                        <p class="font-semibold truncate">{{ user?.name }}</p>
                        <p class="text-sm text-muted-foreground truncate">{{ user?.email }}</p>
                        <Badge v-if="user?.is_admin" variant="outline" class="text-xs mt-1">
                            <Crown class="h-3 w-3 mr-1" />
                            Admin Access
                        </Badge>
                    </div>
                </div>

                <Separator class="mb-4" />

                <div class="space-y-1 text-sm">
                    <div class="flex justify-between">
                        <span class="text-muted-foreground">Member since</span>
                        <span>{{ new Date(user?.created_at).getFullYear() }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-muted-foreground">Total orders</span>
                        <span>{{ user?.orders_count || 0 }}</span>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Navigation Menu -->
        <Card>
            <CardContent class="p-0">
                <nav class="space-y-1">
                    <div v-for="item in navigationItems" :key="item.href">
                        <Button
                            :variant="item.isActive(currentPath) ? 'secondary' : 'ghost'"
                            :class="[
                                'w-full justify-start h-12 text-left font-normal rounded-none first:rounded-t-lg last:rounded-b-lg',
                                item.isActive(currentPath)
                                    ? 'bg-primary/10 text-primary border-r-2 border-primary'
                                    : 'hover:bg-muted'
                            ]"
                            as="a"
                            :href="item.href"
                        >
                            <component :is="item.icon" class="h-4 w-4 mr-3" />
                            <span>{{ item.title }}</span>
                        </Button>
                    </div>
                </nav>
            </CardContent>
        </Card>

        <!-- Admin Quick Access -->
        <Card v-if="user?.is_admin" class="border-purple-200 bg-purple-50">
            <CardContent class="p-4">
                <div class="flex items-center space-x-2 mb-2">
                    <Crown class="h-4 w-4 text-purple-600" />
                    <span class="font-medium text-purple-900">Admin Access</span>
                </div>
                <Button
                    variant="outline"
                    size="sm"
                    as="a"
                    href="/admin"
                    class="w-full text-purple-700 border-purple-300 hover:bg-purple-100"
                >
                    Go to Admin Panel
                </Button>
            </CardContent>
        </Card>

        <!-- Sign Out -->
        <Button
            variant="ghost"
            class="w-full justify-start text-destructive hover:text-destructive hover:bg-destructive/10"
            @click="logout"
        >
            <LogOut class="h-4 w-4 mr-3" />
            Sign Out
        </Button>
    </div>
</template>
