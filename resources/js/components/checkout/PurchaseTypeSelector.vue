<script setup lang="ts">
import { Card, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { ShoppingCart, Heart, Gift } from 'lucide-vue-next';
import { useI18n } from 'vue-i18n';

const { t, locale } = useI18n();

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
    return new Intl.NumberFormat(locale.value === 'ar' ? 'ar-SY' : 'en-US', {
        style: 'currency',
        currency: 'USD'
    }).format(amount);
};
</script>

<template>
    <div class="space-y-4">
        <h3 class="text-lg font-semibold">{{ t('checkout.choosePaymentType') }}</h3>

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
                            selectedType === 'purchase' ? 'bg-primary text-primary-foreground' : 'bg-primary/20 text-primary'
                        ]">
                            <ShoppingCart class="h-5 w-5" />
                        </div>
                        <div>
                            <h4 class="font-semibold ">{{ t('checkout.purchase') }}</h4>
                            <Badge v-if="selectedType === 'purchase'" class="mt-1 bg-primary">{{ t('checkout.selected') }}</Badge>
                        </div>
                    </div>
                    <p class="text-sm text-muted-foreground mb-3">
                        {{ t('checkout.purchaseDesc') }}
                    </p>
                    <div class="text-lg font-bold">{{ formatPrice(totalAmount) }}</div>
                    <p class="text-xs text-muted-foreground mt-1">{{ t('checkout.plusShipping') }}</p>
                </CardContent>
            </Card>

            <!-- Donation Option -->
            <Card
                :class="[
                    'cursor-pointer transition-all border-2',
                    selectedType === 'donation'
                        ? 'border-destructive bg-destructive/10'
                        : hasDonationItems
                            ? 'border-muted hover:border-destructive/50'
                            : 'border-muted opacity-50 cursor-not-allowed'
                ]"
                @click="hasDonationItems && emit('update:selectedType', 'donation')"
            >
                <CardContent class="p-6">
                    <div class="flex items-center space-x-3 mb-3">
                        <div :class="[
                            'p-2 rounded-lg',
                            selectedType === 'donation' ? 'bg-destructive text-destructive-foreground' : 'bg-destructive/20 text-destructive'
                        ]">
                            <Heart class="h-5 w-5" />
                        </div>
                        <div>
                            <h4 class="font-semibold">{{ t('checkout.donation') }}</h4>
                            <Badge v-if="selectedType === 'donation'" variant="destructive" class="mt-1">{{ t('checkout.selected') }}</Badge>
                            <Badge v-else-if="!hasDonationItems" variant="outline" class="mt-1">{{ t('checkout.notAvailable') }}</Badge>
                        </div>
                    </div>
                    <p class="text-sm text-muted-foreground mb-3">
                        {{ hasDonationItems
                        ? t('checkout.donationDesc')
                        : t('checkout.noDonationItems')
                        }}
                    </p>
                    <div class="text-lg font-bold">{{ formatPrice(totalAmount) }}</div>
                    <p class="text-xs text-muted-foreground mt-1">
                        {{ hasDonationItems ? t('checkout.noAdditionalFees') : t('checkout.addDonatableItems') }}
                    </p>
                </CardContent>
            </Card>
        </div>

        <!-- Info Box -->
        <div v-if="selectedType === 'donation'" class="bg-warning/10 border border-warning/20 rounded-lg p-4">
            <div class="flex items-start space-x-3">
                <Gift class="h-5 w-5 text-warning mt-0.5" />
                <div class="text-sm text-warning">
                    <p class="font-medium">{{ t('checkout.donationModeBenefits') }}</p>
                    <ul class="list-disc list-inside mt-1 space-y-1">
                        <li>{{ t('checkout.taxExemptContribution') }}</li>
                        <li>{{ t('checkout.noShippingCosts') }}</li>
                        <li>{{ t('checkout.supportingCause') }}</li>
                        <li>{{ t('checkout.emailReceiptTax') }}</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</template>
