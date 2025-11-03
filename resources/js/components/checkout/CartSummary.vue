<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import { Heart, ShoppingCart, Truck } from 'lucide-vue-next';

interface Props {
    items: any[];
    shippingCost: number;
    isDonation: boolean;
    total: number;
}

const props = defineProps<Props>();

const formatPrice = (amount: number) => {
    return new Intl.NumberFormat('en-US', {
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
                <span>{{ isDonation ? 'Donation' : 'Order' }} Summary</span>
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
                            <p class="text-xs text-muted-foreground">Qty: {{ item.quantity }}</p>
                            <Badge v-if="isDonation && item.product.is_donatable"
                                   class="bg-red-100 text-red-800 text-xs mt-1">
                                <Heart class="h-3 w-3 mr-1" />
                                Donation Item
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
                    <span>Subtotal</span>
                    <span>{{ formatPrice(subtotal) }}</span>
                </div>

                <div v-if="!isDonation" class="flex justify-between text-sm">
                    <span class="flex items-center space-x-1">
                        <Truck class="h-3 w-3" />
                        <span>Shipping</span>
                    </span>
                    <span>{{ shippingCost === 0 ? 'FREE' : formatPrice(shippingCost) }}</span>
                </div>

                <div v-if="isDonation" class="flex justify-between text-sm text-green-600">
                    <span>Tax Benefit</span>
                    <span>Tax Exempt</span>
                </div>

                <Separator />

                <div class="flex justify-between text-lg font-bold">
                    <span>Total</span>
                    <span>{{ formatPrice(total) }}</span>
                </div>
            </div>

            <!-- Donation Notice -->
            <div v-if="isDonation" class="bg-red-50 border border-red-200 rounded-lg p-3 text-sm text-red-700">
                <p class="font-medium mb-1">🎁 Thank you for your donation!</p>
                <p class="text-xs">You'll receive a tax-exempt receipt via email.</p>
            </div>

        </CardContent>
    </Card>
</template>
