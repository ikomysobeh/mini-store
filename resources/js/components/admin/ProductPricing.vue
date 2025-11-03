<script setup lang="ts">
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { computed } from 'vue';

interface Props {
    price: string;
    originalPrice: string;
    sku: string;
    stock: string;
    currency?: string;
    errors: Record<string, string>;
}

interface Emits {
    (e: 'update:price', value: string): void;
    (e: 'update:originalPrice', value: string): void;
    (e: 'update:sku', value: string): void;
    (e: 'update:stock', value: string): void;
}

const props = withDefaults(defineProps<Props>(), {
    currency: '$'
});

const emit = defineEmits<Emits>();

// Computed properties
const isOnSale = computed(() => {
    return props.originalPrice &&
        parseFloat(props.originalPrice) > parseFloat(props.price || '0');
});

const discountPercentage = computed(() => {
    if (!isOnSale.value) return 0;
    const original = parseFloat(props.originalPrice);
    const current = parseFloat(props.price || '0');
    return Math.round(((original - current) / original) * 100);
});

const isLowStock = computed(() => {
    const stockNum = parseInt(props.stock || '0');
    return stockNum > 0 && stockNum < 10;
});
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle>Pricing & Inventory</CardTitle>
        </CardHeader>
        <CardContent class="space-y-6">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Price -->
                <div class="space-y-2">
                    <Label for="price">
                        Price <span class="text-destructive">*</span>
                    </Label>
                    <div class="relative">
                        <span class="absolute left-3 top-3 text-muted-foreground">{{ currency }}</span>
                        <Input
                            id="price"
                            :model-value="price"
                            @update:model-value="emit('update:price', $event)"
                            type="number"
                            step="0.01"
                            min="0"
                            placeholder="0.00"
                            class="pl-8"
                            :class="{ 'border-destructive': errors.price }"
                        />
                    </div>
                    <p v-if="errors.price" class="text-sm text-destructive">
                        {{ errors.price }}
                    </p>
                </div>

                <!-- Original Price (Sale) -->
                <div class="space-y-2">
                    <Label for="original_price">Original Price (Optional)</Label>
                    <div class="relative">
                        <span class="absolute left-3 top-3 text-muted-foreground">{{ currency }}</span>
                        <Input
                            id="original_price"
                            :model-value="originalPrice"
                            @update:model-value="emit('update:originalPrice', $event)"
                            type="number"
                            step="0.01"
                            min="0"
                            placeholder="0.00"
                            class="pl-8"
                        />
                    </div>
                    <div v-if="isOnSale" class="flex items-center space-x-2">
                        <Badge variant="secondary" class="bg-green-100 text-green-800">
                            {{ discountPercentage }}% OFF
                        </Badge>
                        <span class="text-xs text-green-600">Sale price active</span>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- SKU -->
                <div class="space-y-2">
                    <Label for="sku">SKU (Stock Keeping Unit)</Label>
                    <Input
                        id="sku"
                        :model-value="sku"
                        @update:model-value="emit('update:sku', $event)"
                        placeholder="PRODUCT-001"
                        class="font-mono"
                    />
                </div>

                <!-- Stock -->
                <div class="space-y-2">
                    <Label for="stock">Stock Quantity</Label>
                    <Input
                        id="stock"
                        :model-value="stock"
                        @update:model-value="emit('update:stock', $event)"
                        type="number"
                        min="0"
                        placeholder="0"
                    />
                    <p v-if="isLowStock" class="text-xs text-orange-600 flex items-center">
                        ⚠️ Low stock warning will be shown to customers
                    </p>
                </div>
            </div>

        </CardContent>
    </Card>
</template>
