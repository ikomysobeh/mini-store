<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Eye, BarChart3 } from 'lucide-vue-next';

interface Category {
    id: number;
    name: string;
    slug: string;
    products_count?: number;
    is_active: boolean;
    created_at: string;
    updated_at: string;
}

interface Props {
    category: Category;
    title?: string;
    showActions?: boolean;
}

const { category, title = 'Category Stats', showActions = true } = defineProps<Props>();

// Helper functions
const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
};

const getStatusConfig = (isActive: boolean) => {
    return isActive
        ? { label: 'Active', class: 'text-green-600' }
        : { label: 'Inactive', class: 'text-red-600' };
};

const statusConfig = getStatusConfig(category.is_active);
</script>

<template>
    <Card>
        <CardHeader class="pb-3">
            <CardTitle class="text-lg flex items-center space-x-2">
                <BarChart3 class="h-5 w-5" />
                <span>{{ title }}</span>
            </CardTitle>
        </CardHeader>
        <CardContent class="space-y-4">

            <!-- Stats Grid -->
            <div class="space-y-3 text-sm">
                <div class="flex justify-between items-center">
                    <span class="text-muted-foreground">Products:</span>
                    <Badge variant="outline" class="font-medium">
                        {{ category.products_count || 0 }} product{{ category.products_count !== 1 ? 's' : '' }}
                    </Badge>
                </div>

                <div class="flex justify-between items-center">
                    <span class="text-muted-foreground">Status:</span>
                    <Badge :class="statusConfig.class" variant="outline">
                        {{ statusConfig.label }}
                    </Badge>
                </div>

                <div class="flex justify-between items-center">
                    <span class="text-muted-foreground">Created:</span>
                    <span class="font-medium">{{ formatDate(category.created_at) }}</span>
                </div>

                <div class="flex justify-between items-center">
                    <span class="text-muted-foreground">Updated:</span>
                    <span class="font-medium">{{ formatDate(category.updated_at) }}</span>
                </div>

                <div class="flex justify-between items-center">
                    <span class="text-muted-foreground">Category ID:</span>
                    <code class="text-xs bg-muted px-1.5 py-0.5 rounded font-mono">
                        {{ category.id }}
                    </code>
                </div>
            </div>

            <!-- Quick Actions -->
            <div v-if="showActions" class="pt-2 border-t space-y-2">
                <Button
                    variant="outline"
                    size="sm"
                    class="w-full justify-start"
                    as="a"
                    :href="`/products?category=${category.slug}`"
                    target="_blank"
                >
                    <Eye class="h-4 w-4 mr-2" />
                    View Category Page
                </Button>

                <Button
                    variant="outline"
                    size="sm"
                    class="w-full justify-start"
                    as="a"
                    :href="`/admin/products?category=${category.slug}`"
                >
                    <BarChart3 class="h-4 w-4 mr-2" />
                    Manage Products
                </Button>
            </div>
        </CardContent>
    </Card>
</template>
