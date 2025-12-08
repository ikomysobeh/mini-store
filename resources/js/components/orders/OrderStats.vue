<!-- resources/js/components/orders/OrderStats.vue -->
<script setup lang="ts">
import { Card, CardContent } from '@/components/ui/card';
import { ShoppingCart, Heart, DollarSign, Calendar, TrendingUp } from 'lucide-vue-next';
import { useI18n } from 'vue-i18n';
import { computed } from 'vue';

const { t } = useI18n();

const props = defineProps({
    stats: { type: Object, required: true }
});

const formatPrice = (price) => {
    return `$${parseFloat(price || 0).toFixed(2)}`;
};

const statsConfig = computed(() => [
    {
        label: t('orders.totalOrders'),
        value: props.stats.total_orders,
        icon: ShoppingCart,
        color: 'text-info'
    },
    {
        label: t('orders.purchases'),
        value: props.stats.total_purchases,
        icon: ShoppingCart,
        color: 'text-success'
    },
    {
        label: t('orders.donations'),
        value: props.stats.total_donations,
        icon: Heart,
        color: 'text-warning'
    },
    {
        label: t('orders.totalSpent'),
        value: formatPrice(props.stats.total_spent),
        icon: DollarSign,
        color: 'text-primary'
    },
    {
        label: t('orders.thisYear'),
        value: formatPrice(props.stats.this_year_spent),
        icon: TrendingUp,
        color: 'text-secondary'
    }
]);
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
