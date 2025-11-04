<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { CreditCard, Heart, ShoppingCart, User, MessageSquare } from 'lucide-vue-next';
import { ref, computed } from 'vue';

interface CartItem {
    id: number;
    quantity: number;
    price: number;
    product?: {
        id: number;
        name: string;
        price: number;
        image?: string;
    };
    // Fallback properties if product is not loaded
    product_name?: string;
    product_id?: number;
}

interface Customer {
    first_name?: string;
    last_name?: string;
    phone?: string;
    address?: string;
}

interface Props {
    cartItems: CartItem[];
    customer?: Customer | null;
    user?: any;
    shippingMethods?: any[];
    paymentMethods?: any[];
}

const props = withDefaults(defineProps<Props>(), {
    customer: null,
    user: null,
    shippingMethods: () => [],
    paymentMethods: () => []
});

// State
const isProcessing = ref(false);
const purchaseType = ref<'purchase' | 'donation'>('purchase');
const formData = ref({
    first_name: props.customer?.first_name || '',
    last_name: props.customer?.last_name || '',
    phone: props.customer?.phone || '',
    address: props.customer?.address || '',
    notes: ''
});

// Computed
const totalAmount = computed(() => {
    return props.cartItems.reduce((sum, item) => sum + (item.price * item.quantity), 0);
});

const formatPrice = (amount: number) => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD'
    }).format(amount);
};

// Helper function to get product name safely
const getProductName = (item: CartItem) => {
    return item.product?.name || item.product_name || 'Product';
};

// Helper function to get product image safely
const getProductImage = (item: CartItem) => {
    return item.product?.image || null;
};

// Validation
const isFormValid = computed(() => {
    return formData.value.first_name &&
        formData.value.last_name &&
        (purchaseType.value === 'donation' || formData.value.address);
});

// Handle form submission
const completeOrder = async () => {
    if (isProcessing.value || !isFormValid.value) return;

    isProcessing.value = true;

    try {
        // Better CSRF token retrieval
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        if (!csrfToken) {
            throw new Error('CSRF token not found. Please refresh the page.');
        }

        const response = await fetch('/orders', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json', // Important for Laravel
            },
            credentials: 'same-origin', // Include cookies
            body: JSON.stringify({
                is_donation: purchaseType.value === 'donation',
                first_name: formData.value.first_name,
                last_name: formData.value.last_name,
                phone: formData.value.phone,
                address: formData.value.address,
                notes: formData.value.notes,
            }),
        });

        if (!response.ok) {
            // Get error details
            const errorData = await response.text();
            console.error('Response error:', response.status, errorData);
            throw new Error(`Server error: ${response.status}`);
        }

        const data = await response.json();

        if (data.url) {
            // Redirect to Stripe Checkout
            window.location.href = data.url;
        } else {
            throw new Error('No checkout URL received');
        }

    } catch (error) {
        console.error('Order creation error:', error);
        alert(`Failed to create order: ${error.message}`);
    } finally {
        isProcessing.value = false;
    }
};
</script>

