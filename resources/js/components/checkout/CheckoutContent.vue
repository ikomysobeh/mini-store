<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { CreditCard, Heart, ShoppingCart, User, MessageSquare, Clock } from 'lucide-vue-next';
import { ref, computed, watch } from 'vue';

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
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD'
    }).format(amount);
};

const missingFields = computed(() => {
    const missing: string[] = [];
    
    if (!formData.value.first_name) missing.push('First Name');
    if (!formData.value.last_name) missing.push('Last Name');
    if (!formData.value.phone) missing.push('Phone Number');
    
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
        };

        const response = await fetch('/orders', {
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
                                <p>✓ Buy it for yourself or for one of your relatives in Sweida</p>
                                <p>✓ Receive it from one of our centers</p>
                                <p class="flex items-center space-x-1">
                                    <Clock class="h-3 w-3" />
                                    <span>received : 1 day to 1 week</span>
                                </p>
                            </div>

                            <div v-if="purchaseType === 'purchase'" class="absolute top-3 right-3">
                                <span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-800">
                                    Selected ✓
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
                                <p>❤️ Donate to one of the displaced families</p>
                                <p>❤️ Contributed to warming a family</p>
                            </div>

                            <div v-if="purchaseType === 'donation'" class="absolute top-3 right-3">
                                <span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-800">
                                    Selected ✓
                                </span>
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
                        <span v-if="existingCustomerData?.has_existing_data" class="ml-auto inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-800">
                            Pre-filled
                        </span>
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

                    <!-- ✅ UPDATED: Phone Number with Country Code -->
                    <!-- ✅ UPDATED: Phone Number with Country Code - Mobile Responsive -->
<div class="space-y-2">
    <Label for="phone">Phone Number *</Label>
    
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
        Full number: <strong>{{ fullPhoneNumber || 'Enter phone number' }}</strong>
    </p>
    <p v-if="isCustomCode" class="text-xs text-amber-600">
        💡 Enter your country code including the + symbol (e.g., +49 for Germany)
    </p>
</div>


                    <!--  Time Note -->
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <div class="flex items-start space-x-3">
                            <Clock class="h-5 w-5 text-blue-600 mt-0.5 flex-shrink-0" />
                            <div class="space-y-2">
                                <h4 class="text-sm font-medium text-blue-800"> Information</h4>
                                <p class="text-sm text-blue-700">
                                    📦 Your order will be received within 1 to 7 days
                                </p>
                                <p class="text-sm text-blue-700">
                                    🏢 The admin will be coordinated either at one of our centers or through another suitable method.
                                </p>
                                <p class="text-sm text-blue-700">
                                    📞 We will contact the number you provided within a week.
                                </p>
                            </div>
                        </div>
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
                                : ' preferred time slots, special instructions, etc...'"
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
                        </div>

                        <!-- Complete Order Button -->
                        <Button
                            @click="completeOrder"
                            :disabled="isProcessing || !isFormValid"
                            class="w-full h-14 text-lg font-bold"
                            :class="purchaseType === 'donation'
                                ? 'bg-red-600 hover:bg-red-700 text-white disabled:bg-red-400'
                                : 'bg-blue-600 hover:bg-blue-700 text-white disabled:bg-blue-400'"
                        >
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

                        <!-- Missing fields helper -->
                        <div
                            v-if="!isProcessing && !isFormValid && missingFields.length"
                            class="mt-3 rounded-md border border-amber-300 bg-amber-50 px-3 py-3 text-sm text-amber-900"
                            role="alert"
                            aria-live="polite"
                        >
                            <p class="font-medium">
                                Please complete the following to {{ purchaseType === 'donation' ? 'donate' : 'checkout' }}:
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
                            <p>🔒 Secure payment powered by Stripe</p>
                            <p>You'll be redirected to complete your payment</p>
                        </div>

                    </CardContent>
                </Card>
            </div>
        </div>
    </div>
</template>
