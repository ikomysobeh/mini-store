<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { Badge } from '@/components/ui/badge';
import { Calculator } from 'lucide-vue-next';

interface Props {
    order: {
        subtotal: number;
        shipping_cost?: number;
        tax?: number;
        discount?: number;
        total: number;
        is_donation: boolean;
    };
    currency?: string;
    locale?: string;
}

const { order, currency = 'EUR', locale = 'nl-NL' } = defineProps<Props>();

const formatPrice = (price: number) => {
    return new Intl.NumberFormat(locale, {
        style: 'currency',
        currency: currency
    }).format(price || 0);
};
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle class="flex items-center space-x-2">
                <Calculator class="h-5 w-5" />
                <span>Order Summary</span>
            </CardTitle>
        </CardHeader>
        <CardContent class="space-y-4">
            <div class="space-y-3">
                <!-- Subtotal -->
                <div class="flex justify-between text-sm">
                    <span>Subtotal</span>
                    <span class="font-medium">{{ formatPrice(order.subtotal) }}</span>
                </div>

                <!-- Shipping -->
                <div v-if="order.shipping_cost" class="flex justify-between text-sm">
                    <span>Shipping</span>
                    <span class="font-medium">{{ formatPrice(order.shipping_cost) }}</span>
                </div>

                <!-- Tax -->
                <div v-if="order.tax" class="flex justify-between text-sm">
                    <span>Tax (VAT)</span>
                    <span class="font-medium">{{ formatPrice(order.tax) }}</span>
                </div>

                <!-- Discount -->
                <div v-if="order.discount" class="flex justify-between text-sm text-green-600">
                    <span>Discount</span>
                    <span class="font-medium">-{{ formatPrice(order.discount) }}</span>
                </div>

                <!-- Donation Notice -->
                <div v-if="order.is_donation" class="text-xs text-orange-600 bg-orange-50 p-3 rounded-lg border border-orange-200">
                    <div class="flex items-center space-x-1">
                        <span>💝</span>
                        <span class="font-medium">Tax-exempt donation</span>
                    </div>
                    <p class="mt-1">This order qualifies as a charitable donation and is tax-exempt.</p>
                </div>

                <Separator />

                <!-- Total -->
                <div class="flex justify-between text-lg font-bold">
                    <span>Total</span>
                    <span class="text-primary">{{ formatPrice(order.total) }}</span>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
