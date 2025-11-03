<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Package } from 'lucide-vue-next';
import { computed } from 'vue';

interface Props {
    name: string;
    shortDescription?: string;
    price: string;
    originalPrice?: string;
    imagePreview?: string | null;
    currency?: string;
    locale?: string;
    title?: string;
}

const props = withDefaults(defineProps<Props>(), {
    currency: 'EUR',
    locale: 'nl-NL',
    title: 'Preview'
});

const formatPrice = (price: string | number) => {
    const numPrice = typeof price === 'string' ? parseFloat(price) : price;
    return new Intl.NumberFormat(props.locale, {
        style: 'currency',
        currency: props.currency
    }).format(numPrice || 0);
};

const isOnSale = computed(() => {
    return props.originalPrice &&
        parseFloat(props.originalPrice) > parseFloat(props.price || '0');
});

const discountPercentage = computed(() => {
    if (!isOnSale.value) return 0;
    const original = parseFloat(props.originalPrice || '0');
    const current = parseFloat(props.price || '0');
    return Math.round(((original - current) / original) * 100);
});
</script>

<template>
    <Card v-if="name">
        <CardHeader>
            <CardTitle class="text-lg flex items-center space-x-2">
                <Package class="h-5 w-5" />
                <span>{{ title }}</span>
            </CardTitle>
        </CardHeader>
        <CardContent class="space-y-4">

            <!-- Product Image -->
            <div class="aspect-square relative">
                <img
                    v-if="imagePreview"
                    :src="imagePreview"
                    :alt="`${name} preview`"
                    class="w-full h-full object-cover rounded-lg border"
                />
                <div
                    v-else
                    class="w-full h-full bg-muted rounded-lg border-2 border-dashed border-muted-foreground/25 flex items-center justify-center"
                >
                    <div class="text-center">
                        <Package class="h-12 w-12 text-muted-foreground mx-auto mb-2" />
                        <p class="text-sm text-muted-foreground">No image</p>
                    </div>
                </div>

                <!-- Sale Badge -->
                <div v-if="isOnSale" class="absolute top-2 left-2">
                    <Badge class="bg-red-500 text-white">
                        {{ discountPercentage }}% OFF
                    </Badge>
                </div>
            </div>

            <!-- Product Details -->
            <div class="space-y-3">
                <h3 class="font-semibold text-lg leading-tight">{{ name }}</h3>

                <p v-if="shortDescription" class="text-sm text-muted-foreground leading-relaxed">
                    {{ shortDescription }}
                </p>

                <!-- Price Display -->
                <div class="flex items-center space-x-2">
                    <span class="text-xl font-bold text-primary">
                        {{ formatPrice(price) }}
                    </span>
                    <span
                        v-if="isOnSale"
                        class="text-sm text-muted-foreground line-through"
                    >
                        {{ formatPrice(originalPrice) }}
                    </span>
                </div>

                <!-- Additional Info -->
                <div class="text-xs text-muted-foreground">
                    This is how your product will appear to customers
                </div>
            </div>

        </CardContent>
    </Card>
</template>
