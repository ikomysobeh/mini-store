<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { ShoppingCart, ArrowLeft } from 'lucide-vue-next';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

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
            <span>{{ t('cart.title') }}</span>
            <Badge v-if="!isEmpty" variant="outline">
                {{ itemCount }} {{ t('cart.itemsInCart') }}
            </Badge>
        </h1>
        <Button variant="outline" as="a" href="/products" class="flex items-center space-x-2">
            <ArrowLeft class="h-4 w-4" />
            <span>{{ t('cart.continueShopping') }}</span>
        </Button>
    </div>
</template>
