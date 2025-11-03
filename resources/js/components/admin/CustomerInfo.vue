<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { User, Phone, Mail, Calendar } from 'lucide-vue-next';

interface Props {
    order: {
        customer?: {
            user?: {
                name: string;
                email: string;
                created_at: string;
            };
            phone?: string;
        };
        email?: string;
        phone?: string;
        created_at: string;
    };
    locale?: string;
}

const { order, locale = 'nl-NL' } = defineProps<Props>();

const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString(locale, {
        day: '2-digit',
        month: 'long',
        year: 'numeric'
    });
};

const customerName = order.customer?.user?.name || 'Guest Customer';
const customerEmail = order.customer?.user?.email || order.email;
const customerPhone = order.customer?.phone || order.phone;
const customerSince = order.customer?.user?.created_at || order.created_at;
const isGuest = !order.customer?.user;
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle class="flex items-center space-x-2">
                <User class="h-5 w-5" />
                <span>Customer Information</span>
            </CardTitle>
        </CardHeader>
        <CardContent class="space-y-4">
            <!-- Customer Avatar and Name -->
            <div class="flex items-center space-x-3">
                <Avatar class="h-12 w-12">
                    <AvatarFallback class="bg-primary/10 text-primary text-lg font-semibold">
                        {{ customerName.charAt(0).toUpperCase() }}
                    </AvatarFallback>
                </Avatar>
                <div class="flex-1">
                    <div class="flex items-center space-x-2">
                        <p class="font-semibold text-lg">{{ customerName }}</p>
                        <Badge v-if="isGuest" variant="secondary" class="text-xs">Guest</Badge>
                    </div>
                    <p class="text-sm text-muted-foreground">{{ customerEmail }}</p>
                </div>
            </div>

            <Separator />

            <!-- Contact Information -->
            <div class="space-y-3">
                <h4 class="font-medium text-sm">Contact Details</h4>

                <div class="space-y-2">
                    <div class="flex items-center space-x-2 text-sm">
                        <Mail class="h-4 w-4 text-muted-foreground" />
                        <span>{{ customerEmail }}</span>
                    </div>

                    <div v-if="customerPhone" class="flex items-center space-x-2 text-sm">
                        <Phone class="h-4 w-4 text-muted-foreground" />
                        <span>{{ customerPhone }}</span>
                    </div>

                    <div class="flex items-center space-x-2 text-sm">
                        <Calendar class="h-4 w-4 text-muted-foreground" />
                        <span>Customer since: {{ formatDate(customerSince) }}</span>
                    </div>
                </div>
            </div>

            <!-- Customer Stats -->
            <div v-if="!isGuest" class="pt-3 border-t bg-muted/20 p-3 rounded-lg">
                <h4 class="font-medium text-sm mb-2">Customer Stats</h4>
                <div class="grid grid-cols-2 gap-4 text-center">
                    <div>
                        <p class="text-lg font-semibold text-primary">{{ order.customer?.total_orders || 1 }}</p>
                        <p class="text-xs text-muted-foreground">Total Orders</p>
                    </div>
                    <div>
                        <p class="text-lg font-semibold text-primary">{{ order.customer?.total_spent || 0 }}</p>
                        <p class="text-xs text-muted-foreground">Total Spent</p>
                    </div>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
