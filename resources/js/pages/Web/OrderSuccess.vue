<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import Navbar from '@/components/Navbar.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { CheckCircle, Package, Heart, Home, Eye, MessageCircle, Facebook } from 'lucide-vue-next';

// Props with safe defaults
const props = defineProps({
    order: { type: Object, default: () => ({ id: 0, total: 0, is_donation: false, items: [] }) },
    categories: { type: Array, default: () => [] },
    cartItems: { type: Array, default: () => [] },
    auth: { type: Object, default: () => ({ user: null }) },
    settings: { type: Object, default: () => ({ site_name: 'Elegant Store' }) },
});

const siteName = props.settings?.site_name || 'Elegant Store';
const user = props.auth?.user || null;

const formatPrice = (price) => {
    return parseFloat(price || 0).toFixed(2);
};

// Navigation functions
const goHome = () => {
    router.visit('/');
};

const viewOrders = () => {
    router.visit('/my-orders');
};

// WhatsApp link generator
const getWhatsAppLink = (phoneNumber: string) => {
    const cleanNumber = phoneNumber.replace(/\D/g, '');
    const internationalNumber = cleanNumber.startsWith('963') ? cleanNumber : `963${cleanNumber}`;
    return `https://wa.me/${internationalNumber}`;
};
</script>

<template>
    <div class="min-h-screen bg-background">
        <Head title="Payment Successful" />

        <!-- Navbar -->
       

        <div class="max-w-2xl mx-auto px-4 py-16">
            <Card class="text-center">
                <CardContent class="py-12">

                    <!-- Success Icon -->
                    <div class="w-20 h-20 bg-green-100 dark:bg-green-900/20 rounded-full flex items-center justify-center mx-auto mb-6">
                        <CheckCircle class="h-12 w-12 text-green-600 dark:text-green-400" />
                    </div>

                    <!-- Success Message -->
                    <h1 class="text-3xl font-bold text-foreground mb-3">
                        {{ order?.is_donation ? '🎉 Thank You!' : '✅ Payment Successful!' }}
                    </h1>

                    <p class="text-muted-foreground mb-6 text-lg">
                        {{ order?.is_donation
                        ? 'Your generous donation means the world to us!'
                        : 'Your order has been confirmed and payment processed.'
                        }}
                    </p>

                    <!-- ✅ NEW: Contact Information Section -->
                    <div class="mb-8 pb-6 border-b">
                        <p class="text-sm font-medium text-foreground mb-4">
                            You can contact us:
                        </p>
                        
                        <div class="max-w-lg mx-auto space-y-3">
                            <!-- WhatsApp Contacts -->
                            <div class="flex flex-col sm:flex-row items-center justify-center gap-3 text-sm">
                                <!-- Initiative -->
                                <a 
                                    :href="getWhatsAppLink('963944255208')"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="flex items-center gap-2 text-foreground hover:text-primary transition-colors"
                                >
                                    <MessageCircle class="h-4 w-4 text-green-600" />
                                    <span class="font-medium">3lmni al 9aid:</span>
                                    <span>963944255208</span>
                                </a>

                                <!-- Separator -->
                                <span class="hidden sm:inline text-muted-foreground">|</span>

                                <!-- Technical Support -->
                                <a 
                                    :href="getWhatsAppLink('963937671126')"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="flex items-center gap-2 text-foreground hover:text-primary transition-colors"
                                >
                                    <MessageCircle class="h-4 w-4 text-green-600" />
                                    <span class="font-medium">Technical Support:</span>
                                    <span>963937671126</span>
                                </a>
                            </div>

                            <!-- Facebook Link -->
                            <div class="flex items-center justify-center pt-2">
                                <a 
                                    href="https://www.facebook.com/share/1KAWdthyWQ/" 
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="inline-flex items-center gap-2 px-4 py-2 bg-muted/50 hover:bg-muted rounded-full transition-all hover:scale-105 text-sm"
                                >
                                    <Facebook class="h-4 w-4 text-blue-600" />
                                    <span class="font-medium">Follow us on Facebook</span>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Order Summary Card -->
                    <Card class="bg-muted/50 mb-8">
                        <CardContent class="py-6">
                            <div class="flex items-center justify-center mb-4">
                                <component :is="order?.is_donation ? Heart : Package" class="h-5 w-5 text-muted-foreground mr-2" />
                                <h2 class="font-semibold text-lg">
                                    {{ order?.is_donation ? 'Donation' : 'Order' }} Summary
                                </h2>
                            </div>

                            <div class="space-y-3">
                                <div class="flex justify-between items-center">
                                    <span class="text-muted-foreground">{{ order?.is_donation ? 'Donation' : 'Order' }} #</span>
                                    <Badge variant="secondary" class="font-mono">{{ order?.id || 'N/A' }}</Badge>
                                </div>

                                <div class="flex justify-between items-center">
                                    <span class="text-muted-foreground">Amount</span>
                                    <span class="text-2xl font-bold text-green-600">${{ formatPrice(order?.total) }}</span>
                                </div>

                                <div class="flex justify-between items-center">
                                    <span class="text-muted-foreground">Status</span>
                                    <Badge class="bg-green-100 text-green-700 dark:bg-green-900/20 dark:text-green-400">Paid</Badge>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Action Buttons -->
                    <div class="space-y-4">
                        <Button
                            @click="goHome"
                            size="lg"
                            class="w-full"
                        >
                            <Home class="h-4 w-4 mr-2" />
                            Continue Shopping
                        </Button>

                        <Button
                            @click="viewOrders"
                            variant="outline"
                            size="lg"
                            class="w-full"
                        >
                            <Eye class="h-4 w-4 mr-2" />
                            View {{ order?.is_donation ? 'Donations' : 'Orders' }}
                        </Button>
                    </div>

                    <!-- Thank You Message -->
                    <div class="mt-8 pt-6 border-t">
                        <p class="text-muted-foreground">
                            {{ order?.is_donation
                            ? '🙏 Your support helps us make a difference!'
                            : '🎉 Thank you for choosing us!'
                            }}
                        </p>
                    </div>

                </CardContent>
            </Card>
        </div>
    </div>
</template>
