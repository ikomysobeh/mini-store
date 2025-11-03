<script setup lang="ts">
import { Card, CardContent } from '@/components/ui/card';
import { computed } from 'vue';

interface StatItem {
    label: string;
    value: number | string;
    icon: any;
    color: string;
    formatter?: 'currency' | 'number' | 'text';
}

interface Props {
    stats: Record<string, any>;
    currency?: string;
    locale?: string;
}

const { stats, currency = 'EUR', locale = 'nl-NL' } = defineProps<Props>();

const formatValue = (value: any, formatter?: string) => {
    if (formatter === 'currency') {
        return new Intl.NumberFormat(locale, {
            style: 'currency',
            currency: currency
        }).format(value || 0);
    } else if (formatter === 'number') {
        return (value || 0).toLocaleString();
    }
    return value || 0;
};

const statsConfig = computed(() => [
    {
        label: 'Total Orders',
        value: stats.total,
        icon: 'ShoppingCart',
        color: 'blue',
        formatter: 'number'
    },
    {
        label: 'Revenue',
        value: stats.total_revenue,
        icon: 'CheckCircle',
        color: 'green',
        formatter: 'currency'
    },
    {
        label: 'Donations',
        value: stats.donations_total,
        icon: 'Heart',
        color: 'red',
        formatter: 'currency'
    },
    {
        label: 'Pending',
        value: stats.pending,
        icon: 'Clock',
        color: 'yellow',
        formatter: 'number'
    }
]);

const getColorClasses = (color: string) => {
    const colors = {
        blue: 'bg-blue-100 text-blue-600',
        green: 'bg-green-100 text-green-600',
        red: 'bg-red-100 text-red-600',
        yellow: 'bg-yellow-100 text-yellow-600',
        purple: 'bg-purple-100 text-purple-600'
    };
    return colors[color] || colors.blue;
};
</script>

<template>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <Card v-for="stat in statsConfig" :key="stat.label">
            <CardContent class="p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-muted-foreground">{{ stat.label }}</p>
                        <p class="text-2xl font-bold">{{ formatValue(stat.value, stat.formatter) }}</p>
                    </div>
                    <div :class="`p-3 rounded-full ${getColorClasses(stat.color)}`">
                        <!-- Dynamic icon rendering would need icon mapping -->
                        <div class="h-5 w-5">📊</div>
                    </div>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
