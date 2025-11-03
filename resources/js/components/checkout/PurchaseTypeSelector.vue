<script setup lang="ts">
import { Card, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { ShoppingCart, Heart, Gift } from 'lucide-vue-next';

interface Props {
    selectedType: 'purchase' | 'donation';
    hasDonationItems: boolean;
    totalAmount: number;
    currency?: string;
}

interface Emits {
    (e: 'update:selectedType', value: 'purchase' | 'donation'): void;
}

const props = withDefaults(defineProps<Props>(), {
    currency: '$'
});

const emit = defineEmits<Emits>();

const formatPrice = (amount: number) => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD'
    }).format(amount);
};
</script>

<template>
    <div class="space-y-4">
        <h3 class="text-lg font-semibold">Choose Payment Type</h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

            <!-- Purchase Option -->
            <Card
                :class="[
                    'cursor-pointer transition-all border-2',
                    selectedType === 'purchase'
                        ? 'border-primary bg-primary/5'
                        : 'border-muted hover:border-primary/50'
                ]"
                @click="emit('update:selectedType', 'purchase')"
            >
                <CardContent class="p-6">
                    <div class="flex items-center space-x-3 mb-3">
                        <div :class="[
                            'p-2 rounded-lg',
                            selectedType === 'purchase' ? 'bg-primary text-primary-foreground' : 'bg-blue-100 text-blue-600'
                        ]">
                            <ShoppingCart class="h-5 w-5" />
                        </div>
                        <div>
                            <h4 class="font-semibold">Purchase</h4>
                            <Badge v-if="selectedType === 'purchase'" class="mt-1">Selected</Badge>
                        </div>
                    </div>
                    <p class="text-sm text-muted-foreground mb-3">
                        Buy products with standard pricing and shipping
                    </p>
                    <div class="text-lg font-bold">{{ formatPrice(totalAmount) }}</div>
                    <p class="text-xs text-muted-foreground mt-1">+ shipping costs</p>
                </CardContent>
            </Card>

            <!-- Donation Option -->
            <Card
                :class="[
                    'cursor-pointer transition-all border-2',
                    selectedType === 'donation'
                        ? 'border-red-500 bg-red-50'
                        : hasDonationItems
                            ? 'border-muted hover:border-red-300'
                            : 'border-muted opacity-50 cursor-not-allowed'
                ]"
                @click="hasDonationItems && emit('update:selectedType', 'donation')"
            >
                <CardContent class="p-6">
                    <div class="flex items-center space-x-3 mb-3">
                        <div :class="[
                            'p-2 rounded-lg',
                            selectedType === 'donation' ? 'bg-red-500 text-white' : 'bg-red-100 text-red-600'
                        ]">
                            <Heart class="h-5 w-5" />
                        </div>
                        <div>
                            <h4 class="font-semibold">Donation</h4>
                            <Badge v-if="selectedType === 'donation'" class="mt-1 bg-red-100 text-red-800">Selected</Badge>
                            <Badge v-else-if="!hasDonationItems" variant="outline" class="mt-1">Not Available</Badge>
                        </div>
                    </div>
                    <p class="text-sm text-muted-foreground mb-3">
                        {{ hasDonationItems
                        ? 'Support our cause - tax exempt, no shipping'
                        : 'No donatable items in cart'
                        }}
                    </p>
                    <div class="text-lg font-bold">{{ formatPrice(totalAmount) }}</div>
                    <p class="text-xs text-muted-foreground mt-1">
                        {{ hasDonationItems ? 'No additional fees' : 'Add donatable items' }}
                    </p>
                </CardContent>
            </Card>
        </div>

        <!-- Info Box -->
        <div v-if="selectedType === 'donation'" class="bg-orange-50 border border-orange-200 rounded-lg p-4">
            <div class="flex items-start space-x-3">
                <Gift class="h-5 w-5 text-orange-600 mt-0.5" />
                <div class="text-sm text-orange-700">
                    <p class="font-medium">Donation Mode Benefits:</p>
                    <ul class="list-disc list-inside mt-1 space-y-1">
                        <li>Tax-exempt contribution</li>
                        <li>No shipping costs</li>
                        <li>Supporting a good cause</li>
                        <li>Email receipt for tax purposes</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</template>
