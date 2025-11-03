<script setup lang="ts">
import { Card, CardContent } from '@/components/ui/card';
import { computed } from 'vue';

interface Stat {
    label: string;
    value: number | string;
    color?: string;
    icon?: any;
}

interface Props {
    stats: Stat[];
    columns?: number;
    className?: string;
}

const { stats, columns = 4, className = 'mt-8' } = defineProps<Props>();

const gridClass = computed(() => {
    return `grid-cols-1 md:grid-cols-${Math.min(columns, stats.length)}`;
});

const getValueColor = (color?: string) => {
    const colors = {
        primary: 'text-primary',
        green: 'text-green-600',
        red: 'text-red-600',
        blue: 'text-blue-600',
        yellow: 'text-yellow-600',
        purple: 'text-purple-600',
        orange: 'text-orange-600'
    };
    return colors[color] || 'text-primary';
};
</script>

<template>
    <div :class="`grid ${gridClass} gap-4 ${className}`">
        <Card v-for="(stat, index) in stats" :key="index">
            <CardContent class="p-6 text-center">
                <div v-if="stat.icon" class="flex justify-center mb-3">
                    <div class="p-2 bg-muted rounded-lg">
                        <component :is="stat.icon" class="h-6 w-6 text-muted-foreground" />
                    </div>
                </div>
                <p :class="`text-3xl font-bold ${getValueColor(stat.color)}`">
                    {{ typeof stat.value === 'number' ? stat.value.toLocaleString() : stat.value }}
                </p>
                <p class="text-sm text-muted-foreground mt-1">{{ stat.label }}</p>
            </CardContent>
        </Card>
    </div>
</template>
