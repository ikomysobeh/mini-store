<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { CreditCard, Shield, ShoppingCart, ArrowLeft } from 'lucide-vue-next';
import { ref, computed } from 'vue';

interface Props {
    subtotal: number;
    shipping: number;
    total: number;
    itemCount: number;
    user?: any;
    updatingItems: Set<number>;
}

interface Emits {
    (e: 'proceedToCheckout'): void;
    (e: 'applyPromoCode', code: string): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

// State
const promoCode = ref('');
const applyingPromo = ref(false);

// Computed
const freeShippingProgress = computed(() => {
    if (props.shipping === 0) return 100;
    return Math.min((props.subtotal / 50) * 100, 100);
});

const freeShippingRemaining = computed(() => {
    return Math.max(50 - props.subtotal, 0);
});

// Helper functions
const formatPrice = (price: number) => {
    return parseFloat(price.toString()).toFixed(2);
};

const applyPromoCode = () => {
    if (!promoCode.value.trim()) return;

    applyingPromo.value = true;
    emit('applyPromoCode', promoCode.value.trim());

    // Reset after a delay (will be handled by parent component)
    setTimeout(() => {
        applyingPromo.value = false;
        promoCode.value = '';
    }, 1000);
};

const proceedToCheckout = () => {
    emit('proceedToCheckout');
};
</script>

<template>
    <div class="lg:col-span-1">
        <div class="sticky top-24 space-y-6">

            <!-- Summary Card -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center space-x-2">
                        <CreditCard class="h-5 w-5" />
                        <span>Order Summary</span>
                    </CardTitle>
                </CardHeader>
                <CardContent class="space-y-4">

                    <!-- Subtotal -->
                    <div class="flex justify-between text-base">
                        <span>Subtotal ({{ itemCount }} items)</span>
                        <span class="font-medium">${{ formatPrice(subtotal) }}</span>
                    </div>


                    <Separator />

                    <!-- Total -->
                    <div class="flex justify-between text-xl font-bold">
                        <span>Total</span>
                        <span class="text-primary">${{ formatPrice(total) }}</span>
                    </div>

                    <!-- Checkout Button -->
                    <Button
                        @click="proceedToCheckout"
                        size="lg"
                        class="w-full mt-6"
                        :disabled="updatingItems.size > 0"
                    >
                        <CreditCard class="h-4 w-4 mr-2" />
                        {{ user ? 'Proceed to Checkout' : 'Login & Checkout' }}
                    </Button>

                    <p v-if="!user" class="text-xs text-center text-muted-foreground">
                        You'll be redirected to login first
                    </p>

                    <!-- Security Notice -->
                    <div class="flex items-center justify-center space-x-2 text-xs text-muted-foreground mt-4 p-3 bg-muted/50 rounded-lg">
                        <Shield class="h-3 w-3" />
                        <span>256-bit SSL Secure Checkout</span>
                    </div>
                </CardContent>
            </Card>


        </div>
    </div>
</template>
