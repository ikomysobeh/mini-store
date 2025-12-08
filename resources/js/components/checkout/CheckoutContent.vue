<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { CreditCard, Heart, ShoppingCart, User, MessageSquare, Clock } from 'lucide-vue-next';
// import { Wallet } from 'lucide-vue-next'; // PayPal postponed

import { ref, computed, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { useLocale } from '@/composables/useLocale';

const { t, locale } = useI18n();
const { localizedUrl } = useLocale();

// RTL support
const isRTL = computed(() => locale.value === 'ar');
const iconSpacing = computed(() => isRTL.value ? 'ml-2' : 'mr-2');

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
    product_name?: string;
    product_id?: number;
}

interface Customer {
    first_name?: string;
    last_name?: string;
    phone?: string;
    has_existing_data?: boolean;
}

interface Props {
    cartItems: CartItem[];
    customer?: Customer | null;
    user?: any;
    shippingMethods?: any[];
    paymentMethods?: any[];
    existingCustomerData?: Customer | null;
}

const props = withDefaults(defineProps<Props>(), {
    customer: null,
    user: null,
    shippingMethods: () => [],
    paymentMethods: () => [],
    existingCustomerData: null
});

// State
const isProcessing = ref(false);
const purchaseType = ref<'purchase' | 'donation'>('purchase');
const paymentMethod = ref<'stripe'>('stripe'); // PayPal postponed for now
// const paymentMethod = ref<'stripe' | 'paypal'>('stripe');
const isPayPalSelected = computed(() => false); // PayPal postponed
// const isPayPalSelected = computed(() => paymentMethod.value === 'paypal');

// ✅ NEW: Country code state
const countryCode = ref('+963');
const isCustomCode = ref(false);
const customCode = ref('');

// ✅ NEW: Country codes list
const countryCodes = [
    { code: '+963', country: 'Syria', flag: '🇸🇾' },
    { code: '+1', country: 'USA', flag: '🇺🇸' },
    { code: '+44', country: 'UK', flag: '🇬🇧' },
    { code: '+971', country: 'UAE', flag: '🇦🇪' },
    { code: '+966', country: 'Saudi Arabia', flag: '🇸🇦' },
    { code: '+962', country: 'Jordan', flag: '🇯🇴' },
    { code: '+961', country: 'Lebanon', flag: '🇱🇧' },
    { code: '+20', country: 'Egypt', flag: '🇪🇬' },
    { code: 'custom', country: 'Other Country', flag: '🌍' },
];

// Form data - ✅ UPDATED: Remove country code from phone
const formData = ref({
    first_name: props.existingCustomerData?.first_name || props.customer?.first_name || '',
    last_name: props.existingCustomerData?.last_name || props.customer?.last_name || '',
    phone: props.existingCustomerData?.phone?.replace(/^\+\d+\s*/, '') || props.customer?.phone?.replace(/^\+\d+\s*/, '') || '',
    notes: '',
});

// ✅ NEW: Watch for country code changes
watch(countryCode, (newValue) => {
    if (newValue === 'custom') {
        isCustomCode.value = true;
        customCode.value = '+';
    } else {
        isCustomCode.value = false;
        customCode.value = '';
    }
});

// ✅ NEW: Full phone number computed
const fullPhoneNumber = computed(() => {
    if (!formData.value.phone) return '';
    
    const activeCode = isCustomCode.value ? customCode.value : countryCode.value;
    
    if (!activeCode || activeCode === 'custom') return formData.value.phone;
    
    return `${activeCode}${formData.value.phone.replace(/^0+/, '')}`;
});

// Computed
const totalAmount = computed(() => {
    return props.cartItems.reduce((sum, item) => sum + (item.price * item.quantity), 0);
});

const formatPrice = (amount: number) => {
    return new Intl.NumberFormat(locale.value === 'ar' ? 'ar-SY' : 'en-US', {
        style: 'currency',
        currency: 'USD'
    }).format(amount);
};

const checkoutCtaText = computed(() => {
    const amountLabel = formatPrice(totalAmount.value);

    // PayPal postponed for now
    // if (isPayPalSelected.value) {
    //     return t('checkout.paypalCta', { amount: amountLabel });
    // }

    return purchaseType.value === 'donation'
        ? `${t('checkout.donate')} ${amountLabel}`
        : `${t('checkout.pay')} ${amountLabel}`;
});

