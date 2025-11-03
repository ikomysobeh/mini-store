<script setup lang="ts">
import { Card, CardContent } from '@/components/ui/card';
import { Package, Heart, AlertTriangle, CheckCircle } from 'lucide-vue-next';

interface Product {
    is_active: boolean;
    is_donatable: boolean;
    stock: number;
}

interface Props {
    products: Product[];
    className?: string;
}

const { products, className = 'mb-8' } = defineProps<Props>();

const stats = {
    total: products.length,
    active: products.filter(p => p.is_active).length,
    donations: products.filter(p => p.is_donatable).length,
    lowStock: products.filter(p => p.stock < 10 && p.stock > 0).length,
    outOfStock: products.filter(p => p.stock === 0).length
};
</script>

<template>
    <div :class="`grid grid-cols-1 md:grid-cols-5 gap-4 ${className}`">
        <!-- Total Products -->
        <Card>
            <CardContent class="p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs text-muted-foreground">Total Products</p>
                        <p class="text-xl font-bold">{{ stats.total.toLocaleString() }}</p>
                    </div>
                    <div class="p-2 bg-blue-100 rounded-lg">
                        <Package class="h-4 w-4 text-blue-600" />
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Active Products -->
        <Card>
            <CardContent class="p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs text-muted-foreground">Active</p>
                        <p class="text-xl font-bold text-green-600">{{ stats.active.toLocaleString() }}</p>
                    </div>
                    <div class="p-2 bg-green-100 rounded-lg">
                        <CheckCircle class="h-4 w-4 text-green-600" />
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Donation Products -->
        <Card>
            <CardContent class="p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs text-muted-foreground">Donations</p>
                        <p class="text-xl font-bold text-red-600">{{ stats.donations.toLocaleString() }}</p>
                    </div>
                    <div class="p-2 bg-red-100 rounded-lg">
                        <Heart class="h-4 w-4 text-red-600" />
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Low Stock -->
        <Card>
            <CardContent class="p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs text-muted-foreground">Low Stock</p>
                        <p class="text-xl font-bold text-orange-600">{{ stats.lowStock.toLocaleString() }}</p>
                    </div>
                    <div class="p-2 bg-orange-100 rounded-lg">
                        <AlertTriangle class="h-4 w-4 text-orange-600" />
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Out of Stock -->
        <Card>
            <CardContent class="p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs text-muted-foreground">Out of Stock</p>
                        <p class="text-xl font-bold text-red-600">{{ stats.outOfStock.toLocaleString() }}</p>
                    </div>
                    <div class="p-2 bg-red-100 rounded-lg">
                        <Package class="h-4 w-4 text-red-600" />
                    </div>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
