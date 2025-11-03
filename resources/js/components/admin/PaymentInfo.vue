<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { CreditCard, Calendar, Hash } from 'lucide-vue-next';

interface Props {
    order: {
        payment_method?: string;
        payment_status: string;
        transaction_id?: string;
        payment_date?: string;
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

const getPaymentStatusColor = (status: string) => {
    const colors = {
        paid: 'bg-green-100 text-green-800',
        pending: 'bg-yellow-100 text-yellow-800',
        failed: 'bg-red-100 text-red-800',
        refunded: 'bg-gray-100 text-gray-800'
    };
    return colors[status] || 'bg-gray-100 text-gray-800';
};

const formatPaymentMethod = (method: string) => {
    const methods = {
        card: 'Credit/Debit Card',
        paypal: 'PayPal',
        apple_pay: 'Apple Pay',
        google_pay: 'Google Pay',
        bank_transfer: 'Bank Transfer'
    };
    return methods[method] || method || 'N/A';
};
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
                <Badge :class="getPaymentStatusColor(order.payment_status)" class="text-xs">
                    {{ (order.payment_status || 'pending').toUpperCase() }}
                </Badge>
            </div>

            <!-- Transaction ID -->
            <div v-if="order.transaction_id" class="flex justify-between items-center">
                <span class="text-sm font-medium">Transaction:</span>
                <div class="flex items-center space-x-2">
                    <Hash class="h-4 w-4 text-muted-foreground" />
                    <code class="text-sm font-mono bg-muted px-2 py-1 rounded">
                        {{ order.transaction_id }}
                    </code>
                </div>
            </div>

            <!-- Payment Date -->
            <div v-if="order.payment_date" class="flex justify-between items-center">
                <span class="text-sm font-medium">Payment Date:</span>
                <div class="flex items-center space-x-2">
                    <Calendar class="h-4 w-4 text-muted-foreground" />
                    <span class="text-sm">{{ formatDate(order.payment_date) }}</span>
                </div>
            </div>

            <!-- Payment Details -->
            <div class="pt-3 border-t bg-muted/20 p-3 rounded-lg">
                <div class="flex items-center justify-between text-xs text-muted-foreground">
                    <span>Payment processed securely</span>
                    <div class="flex items-center space-x-1">
                        <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                        <span>SSL Encrypted</span>
                    </div>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
