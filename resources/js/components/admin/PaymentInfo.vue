<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { CreditCard, Calendar } from 'lucide-vue-next';
import { computed } from 'vue';

interface Props {
    order: {
        payment_method?: string;
        payment_date?: string;
        paid_at?: string; // ✅ New field from webhook
        status: string; // ✅ Order status
    };
    locale?: string;
}

const { order, locale = 'nl-NL' } = defineProps<Props>();

const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString(locale, {
        day: '2-digit',
        month: 'long',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

// ✅ Derive payment status from order status
const paymentStatus = computed(() => {
    if (order.paid_at) {
        return 'paid';
    }
    
    switch (order.status) {
        case 'success':
        case 'done':
            return 'paid';
        case 'failed':
            return 'failed';
        case 'processing':
            return 'processing';
        case 'pending':
        default:
            return 'pending';
    }
});

const getPaymentStatusColor = (status: string) => {
    const colors: Record<string, string> = {
        paid: 'bg-green-100 text-green-800',
        pending: 'bg-yellow-100 text-yellow-800',
        processing: 'bg-blue-100 text-blue-800',
        failed: 'bg-red-100 text-red-800',
        refunded: 'bg-gray-100 text-gray-800'
    };
    return colors[status] || 'bg-gray-100 text-gray-800';
};

const formatPaymentMethod = (method?: string) => {
    const methods: Record<string, string> = {
        stripe: 'Stripe',
        card: 'Credit/Debit Card',
        paypal: 'PayPal',
        apple_pay: 'Apple Pay',
        google_pay: 'Google Pay',
        bank_transfer: 'Bank Transfer'
    };
    return methods[method || ''] || method || 'N/A';
};

// ✅ Use paid_at if available, otherwise payment_date
const displayDate = computed(() => {
    return order.paid_at || order.payment_date;
});
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle class="flex items-center space-x-2">
                <CreditCard class="h-5 w-5" />
                <span>Payment Information</span>
            </CardTitle>
        </CardHeader>
        <CardContent class="space-y-4">

            <!-- Payment Method -->
            <div class="flex justify-between items-center">
                <span class="text-sm font-medium">Payment Method:</span>
                <div class="flex items-center space-x-2">
                    <CreditCard class="h-4 w-4 text-muted-foreground" />
                    <span class="text-sm">{{ formatPaymentMethod(order.payment_method) }}</span>
                </div>
            </div>

            <!-- Payment Status -->
            <div class="flex justify-between items-center">
                <span class="text-sm font-medium">Status:</span>
                <Badge :class="getPaymentStatusColor(paymentStatus)" class="text-xs">
                    {{ paymentStatus.toUpperCase() }}
                </Badge>
            </div>

            <!-- Payment Date -->
            <div v-if="displayDate" class="flex justify-between items-center">
                <span class="text-sm font-medium">Payment Date:</span>
                <div class="flex items-center space-x-2">
                    <Calendar class="h-4 w-4 text-muted-foreground" />
                    <span class="text-sm">{{ formatDate(displayDate) }}</span>
                </div>
            </div>

            <!-- Payment Details -->
            <div class="pt-3 border-t bg-muted/20 p-3 rounded-lg">
                <div class="flex items-center justify-between text-xs text-muted-foreground">
                    <span>Payment processed securely</span>
                    <div class="flex items-center space-x-1">
                        <div 
                            class="w-2 h-2 rounded-full"
                            :class="{
                                'bg-green-500': paymentStatus === 'paid',
                                'bg-yellow-500': paymentStatus === 'pending',
                                'bg-blue-500': paymentStatus === 'processing',
                                'bg-red-500': paymentStatus === 'failed'
                            }"
                        ></div>
                        <span>SSL Encrypted</span>
                    </div>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
