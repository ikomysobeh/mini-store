<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { ShoppingCart, ArrowLeft } from 'lucide-vue-next';

interface Props {
    itemCount: number;
    isEmpty: boolean;
}

const { itemCount, isEmpty } = defineProps<Props>();
</script>

<template>
    <div class="flex items-center justify-between mb-8">
        <h1 class="text-3xl font-bold flex items-center space-x-2">
            <ShoppingCart class="h-8 w-8" />
            <span>Shopping Cart</span>
            <Badge v-if="!isEmpty" variant="outline">
                {{ itemCount }} item{{ itemCount !== 1 ? 's' : '' }}
            </Badge>
        </h1>
        <Button variant="outline" as="a" href="/products" class="flex items-center space-x-2">
            <ArrowLeft class="h-4 w-4" />
            <span>Continue Shopping</span>
        </Button>
    </div>
</template>
