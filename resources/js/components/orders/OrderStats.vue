<!-- resources/js/components/orders/OrderStats.vue -->
<script setup lang="ts">
import { Card, CardContent } from '@/components/ui/card';
import { ShoppingCart, Heart, DollarSign, Calendar, TrendingUp } from 'lucide-vue-next';

const { stats } = defineProps({
    stats: { type: Object, required: true }
});

const formatPrice = (price) => {
    return `$${parseFloat(price || 0).toFixed(2)}`;
};

const statsConfig = [
    {
        label: 'Total Orders',
        value: stats.total_orders,
        icon: ShoppingCart,
        color: 'text-blue-600'
    },
    {
        label: 'Purchases',
        value: stats.total_purchases,
        icon: ShoppingCart,
        color: 'text-green-600'
    },
    {
        label: 'Donations',
        value: stats.total_donations,
        icon: Heart,
        color: 'text-red-600'
    },
    {
        label: 'Total Spent',
        value: formatPrice(stats.total_spent),
        icon: DollarSign,
        color: 'text-purple-600'
    },
    {
        label: 'This Year',
        value: formatPrice(stats.this_year_spent),
        icon: TrendingUp,
        color: 'text-orange-600'
    }
];
</script>

<template>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
        <Card v-for="stat in statsConfig" :key="stat.label" class="hover:shadow-sm transition-shadow">
            <CardContent class="p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-muted-foreground mb-1">{{ stat.label }}</p>
                        <p class="text-xl font-bold">{{ stat.value }}</p>
                    </div>
                    <component :is="stat.icon" :class="`h-8 w-8 ${stat.color}`" />
                </div>
            </CardContent>
        </Card>
    </div>
</template>
