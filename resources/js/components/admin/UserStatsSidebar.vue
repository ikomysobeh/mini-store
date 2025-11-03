<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { computed } from 'vue'; // FIXED: Added missing import

interface User {
    id: number;
    name: string;
    is_admin: boolean;
    created_at: string;
    updated_at: string;
    customer?: {
        first_name?: string;
        last_name?: string;
        phone?: string;
        address?: string;
        orders_count?: number;
        total_spent?: number;
        created_at?: string;
    };
}

interface Props {
    user: User;
    currency?: string;
    locale?: string;
}

const props = withDefaults(defineProps<Props>(), {
    currency: 'EUR',
    locale: 'nl-NL'
});

const formatPrice = (price: number) => {
    return new Intl.NumberFormat(props.locale, {
        style: 'currency',
        currency: props.currency
    }).format(price || 0);
};

const formatDateShort = (date: string) => {
    return new Date(date).toLocaleDateString(props.locale, {
        day: '2-digit',
        month: 'short',
        year: 'numeric'
    });
};

const fullName = computed(() => {
    if (props.user.customer?.first_name && props.user.customer?.last_name) {
        return `${props.user.customer.first_name} ${props.user.customer.last_name}`;
    }
    return props.user.name;
});

const totalOrders = computed(() => {
    return props.user.customer?.orders_count || 0;
});

const totalSpent = computed(() => {
    return props.user.customer?.total_spent || 0;
});
</script>

<template>
    <div class="space-y-6">

        <!-- Customer Stats -->
        <Card v-if="user.customer">
            <CardHeader>
                <CardTitle class="text-lg">Customer Stats</CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
                <div class="grid grid-cols-1 gap-4">
                    <div class="text-center p-4 bg-muted/30 rounded-lg">
                        <p class="text-2xl font-bold text-primary">{{ totalOrders }}</p>
                        <p class="text-sm text-muted-foreground">Total Orders</p>
                    </div>
                    <div class="text-center p-4 bg-muted/30 rounded-lg">
                        <p class="text-2xl font-bold text-green-600">{{ formatPrice(totalSpent) }}</p>
                        <p class="text-sm text-muted-foreground">Total Spent</p>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Account Details -->
        <Card>
            <CardHeader>
                <CardTitle class="text-lg">Account Details</CardTitle>
            </CardHeader>
            <CardContent class="space-y-3">
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <span class="text-muted-foreground">User ID:</span>
                        <span class="font-medium">#{{ user.id }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-muted-foreground">Full Name:</span>
                        <span>{{ fullName }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-muted-foreground">Registered:</span>
                        <span>{{ formatDateShort(user.created_at) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-muted-foreground">Last Updated:</span>
                        <span>{{ formatDateShort(user.updated_at) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-muted-foreground">Role:</span>
                        <Badge :class="user.is_admin ? 'bg-purple-100 text-purple-800' : 'bg-green-100 text-green-800'" class="text-xs">
                            {{ user.is_admin ? 'Administrator' : 'User' }}
                        </Badge>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Customer Profile -->
        <Card v-if="user.customer">
            <CardHeader>
                <CardTitle class="text-lg">Customer Profile</CardTitle>
            </CardHeader>
            <CardContent class="space-y-3">
                <div class="space-y-3 text-sm">
                    <div v-if="user.customer.phone">
                        <span class="text-muted-foreground">Phone:</span>
                        <p class="font-medium">{{ user.customer.phone }}</p>
                    </div>
                    <div v-if="user.customer.address">
                        <span class="text-muted-foreground">Address:</span>
                        <p class="font-medium">{{ user.customer.address }}</p>
                    </div>
                    <div>
                        <span class="text-muted-foreground">Customer Since:</span>
                        <p class="font-medium">{{ formatDateShort(user.customer.created_at || user.created_at) }}</p>
                    </div>
                </div>
            </CardContent>
        </Card>

    </div>
</template>
