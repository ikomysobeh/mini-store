<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import {
    ShoppingCart, DollarSign, Package, Users,
    TrendingUp, TrendingDown, Eye, MoreHorizontal,
    AlertCircle, CheckCircle, Clock, Heart,
    ArrowUpRight, ArrowDownRight, Activity,
    Calendar, Filter, Download
} from 'lucide-vue-next';
import { ref, computed } from 'vue';
import AdminLayout from '@/layouts/AdminLayout.vue';

const { stats, recentOrders, topProducts } = defineProps({
    stats: { type: Object, required: true },
    recentOrders: { type: Array, required: true },
    topProducts: { type: Array, required: true }
});

// Helper functions
const formatPrice = (price) => {
    return new Intl.NumberFormat('nl-NL', {
        style: 'currency',
        currency: 'USD'
    }).format(price || 0);
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('nl-NL', {
        day: '2-digit',
        month: 'short',
        year: 'numeric'
    });
};

const getStatusColor = (status) => {
    const colors = {
        pending: 'bg-yellow-100 text-yellow-800 border-yellow-200',
        processing: 'bg-blue-100 text-blue-800 border-blue-200',
        shipped: 'bg-purple-100 text-purple-800 border-purple-200',
        delivered: 'bg-green-100 text-green-800 border-green-200',
        cancelled: 'bg-red-100 text-red-800 border-red-200'
    };
    return colors[status] || 'bg-gray-100 text-gray-800 border-gray-200';
};

const getStatusIcon = (status) => {
    const icons = {
        pending: Clock,
        processing: Activity,
        shipped: Package,
        delivered: CheckCircle,
        cancelled: AlertCircle
    };
    return icons[status] || Clock;
};

// Computed values for growth indicators (mock data - replace with real calculations)
const revenueGrowth = computed(() => 12.5);
const ordersGrowth = computed(() => 8.3);
const donationsGrowth = computed(() => 15.2);
const customersGrowth = computed(() => 6.7);

const chartTimeframe = ref('7d');
</script>