<template>
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

        <!-- Main Form -->
        <div class="lg:col-span-2 space-y-8">

            <!-- Purchase Type Selection -->
            <Card>
                <CardHeader>
                    <CardTitle class="text-center text-xl">Choose Your Option</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                        <!-- Purchase Option -->
                        <label
                            :class="[
                'relative cursor-pointer rounded-xl border-2 p-6 focus:outline-none transition-all hover:shadow-md',
                purchaseType === 'purchase'
                    ? 'border-primary bg-primary shadow-md'
                    : 'border-border hover:border-primary/50 bg-background'
            ]"
                        >
                            <input
                                type="radio"
                                value="purchase"
                                v-model="purchaseType"
                                class="sr-only"
                            />

                            <div class="flex flex-col items-center text-center space-y-4">
                                <div :class="[
                    'p-4 rounded-full',
                    purchaseType === 'purchase' ? 'bg-primary' : 'bg-primary/20'
                ]">
                                    <ShoppingCart :class="[
                        'h-8 w-8',
                        purchaseType === 'purchase' ? 'text-primary-foreground' : 'text-primary'
                    ]" />
                                </div>
                                <div>
                                    <h3 :class="[
                        'font-bold text-lg transition-colors',
                        purchaseType === 'purchase' ? 'text-primary-foreground' : 'text-foreground'
                    ]">
                                        Purchase Items
                                    </h3>
                                    <p :class="[
                        'text-sm transition-colors',
                        purchaseType === 'purchase' ? 'text-primary-foreground/90' : 'text-muted-foreground'
                    ]">
                                        Get the actual products delivered
                                    </p>
                                </div>
                            </div>

                            <div :class="[
                'mt-4 space-y-1 text-sm transition-colors',
                purchaseType === 'purchase' ? 'text-yellow-100' : 'text-gray-600'
            ]">
                                <p>✓ Admin will contact you</p>
                                <p>✓ Free delivery arrangement</p>
                                <p>✓ Address required</p>
                            </div>

                            <div v-if="purchaseType === 'purchase'" class="absolute top-3 right-3">
                                <Badge variant="destructive">Selected ✓</Badge>
                            </div>
                        </label>

                        <!-- Donation Option -->
                        <label
                            :class="[
                'relative cursor-pointer rounded-xl border-2 p-6 focus:outline-none transition-all hover:shadow-md',
                purchaseType === 'donation'
                    ? 'border-destructive bg-destructive shadow-md'
                    : 'border-border hover:border-destructive/50 bg-background'
            ]"
                        >
                            <input
                                type="radio"
                                value="donation"
                                v-model="purchaseType"
                                class="sr-only"
                            />

                            <div class="flex flex-col items-center text-center space-y-4">
                                <div :class="[
                    'p-4 rounded-full',
                    purchaseType === 'donation' ? 'bg-destructive' : 'bg-destructive/20'
                ]">
                                    <Heart :class="[
                        'h-8 w-8',
                        purchaseType === 'donation' ? 'text-destructive-foreground' : 'text-destructive'
                    ]" />
                                </div>
                                <div>
                                    <h3 :class="[
                        'font-bold text-lg transition-colors',
                        purchaseType === 'donation' ? 'text-destructive-foreground' : 'text-foreground'
                    ]">
                                        Donate Amount
                                    </h3>
                                    <p :class="[
                        'text-sm transition-colors',
                        purchaseType === 'donation' ? 'text-yellow-100' : 'text-gray-600'
                    ]">
                                        Support our cause financially
                                    </p>
                                </div>
                            </div>

                            <div :class="[
                'mt-4 space-y-1 text-sm transition-colors',
                purchaseType === 'donation' ? 'text-yellow-100' : 'text-gray-600'
            ]">
                                <p>❤️ Support our mission</p>
                                <p>📧 Tax-exempt receipt</p>
                                <p>🎯 100% goes to cause</p>
                            </div>

                            <div v-if="purchaseType === 'donation'" class="absolute top-3 right-3">
                                <Badge variant="destructive">Selected ✓</Badge>
                            </div>
                        </label>
                    </div>
                </CardContent>
            </Card>

            <!-- Customer Information -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center space-x-2">
                        <User class="h-5 w-5" />
                        <span>{{ purchaseType === 'donation' ? 'Donor' : 'Customer' }} Information</span>
                    </CardTitle>
                </CardHeader>
                <CardContent class="space-y-4">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <Label for="first_name">First Name *</Label>
                            <Input
                                id="first_name"
                                v-model="formData.first_name"
                                required
                                placeholder="Enter your first name"
                            />
                        </div>

                        <div class="space-y-2">
                            <Label for="last_name">Last Name *</Label>
                            <Input
                                id="last_name"
                                v-model="formData.last_name"
                                required
                                placeholder="Enter your last name"
                            />
                        </div>
                    </div>

                    <div class="space-y-2">
                        <Label for="phone">Phone Number</Label>
                        <Input
                            id="phone"
                            v-model="formData.phone"
                            type="tel"
                            placeholder="Your contact number"
                        />
                    </div>

                    <div v-if="purchaseType === 'purchase'" class="space-y-2">
                        <Label for="address">Delivery Address *</Label>
                        <textarea
                            id="address"
                            v-model="formData.address"
                            required
                            rows="3"
                            placeholder="Your address in Suwayda (neighborhood like Al-Nahdah, Al-Shuhada, street & building number)"
                            class="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                        ></textarea>
                        <p class="text-sm text-primary flex items-center">
                            📞 Our admin will contact you to coordinate the delivery
                        </p>
                    </div>

                    <div class="space-y-2">
                        <Label for="notes" class="flex items-center space-x-2">
                            <MessageSquare class="h-4 w-4" />
                            <span>Additional Notes (Optional)</span>
                        </Label>
                        <textarea
                            id="notes"
                            v-model="formData.notes"
                            rows="2"
                            :placeholder="purchaseType === 'donation'
                                ? 'Any message or special instructions...'
                                : 'Delivery instructions, preferred time, etc...'"
                            class="flex min-h-[60px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                        ></textarea>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Order Summary -->
        <div class="lg:col-span-1">
            <div class="sticky top-24 space-y-6">

                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center space-x-2">
                            <component :is="purchaseType === 'donation' ? Heart : ShoppingCart" class="h-5 w-5" />
                            <span>{{ purchaseType === 'donation' ? 'Donation' : 'Order' }} Summary</span>
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">

                        <!-- Items -->
                        <div class="space-y-3">
                            <div v-for="item in cartItems" :key="item.id" class="flex justify-between items-center text-sm">
                                <div class="flex items-center space-x-3">
                                    <img
                                        v-if="getProductImage(item)"
                                        :src="getProductImage(item)"
                                        :alt="getProductName(item)"
                                        class="w-12 h-12 object-cover rounded border"
                                    />
                                    <div v-else class="w-12 h-12 bg-muted rounded flex items-center justify-center border">
                                        <ShoppingCart class="h-5 w-5 text-muted-foreground" />
                                    </div>
                                    <div>
                                        <p class="font-medium">{{ getProductName(item) }}</p>
                                        <p class="text-muted-foreground">Qty: {{ item.quantity }}</p>
                                    </div>
                                </div>
                                <p class="font-bold">{{ formatPrice(item.price * item.quantity) }}</p>
                            </div>
                        </div>

                        <div class="border-t pt-4">
                            <div class="flex justify-between text-xl font-bold">
                                <span>{{ purchaseType === 'donation' ? 'Donation Amount' : 'Total' }}:</span>
                                <span>{{ formatPrice(totalAmount) }}</span>
                            </div>
                        </div>

                        <!-- Special Message -->
                        <div v-if="purchaseType === 'donation'" class="bg-destructive/10 border border-destructive/20 rounded-lg p-4 text-center">
                            <p class="text-sm text-destructive font-medium">🙏 Thank you for supporting our cause!</p>
                            <p class="text-xs text-destructive/90 mt-1">Tax-exempt receipt will be emailed to you</p>
                        </div>

                        <div v-else class="bg-primary/10 border border-primary/20 rounded-lg p-4 text-center">
                            <p class="text-xs text-primary/90 mt-1">Admin will contact you within 24-48 hours</p>
                        </div>

                        <!-- Complete Order Button -->
                        <Button
                            @click="completeOrder"
                            :disabled="isProcessing || !isFormValid"
                            class="w-full h-14 text-lg font-bold"
                            :class="purchaseType === 'donation'
        ? 'bg-red-600 hover:bg-red-700 text-white disabled:bg-red-400'
        : 'bg-blue-600 hover:bg-blue-700 text-white disabled:bg-blue-400'
    "
                        >
                            <!-- Loading Spinner - Shows when processing -->
                            <div v-if="isProcessing" class="flex items-center">
                                <svg
                                    class="animate-spin -ml-1 mr-3 h-6 w-6 text-white"
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                >
                                    <circle
                                        class="opacity-25"
                                        cx="12"
                                        cy="12"
                                        r="10"
                                        stroke="currentColor"
                                        stroke-width="4"
                                    ></circle>
                                    <path
                                        class="opacity-75"
                                        fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                                    ></path>
                                </svg>
                                <span>Processing...</span>
                            </div>

                            <!-- Normal State - Shows when not processing -->
                            <div v-else class="flex items-center justify-center">
                                <CreditCard class="h-6 w-6 mr-3" />
                                <span>
            {{ purchaseType === 'donation'
                                    ? `Donate ${formatPrice(totalAmount)} via Stripe`
                                    : `Pay ${formatPrice(totalAmount)} via Stripe`
                                    }}
        </span>
                            </div>
                        </Button>


                        <div class="text-center text-xs text-muted-foreground space-y-1">
                            <p>🔒 Secure payment powered by Stripe</p>
                            <p>You'll be redirected to complete your payment</p>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </div>
</template>