// PayPal postponed for now
// const initiatePayPalCheckout = async (orderId: number, csrfToken: string) => {
//     const response = await fetch(localizedUrl('/payments/paypal/create'), {
//         method: 'POST',
//         headers: {
//             'Content-Type': 'application/json',
//             'Accept': 'application/json',
//             'X-CSRF-TOKEN': csrfToken,
//         },
//         credentials: 'same-origin',
//         body: JSON.stringify({ order_id: orderId }),
//     });
//
//     if (!response.ok) {
//         throw new Error(t('checkout.paypalInitFailed'));
//     }
//
//     const data = await response.json();
//
//     if (!data?.approval_url) {
//         throw new Error(t('checkout.paypalMissingApproval'));
//     }
//
//     window.location.href = data.approval_url;
// };

const missingFields = computed(() => {
    const missing: string[] = [];
    
    if (!formData.value.first_name) missing.push(t('checkout.firstName'));
    if (!formData.value.last_name) missing.push(t('checkout.lastName'));
    if (!formData.value.phone) missing.push(t('checkout.phone'));
    
    return missing;
});

// Helper function to get product name safely
const getProductName = (item: CartItem) => {
    return item.product?.name || item.product_name || 'Product';
};

// Helper function to get product image safely
const getProductImage = (item: CartItem) => {
    return item.product?.image || null;
};

const isFormValid = computed(() => {
    return formData.value.first_name &&
        formData.value.last_name &&
        formData.value.phone;
});

