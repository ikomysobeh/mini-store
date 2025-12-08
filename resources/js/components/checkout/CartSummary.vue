<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import { Heart, ShoppingCart, Truck } from 'lucide-vue-next';
import { useI18n } from 'vue-i18n';

const { t, locale } = useI18n();

interface Props {
    items: any[];
    shippingCost: number;
    isDonation: boolean;
    total: number;
}

const props = defineProps<Props>();

const formatPrice = (amount: number) => {
    return new Intl.NumberFormat(locale.value === 'ar' ? 'ar-SY' : 'en-US', {
        style: 'currency',
        currency: 'USD'
    }).format(amount);
};

const subtotal = props.items.reduce((sum, item) => sum + (item.price * item.quantity), 0);
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle class="flex items-center space-x-2">
                <component :is="isDonation ? Heart : ShoppingCart" class="h-5 w-5" />
                <span>{{ isDonation ? t('checkout.donationSummary') : t('checkout.orderSummary') }}</span>
            </CardTitle>
        </CardHeader>
        <CardContent class="space-y-4">

            <!-- Cart Items -->
            <div class="space-y-3">
                <div v-for="item in items" :key="item.id" class="flex justify-between items-center">
                    <div class="flex items-center space-x-3">
                        <img
                            v-if="item.product.image"
                            :src="item.product.image"
                            :alt="item.product.name"
                            class="w-12 h-12 object-cover rounded border"
                        />
                        <div class="flex-1">
                            <p class="text-sm font-medium">{{ item.product.name }}</p>
                            <p class="text-xs text-muted-foreground">{{ t('checkout.qty') }}: {{ item.quantity }}</p>
                            <Badge v-if="isDonation && item.product.is_donatable"
                                   variant="destructive" class="text-xs mt-1">
                                <Heart class="h-3 w-3 mr-1" />
                                {{ t('cart.donationItem') }}
                            </Badge>
                        </div>
                    </div>
                    <div class="text-sm font-medium">
                        {{ formatPrice(item.price * item.quantity) }}
                    </div>
                </div>
            </div>

            <Separator />

            <!-- Summary Totals -->
            <div class="space-y-2">
                <div class="flex justify-between text-sm">
                    <span>{{ t('checkout.subtotal') }}</span>
                    <span>{{ formatPrice(subtotal) }}</span>
                </div>

                <div v-if="!isDonation" class="flex justify-between text-sm">
                    <span class="flex items-center space-x-1">
                        <Truck class="h-3 w-3" />
                        <span>{{ t('cart.shipping') }}</span>
                    </span>
                    <span>{{ shippingCost === 0 ? t('checkout.free') : formatPrice(shippingCost) }}</span>
                </div>

                <div v-if="isDonation" class="flex justify-between text-sm text-primary">
                    <span>{{ t('checkout.taxBenefit') }}</span>
                    <span>{{ t('checkout.taxExempt') }}</span>
                </div>

                <Separator />

                <div class="flex justify-between text-lg font-bold">
                    <span>{{ t('cart.total') }}</span>
                    <span>{{ formatPrice(total) }}</span>
                </div>
            </div>

            <!-- Donation Notice -->
            <div v-if="isDonation" class="bg-destructive/10 border border-destructive/20 rounded-lg p-3 text-sm text-destructive">
                <p class="font-medium mb-1">🎁 {{ t('checkout.donationThankYou') }}</p>
                <p class="text-xs text-destructive/90">{{ t('checkout.taxReceiptNote') }}</p>
            </div>

        </CardContent>
    </Card>
</template>