<template>
    <AdminLayout
        title="Orders Management"
        :breadcrumbs="breadcrumbs"
    >
    <div class="min-h-screen bg-background text-foreground">
        <Head title="Admin Dashboard" />

        <!-- Header -->

        <div class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Key Metrics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">

                <!-- Total Revenue -->
                <Card class="hover:shadow-md transition-shadow duration-200">
                    <CardContent class="p-6">
                        <div class="flex items-center justify-between">
                            <div class="space-y-2">
                                <p class="text-sm font-medium text-muted-foreground">Total Revenue</p>
                                <p class="text-3xl font-bold text-foreground">
                                    {{ formatPrice(stats.total_revenue) }}
                                </p>
                            </div>
                            <div class="p-3 bg-accent/20 rounded-full">
                                <DollarSign class="h-6 w-6 text-accent-foreground" />
                            </div>
                        </div>
                        <div class="flex items-center mt-4 space-x-2">
                            <div class="flex items-center space-x-1">
                                <ArrowUpRight class="h-4 w-4 text-accent-foreground" />
                                <span class="text-sm font-medium text-accent-foreground">
                                    +{{ revenueGrowth }}%
                                </span>
                            </div>
                            <span class="text-sm text-muted-foreground">from last month</span>
                        </div>
                    </CardContent>
                </Card>

                <!-- Total Orders -->
                <Card class="hover:shadow-md transition-shadow duration-200">
                    <CardContent class="p-6">
                        <div class="flex items-center justify-between">
                            <div class="space-y-2">
                                <p class="text-sm font-medium text-muted-foreground">Total Orders</p>
                                <p class="text-3xl font-bold text-foreground">
                                    {{ stats.total_orders.toLocaleString() }}
                                </p>
                            </div>
                            <div class="p-3 bg-primary/10 rounded-full">
                                <ShoppingCart class="h-6 w-6 text-primary" />
                            </div>
                        </div>
                        <div class="flex items-center mt-4 space-x-2">
                            <div class="flex items-center space-x-1">
                                <ArrowUpRight class="h-4 w-4 text-accent-foreground" />
                                <span class="text-sm font-medium text-accent-foreground">
                                    +{{ ordersGrowth }}%
                                </span>
                            </div>
                            <span class="text-sm text-muted-foreground">from last month</span>
                        </div>
                        <div class="mt-3">
                            <div class="flex items-center space-x-2">
                                <Badge variant="outline" class="bg-orange-50 text-orange-700 border-orange-200">
                                    {{ stats.pending_orders }} pending
                                </Badge>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Total Donations -->
                <Card class="hover:shadow-md transition-shadow duration-200">
                    <CardContent class="p-6">
                        <div class="flex items-center justify-between">
                            <div class="space-y-2">
                                <p class="text-sm font-medium text-muted-foreground">Donations</p>
                                <p class="text-3xl font-bold text-foreground">
                                    {{ formatPrice(stats.total_donations) }}
                                </p>
                            </div>
                            <div class="p-3 bg-destructive/20 rounded-full">
                                <Heart class="h-6 w-6 text-destructive" />
                            </div>
                        </div>
                        <div class="flex items-center mt-4 space-x-2">
                            <div class="flex items-center space-x-1">
                                <ArrowUpRight class="h-4 w-4 text-accent-foreground" />
                                <span class="text-sm font-medium text-accent-foreground">
                                    +{{ donationsGrowth }}%
                                </span>
                            </div>
                            <span class="text-sm text-muted-foreground">from last month</span>
                        </div>
                        <div class="mt-3">
                            <p class="text-xs text-muted-foreground">
                                💝 Tax-exempt contributions
                            </p>
                        </div>
                    </CardContent>
                </Card>

                <!-- Customers -->
                <Card class="hover:shadow-md transition-shadow duration-200">
                    <CardContent class="p-6">
                        <div class="flex items-center justify-between">
                            <div class="space-y-2">
                                <p class="text-sm font-medium text-muted-foreground">Customers</p>
                                <p class="text-3xl font-bold text-foreground">
                                    {{ stats.total_customers.toLocaleString() }}
                                </p>
                            </div>
                            <div class="p-3 bg-secondary/20 rounded-full">
                                <Users class="h-6 w-6 text-secondary-foreground" />
                            </div>
                        </div>
                        <div class="flex items-center mt-4 space-x-2">
                            <div class="flex items-center space-x-1">
                                <ArrowUpRight class="h-4 w-4 text-accent-foreground" />
                                <span class="text-sm font-medium text-accent-foreground">
                                    +{{ customersGrowth }}%
                                </span>
                            </div>
                            <span class="text-sm text-muted-foreground">new this month</span>
                        </div>
                        <div class="mt-3">
                            <div class="flex items-center space-x-2">
                                <div class="text-xs text-muted-foreground">
                                    {{ stats.total_products }} products available
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

                <!-- Recent Orders -->
                <div class="lg:col-span-2 space-y-6">
                    <Card class="shadow-sm">
                        <CardHeader class="pb-4">
                            <div class="flex items-center justify-between">
                                <CardTitle class="text-xl font-semibold">Recent Orders</CardTitle>
                                <Button variant="outline" size="sm" as="a" href="/admin/orders">
                                    <Eye class="h-4 w-4 mr-2" />
                                    View All
                                </Button>
                            </div>
                        </CardHeader>
                        <CardContent class="p-0">
                            <div class="space-y-0">
                                <div
                                    v-for="(order, index) in recentOrders.slice(0, 8)"
                                    :key="order.id"
                                    :class="[
                                        'flex items-center justify-between p-6 hover:bg-muted/50 transition-colors',
                                        index !== recentOrders.slice(0, 8).length - 1 ? 'border-b' : ''
                                    ]"
                                >
                                    <div class="flex items-center space-x-4">
                                        <!-- Order Avatar -->
                                        <Avatar class="h-10 w-10">
                                            <AvatarFallback class="bg-primary/10 text-primary font-semibold">
                                                {{ order.customer?.user?.name?.charAt(0) || 'G' }}
                                            </AvatarFallback>
                                        </Avatar>

                                        <!-- Order Info -->
                                        <div class="space-y-1">
                                            <div class="flex items-center space-x-2">
                                                <p class="font-medium text-sm">
                                                    Order #{{ order.id }}
                                                </p>
                                                <Badge
                                                    :class="getStatusColor(order.status)"
                                                    class="text-xs px-2 py-0.5 border"
                                                >
                                                    <component
                                                        :is="getStatusIcon(order.status)"
                                                        class="h-3 w-3 mr-1"
                                                    />
                                                    {{ order.status }}
                                                </Badge>
                                            </div>
                                            <div class="flex items-center space-x-3 text-xs text-muted-foreground">
                                                <span>{{ order.customer?.user?.name || 'Guest' }}</span>
                                                <span>•</span>
                                                <span>{{ formatDate(order.created_at) }}</span>
                                                <span v-if="order.is_donation">•</span>
                                                <Badge v-if="order.is_donation" variant="outline" class="text-xs px-1.5 py-0">
                                                    <Heart class="h-2.5 w-2.5 mr-1 text-red-500" />
                                                    Donation
                                                </Badge>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Order Details -->
                                    <div class="flex items-center space-x-4">
                                        <div class="text-right">
                                            <p class="font-semibold text-sm">
                                                {{ formatPrice(order.total) }}
                                            </p>
                                            <p class="text-xs text-muted-foreground">
                                                {{ order.items?.length || 0 }} items
                                            </p>
                                        </div>


                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Top Products & Quick Stats -->
                <div class="space-y-6">

                    <!-- Top Products -->
                    <Card class="shadow-sm">
                        <CardHeader class="pb-4">
                            <div class="flex items-center justify-between">
                                <CardTitle class="text-lg font-semibold">Top Products</CardTitle>
                                <Button variant="outline" size="sm" as="a" href="/admin/products">
                                    <Eye class="h-4 w-4 mr-2" />
                                    View All
                                </Button>
                            </div>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div
                                v-for="(product, index) in topProducts"
                                :key="product.id"
                                class="flex items-center justify-between p-3 rounded-lg bg-muted/30"
                            >
                                <div class="flex items-center space-x-3">
                                    <!-- Rank Badge -->
                                    <div :class="[
                                        'w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold text-white',
                                        index === 0 ? 'bg-yellow-500' :
                                        index === 1 ? 'bg-gray-400' :
                                        index === 2 ? 'bg-orange-600' : 'bg-muted-foreground'
                                    ]">
                                        {{ index + 1 }}
                                    </div>

                                    <!-- Product Info -->
                                    <div>
                                        <p class="font-medium text-sm">{{ product.name }}</p>
                                        <div class="flex items-center space-x-2 text-xs text-muted-foreground">
                                            <span>{{ product.total_sold || 0 }} sold</span>
                                            <span>•</span>
                                            <span>{{ formatPrice(product.price) }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Trending Icon -->
                                <div class="flex items-center space-x-1">
                                    <TrendingUp class="h-4 w-4 text-accent-foreground" />
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Quick Actions -->
                    <Card class="shadow-sm hover:shadow-md transition-all">
                        <CardHeader class="pb-4">
                            <CardTitle class="text-lg font-semibold">Quick Actions</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-3">
                            <Button variant="outline" class="w-full justify-start" as="a" href="/admin/products/create">
                                <Package class="h-4 w-4 mr-2" />
                                Add New Product
                            </Button>
                            <Button variant="outline" class="w-full justify-start" as="a" href="/admin/orders">
                                <ShoppingCart class="h-4 w-4 mr-2" />
                                Manage Orders
                            </Button>
                            <Button variant="outline" class="w-full justify-start" as="a" href="/admin/users">
                                <Users class="h-4 w-4 mr-2" />
                                View Customers
                            </Button>
                            <Button variant="outline" class="w-full justify-start" as="a" href="/admin/settings">
                                <Activity class="h-4 w-4 mr-2" />
                                Settings
                            </Button>
                        </CardContent>
                    </Card>

                    <!-- System Status -->
                    <Card class="shadow-sm">
                        <CardHeader class="pb-4">
                            <CardTitle class="text-lg font-semibold">System Status</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-2">
                                    <div class="w-3 h-3 bg-accent rounded-full animate-pulse"></div>
                                    <span class="text-sm">Server Status</span>
                                </div>
                                <Badge variant="outline" class="bg-accent/20 text-accent-foreground border-accent">
                                    Online
                                </Badge>
                            </div>

                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-2">
                                    <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                                    <span class="text-sm">Payment Gateway</span>
                                </div>
                                <Badge variant="outline" class="bg-green-50 text-green-700 border-green-200">
                                    Active
                                </Badge>
                            </div>

                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-2">
                                    <div class="w-3 h-3 bg-blue-500 rounded-full"></div>
                                    <span class="text-sm">Email Service</span>
                                </div>
                                <Badge variant="outline" class="bg-blue-50 text-blue-700 border-blue-200">
                                    Running
                                </Badge>
                            </div>

                            <div class="pt-3 border-t">
                                <div class="text-xs text-muted-foreground">
                                    Last updated: 2 minutes ago
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>

        </div>
    </div>
    </AdminLayout>
</template>

<style scoped>
/* Custom animations */
.animate-pulse {
    animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

@keyframes pulse {
    0%, 100% {
        opacity: 1;
    }
    50% {
        opacity: .5;
    }
}

/* Smooth hover transitions */
.hover\:shadow-md:hover {
    transition: box-shadow 0.2s ease-in-out;
}

/* Custom scrollbar for sidebar */
.space-y-0::-webkit-scrollbar {
    width: 4px;
}

.space-y-0::-webkit-scrollbar-track {
    background: transparent;
}

.space-y-0::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 2px;
}
</style>