// Handle form submission - ✅ UPDATED: Use full phone number
const completeOrder = async () => {
    if (isProcessing.value || !isFormValid.value) return;

    isProcessing.value = true;

    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        if (!csrfToken) {
            throw new Error('CSRF token not found. Please refresh the page.');
        }

        const orderData = {
            is_donation: purchaseType.value === 'donation',
            first_name: formData.value.first_name,
            last_name: formData.value.last_name,
            phone: fullPhoneNumber.value, // ✅ CHANGED: Use full phone with country code
            notes: formData.value.notes,
            payment_method: paymentMethod.value,
        };

        const response = await fetch(localizedUrl('/orders'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            },
            credentials: 'same-origin',
            body: JSON.stringify(orderData),
        });

        if (!response.ok) {
            const errorData = await response.text();
            console.error('Response error:', response.status, errorData);
            throw new Error(`Server error: ${response.status}`);
        }

        const data = await response.json();

        // PayPal postponed for now
        // if (paymentMethod.value === 'paypal') {
        //     if (!data?.order_id) {
        //         throw new Error(t('checkout.paypalInitFailed'));
        //     }
        //
        //     await initiatePayPalCheckout(data.order_id, csrfToken);
        //     return;
        // }

        if (data.url) {
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
                    <CardTitle class="text-center text-xl">{{ t('checkout.chooseOption') }}</CardTitle>
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
                                        {{ t('checkout.purchaseItems') }}
                                    </h3>
                                    <p :class="[
                                        'text-sm transition-colors',
                                        purchaseType === 'purchase' ? 'text-primary-foreground/90' : 'text-muted-foreground'
                                    ]">
                                        {{ t('checkout.purchaseDescription') }}
                                    </p>
                                </div>
                            </div>

                            <div :class="[
                                'mt-4 space-y-1 text-sm transition-colors',
                                purchaseType === 'purchase' ? 'text-yellow-100' : 'text-gray-600'
                            ]">
                                <p>✓ {{ t('checkout.purchaseBenefit1') }}</p>
                                <p>✓ {{ t('checkout.purchaseBenefit2') }}</p>
                                <p class="flex items-center space-x-1">
                                    <Clock class="h-3 w-3" />
                                    <span>{{ t('checkout.deliveryTime') }}</span>
                                </p>
                            </div>

                            <div v-if="purchaseType === 'purchase'" class="absolute top-3 right-3">
                                <span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-800">
                                    {{ t('checkout.selected') }} ✓
                                </span>
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
                                        {{ t('checkout.donateAmount') }}
                                    </h3>
                                    <p :class="[
                                        'text-sm transition-colors',
                                        purchaseType === 'donation' ? 'text-yellow-100' : 'text-gray-600'
                                    ]">
                                        {{ t('checkout.donateDescription') }}
                                    </p>
                                </div>
                            </div>

                            <div :class="[
                                'mt-4 space-y-1 text-sm transition-colors',
                                purchaseType === 'donation' ? 'text-yellow-100' : 'text-gray-600'
                            ]">
                                <p>❤️ {{ t('checkout.donateBenefit1') }}</p>
                                <p>❤️ {{ t('checkout.donateBenefit2') }}</p>
                            </div>

                            <div v-if="purchaseType === 'donation'" class="absolute top-3 right-3">
                                <span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-800">
                                    {{ t('checkout.selected') }} ✓
                                </span>
                            </div>
                        </label>
                    </div>
                </CardContent>
            </Card>

            <!-- Payment Method Selection - PayPal postponed, showing only Stripe -->
            <Card>
                <CardHeader>
                    <CardTitle class="text-center text-xl">{{ t('checkout.paymentMethod') }}</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="flex justify-center">
                        <!-- Stripe/Card Payment Option -->
                        <label
                            class="relative cursor-pointer rounded-xl border-2 p-6 focus:outline-none transition-all hover:shadow-md border-primary bg-primary shadow-md max-w-md w-full"
                        >
                            <input
                                type="radio"
                                value="stripe"
                                v-model="paymentMethod"
                                class="sr-only"
                                checked
                            />

                            <div class="flex flex-col items-center text-center space-y-4">
                                <div class="p-4 rounded-full bg-primary">
                                    <CreditCard class="h-8 w-8 text-primary-foreground" />
                                </div>
                                <div>
                                    <h3 class="font-bold text-lg text-primary-foreground">
                                        {{ t('checkout.cardOptionTitle') }}
                                    </h3>
                                    <p class="text-sm text-primary-foreground/90">
                                        {{ t('checkout.cardOptionDescription') }}
                                    </p>
                                </div>
                            </div>

                            <div class="absolute top-3 right-3">
                                <span class="inline-flex items-center rounded-full bg-white/20 px-2.5 py-0.5 text-xs font-medium text-white">
                                    {{ t('checkout.selected') }} ✓
                                </span>
                            </div>
                        </label>

                        <!-- PayPal Option - POSTPONED
                        <label
                            :class="[
                                'relative cursor-pointer rounded-xl border-2 p-6 focus:outline-none transition-all hover:shadow-md',
                                paymentMethod === 'paypal'
                                    ? 'border-yellow-500 bg-yellow-500 shadow-md'
                                    : 'border-border hover:border-yellow-500/50 bg-background'
                            ]"
                        >
                            <input
                                type="radio"
                                value="paypal"
                                v-model="paymentMethod"
                                class="sr-only"
                            />

                            <div class="flex flex-col items-center text-center space-y-4">
                                <div :class="[
                                    'p-4 rounded-full',
                                    paymentMethod === 'paypal' ? 'bg-yellow-500' : 'bg-yellow-500/20'
                                ]">
                                    <Wallet :class="[
                                        'h-8 w-8',
                                        paymentMethod === 'paypal' ? 'text-yellow-900' : 'text-yellow-600'
                                    ]" />
                                </div>
                                <div>
                                    <h3 :class="[
                                        'font-bold text-lg transition-colors',
                                        paymentMethod === 'paypal' ? 'text-yellow-900' : 'text-foreground'
                                    ]">
                                        {{ t('checkout.paypalOptionTitle') }}
                                    </h3>
                                    <p :class="[
                                        'text-sm transition-colors',
                                        paymentMethod === 'paypal' ? 'text-yellow-100' : 'text-muted-foreground'
                                    ]">
                                        {{ t('checkout.paypalOptionDescription') }}
                                    </p>
                                </div>
                            </div>

                            <div v-if="paymentMethod === 'paypal'" class="absolute top-3 right-3">
                                <span class="inline-flex items-center rounded-full bg-white/20 px-2.5 py-0.5 text-xs font-medium text-yellow-900">
                                    {{ t('checkout.selected') }} ✓
                                </span>
                            </div>
                        </label>
                        -->
                    </div>
                </CardContent>
            </Card>

            <!-- Customer Information -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center space-x-2">
                        <User class="h-5 w-5" />
                        <span>{{ purchaseType === 'donation' ? t('checkout.donorInfo') : t('checkout.customerInfo') }}</span>
                        <span v-if="existingCustomerData?.has_existing_data" class="ml-auto inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-800">
                            {{ t('checkout.prefilled') }}
                        </span>
                    </CardTitle>
                </CardHeader>
                <CardContent class="space-y-4">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <Label for="first_name">{{ t('checkout.firstName') }} *</Label>
                            <Input
                                id="first_name"
                                v-model="formData.first_name"
                                required
                                :placeholder="t('checkout.firstNamePlaceholder')"
                            />
                        </div>

                        <div class="space-y-2">
                            <Label for="last_name">{{ t('checkout.lastName') }} *</Label>
                            <Input
                                id="last_name"
                                v-model="formData.last_name"
                                required
                                :placeholder="t('checkout.lastNamePlaceholder')"
                            />
                        </div>
                    </div>

                    <!-- ✅ UPDATED: Phone Number with Country Code -->
                    <!-- ✅ UPDATED: Phone Number with Country Code - Mobile Responsive -->
