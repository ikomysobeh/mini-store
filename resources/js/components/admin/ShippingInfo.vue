<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { MapPin, User } from 'lucide-vue-next';

interface Props {
    order: {
        shipping_name?: string;
        shipping_address?: {
            address_line_1: string;
            address_line_2?: string;
            city: string;
            state?: string;
            postal_code: string;
            country: string;
        };
        customer?: {
            user?: {
                name: string;
            };
        };
    };
}

const { order } = defineProps<Props>();

const recipientName = order.shipping_name || order.customer?.user?.name || 'Unknown Recipient';
</script>

<template>
    <Card v-if="order.shipping_address">
        <CardHeader>
            <CardTitle class="flex items-center space-x-2">
                <MapPin class="h-5 w-5" />
                <span>Shipping Address</span>
            </CardTitle>
        </CardHeader>
        <CardContent class="space-y-4">
            <!-- Recipient -->
            <div class="flex items-center space-x-2">
                <User class="h-4 w-4 text-muted-foreground" />
                <span class="font-medium">{{ recipientName }}</span>
            </div>

            <!-- Address -->
            <div class="pl-6 space-y-1 text-sm">
                <p class="font-medium">{{ order.shipping_address.address_line_1 }}</p>
                <p v-if="order.shipping_address.address_line_2">
                    {{ order.shipping_address.address_line_2 }}
                </p>
                <p>
                    {{ order.shipping_address.city }}
                    <span v-if="order.shipping_address.state">, {{ order.shipping_address.state }}</span>
                    {{ order.shipping_address.postal_code }}
                </p>
                <p class="font-medium">{{ order.shipping_address.country }}</p>
            </div>

            <!-- Google Maps Link -->
            <div class="pt-2 border-t">
                <a
                    :href="`https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(
                        `${order.shipping_address.address_line_1}, ${order.shipping_address.city}, ${order.shipping_address.country}`
                    )}`"
                    target="_blank"
                    class="text-sm text-primary hover:underline flex items-center space-x-1"
                >
                    <MapPin class="h-3 w-3" />
                    <span>View on Google Maps</span>
                </a>
            </div>
        </CardContent>
    </Card>
</template>
