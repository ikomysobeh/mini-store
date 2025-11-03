<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { BarChart3, Calendar, Eye, ShoppingCart } from 'lucide-vue-next';

interface Product {
    created_at: string;
    updated_at: string;
    views?: number;
    total_sold?: number;
    revenue?: number;
}

interface Props {
    product: Product;
    title?: string;
    currency?: string;
    locale?: string;
}

const { product, title = 'Product Stats', currency = 'EUR', locale = 'nl-NL' } = defineProps<Props>();

const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString(locale, {
        day: '2-digit',
        month: 'short',
        year: 'numeric'
    });
};

const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat(locale, {
        style: 'currency',
        currency: currency
    }).format(amount || 0);
};

const stats = [
    {
        label: 'Views',
        value: product.views || 0,
        icon: Eye,
        color: 'text-blue-600'
    },
    {
        label: 'Sales',
        value: product.total_sold || 0,
        icon: ShoppingCart,
        color: 'text-green-600'
    },
    {
        label: 'Revenue',
        value: formatCurrency(product.revenue || 0),
        icon: BarChart3,
        color: 'text-purple-600'
    }
];
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle class="text-lg flex items-center space-x-2">
                <BarChart3 class="h-5 w-5" />
                <span>{{ title }}</span>
            </CardTitle>
        </CardHeader>
        <CardContent class="space-y-4">

            <!-- Performance Stats -->
            <div class="grid grid-cols-1 gap-3">
                <div v-for="stat in stats" :key="stat.label" class="flex items-center justify-between p-3 bg-muted/30 rounded-lg">
                    <div class="flex items-center space-x-2">
                        <component :is="stat.icon" :class="`h-4 w-4 ${stat.color}`" />
                        <span class="text-sm text-muted-foreground">{{ stat.label }}:</span>
                    </div>
                    <span class="font-semibold">{{ stat.value }}</span>
                </div>
            </div>

            <!-- Date Information -->
            <div class="pt-3 border-t space-y-3 text-sm">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <Calendar class="h-4 w-4 text-muted-foreground" />
                        <span class="text-muted-foreground">Created:</span>
                    </div>
                    <span class="font-medium">{{ formatDate(product.created_at) }}</span>
                </div>

                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <Calendar class="h-4 w-4 text-muted-foreground" />
                        <span class="text-muted-foreground">Updated:</span>
                    </div>
                    <span class="font-medium">{{ formatDate(product.updated_at) }}</span>
                </div>
            </div>

            <!-- Performance Badge -->
            <div class="pt-2">
                <Badge
                    v-if="(product.total_sold || 0) > 0"
                    class="w-full justify-center bg-green-100 text-green-800"
                >
                    🎉 Active Product
                </Badge>
                <Badge
                    v-else
                    variant="outline"
                    class="w-full justify-center"
                >
                    📊 New Product
                </Badge>
            </div>
        </CardContent>
    </Card>
</template>