<div class="space-y-2">
    <Label for="phone">{{ t('checkout.phone') }} *</Label>
    
    <!-- Desktop Layout -->
    <div class="hidden sm:flex gap-2">
        <!-- Country Code Dropdown -->
        <select
            v-model="countryCode"
            class="flex h-10 w-40 rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
        >
            <option v-for="country in countryCodes" :key="country.code" :value="country.code">
                {{ country.flag }} {{ country.code === 'custom' ? country.country : country.code }}
            </option>
        </select>

        <!-- Custom Code Input -->
        <Input
            v-if="isCustomCode"
            v-model="customCode"
            type="text"
            placeholder="+XXX"
            class="w-24"
            @input="customCode = customCode.replace(/[^0-9+]/g, '')"
        />

        <!-- Phone Input -->
        <Input
            id="phone"
            v-model="formData.phone"
            type="tel"
            required
            placeholder="944255208"
            class="flex-1"
            @input="formData.phone = formData.phone.replace(/[^0-9]/g, '')"
        />
    </div>

    <!-- Mobile Layout -->
    <div class="sm:hidden space-y-2">
        <!-- Country Code Dropdown (Full Width) -->
        <select
            v-model="countryCode"
            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
        >
            <option v-for="country in countryCodes" :key="country.code" :value="country.code">
                {{ country.flag }} {{ country.code === 'custom' ? country.country : country.code }}
            </option>
        </select>

        <!-- Custom Code Input (Full Width) -->
        <Input
            v-if="isCustomCode"
            v-model="customCode"
            type="text"
            placeholder="+XXX"
            class="w-full"
            @input="customCode = customCode.replace(/[^0-9+]/g, '')"
        />

        <!-- Phone Input (Full Width) -->
        <Input
            id="phone"
            v-model="formData.phone"
            type="tel"
            required
            placeholder="944255208"
            class="w-full"
            @input="formData.phone = formData.phone.replace(/[^0-9]/g, '')"
        />
    </div>

    <p class="text-xs text-muted-foreground">
        {{ t('checkout.fullNumber') }}: <strong>{{ fullPhoneNumber || t('checkout.enterPhone') }}</strong>
    </p>
    <p v-if="isCustomCode" class="text-xs text-amber-600">
        💡 {{ t('checkout.countryCodeHint') }}
    </p>
</div>


                    <!--  Time Note -->
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <div class="flex items-start space-x-3">
                            <Clock class="h-5 w-5 text-blue-600 mt-0.5 flex-shrink-0" />
                            <div class="space-y-2">
                                <h4 class="text-sm font-medium text-blue-800">{{ t('checkout.information') }}</h4>
                                <p class="text-sm text-blue-700">
                                    📦 {{ t('checkout.orderDeliveryInfo') }}
                                </p>
                                <p class="text-sm text-blue-700">
                                    🏢 {{ t('checkout.coordinationInfo') }}
                                </p>
                                <p class="text-sm text-blue-700">
                                    📞 {{ t('checkout.contactInfo') }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <Label for="notes" class="flex items-center space-x-2">
                            <MessageSquare class="h-4 w-4" />
                            <span>{{ t('checkout.additionalNotes') }} ({{ t('common.optional') }})</span>
                        </Label>
                        <textarea
                            id="notes"
                            v-model="formData.notes"
                            rows="2"
                            :placeholder="purchaseType === 'donation'
                                ? t('checkout.notesPlaceholder')
                                : t('checkout.purchaseNotesPlaceholder')"
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
                            <span>{{ purchaseType === 'donation' ? t('checkout.donationSummary') : t('checkout.orderSummary') }}</span>
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
                                        <p class="text-muted-foreground">{{ t('checkout.qty') }}: {{ item.quantity }}</p>
                                    </div>
                                </div>
                                <p class="font-bold">{{ formatPrice(item.price * item.quantity) }}</p>
                            </div>
                        </div>

                        <div class="border-t pt-4">
                            <div class="flex justify-between text-xl font-bold">
                                <span>{{ purchaseType === 'donation' ? t('checkout.donationAmount') : t('cart.total') }}:</span>
                                <span>{{ formatPrice(totalAmount) }}</span>
                            </div>
                        </div>

                        <!-- Special Message -->
                        <div v-if="purchaseType === 'donation'" class="bg-destructive/10 border border-destructive/20 rounded-lg p-4 text-center">
                            <p class="text-sm text-destructive font-medium">🙏 {{ t('checkout.thankYouSupport') }}</p>
                        </div>

                      <!-- Support Contact Section - User Friendly -->
<div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
    <div class="space-y-3">
        <!-- Header -->
        <div class="flex items-center justify-center space-x-2">
            <MessageSquare class="h-5 w-5 text-blue-600" />
            <h4 class="text-sm font-semibold text-blue-900">{{ t('checkout.needHelp') }}</h4>
        </div>
        
        <!-- Message -->
        <p class="text-sm text-blue-800 text-center">
            {{ t('checkout.helpMessage') }}
        </p>
        
        <!-- WhatsApp Contact Button -->
        <a 
            :href="`https://wa.me/963937671126?text=${encodeURIComponent('Hi, I need help with my payment on the order')}`"
            target="_blank"
            rel="noopener noreferrer"
            class="flex items-center justify-center gap-2 w-full bg-green-600 hover:bg-green-700 text-white font-medium py-3 px-4 rounded-lg transition-colors duration-200"
        >
            <svg 
                class="h-5 w-5" 
                fill="currentColor" 
                viewBox="0 0 24 24"
                xmlns="http://www.w3.org/2000/svg"
            >
                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
            </svg>
            <span>{{ t('checkout.contactWhatsapp') }}</span>
        </a>
        
        <!-- Phone Number Display -->
        <p class="text-xs text-blue-700 text-center">
            📞 <span class="font-medium">+963 937 671 126</span>
        </p>
    </div>
</div>


                        <!-- Complete Order Button -->
                        <Button
                            @click="completeOrder"
                            :disabled="isProcessing || !isFormValid"
                            class="w-full h-14 text-lg font-bold"
                            :class="isPayPalSelected
                                ? 'bg-yellow-500 hover:bg-yellow-600 text-gray-900 disabled:bg-yellow-300'
                                : purchaseType === 'donation'
                                    ? 'bg-red-600 hover:bg-red-700 text-white disabled:bg-red-400'
                                    : 'bg-blue-600 hover:bg-blue-700 text-white disabled:bg-blue-400'"
                        >
                            <div v-if="isProcessing" class="flex items-center">
                                <svg
                                    :class="[
                                        'animate-spin h-6 w-6 text-white',
                                        isRTL ? 'ml-3 -mr-1' : '-ml-1 mr-3'
                                    ]"
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
                                <span>{{ t('checkout.processing') }}...</span>
                            </div>

                            <div v-else class="flex items-center justify-center">
                                <CreditCard :class="['h-6 w-6', iconSpacing]" />
                                <!-- PayPal postponed
                                <component
                                    :is="isPayPalSelected ? Wallet : CreditCard"
                                    :class="['h-6 w-6', iconSpacing]"
                                />
                                -->
                                <span>{{ checkoutCtaText }}</span>
                            </div>
                        </Button>

                        <!-- Missing fields helper -->
                        <div
                            v-if="!isProcessing && !isFormValid && missingFields.length"
                            class="mt-3 rounded-md border border-amber-300 bg-amber-50 px-3 py-3 text-sm text-amber-900"
                            role="alert"
                            aria-live="polite"
                        >
                            <p class="font-medium">
                                {{ t('checkout.fillAllFields') }}:
                            </p>
                            <div class="mt-2 flex flex-wrap gap-2">
                                <span
                                    v-for="field in missingFields"
                                    :key="field"
                                    class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-1 text-xs font-medium"
                                >
                                    {{ field }}
                                </span>
                            </div>
                        </div>

                        <div class="text-center text-xs text-muted-foreground space-y-1">
                            <p>🔒 {{ t('checkout.securePayment') }}</p>
                            <p>{{ t('checkout.redirectMessage') }}</p>
                        </div>

                    </CardContent>
                </Card>
            </div>
        </div>
    </div>
</template>
