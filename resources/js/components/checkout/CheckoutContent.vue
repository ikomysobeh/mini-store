<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { CreditCard, Heart, ShoppingCart, User, MessageSquare, Users, ChevronDown, ChevronUp, Clock, Truck } from 'lucide-vue-next';
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
    // Fallback properties if product is not loaded
    product_name?: string;
    product_id?: number;
}

interface Customer {
    first_name?: string;
    last_name?: string;
    phone?: string;
    address?: string;
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

// Beneficiary form state
const showBeneficiaryForm = ref(false);
const beneficiaryIsOrganization = ref(false);

// SIMPLIFIED: Form data with reduced beneficiary fields
const formData = ref({
    // Customer/Donor Information (pre-populate from existing data)
    first_name: props.existingCustomerData?.first_name || props.customer?.first_name || '',
    last_name: props.existingCustomerData?.last_name || props.customer?.last_name || '',
    phone: props.existingCustomerData?.phone || props.customer?.phone || '',
    address: props.existingCustomerData?.address || props.customer?.address || '',
    notes: '',

    // SIMPLIFIED: Beneficiary Information (removed unwanted fields)
    has_beneficiary: false,
    // Individual Person - KEPT: First Name, Last Name, Phone
    beneficiary_first_name: '',
    beneficiary_last_name: '',
    beneficiary_phone: '',
    // REMOVED: beneficiary_email, beneficiary_relationship

    // Organization - KEPT: Organization Name only (like "3lmni al 9aid initiative")
    beneficiary_organization_name: '',
    // REMOVED: organization contact phone, organization contact email

    // Common fields
    beneficiary_special_instructions: '',
    beneficiary_is_organization: false,
});

// Watch for beneficiary form toggle
watch(showBeneficiaryForm, (newValue) => {
    formData.value.has_beneficiary = newValue;
    if (!newValue) {
        // Reset beneficiary data when hiding form
        formData.value.beneficiary_first_name = '';
        formData.value.beneficiary_last_name = '';
        formData.value.beneficiary_phone = '';
        formData.value.beneficiary_organization_name = '';
        formData.value.beneficiary_special_instructions = '';
        formData.value.beneficiary_is_organization = false;
        beneficiaryIsOrganization.value = false;
    }
});

// Watch for organization toggle
// Watch for organization toggle
watch(beneficiaryIsOrganization, (newValue) => {
    formData.value.beneficiary_is_organization = newValue;
    if (newValue) {
        // Clear individual fields when switching to organization
        formData.value.beneficiary_first_name = '';
        formData.value.beneficiary_last_name = '';
        formData.value.beneficiary_phone = '';

        // AUTO-SET: Always set to the fixed organization name
        formData.value.beneficiary_organization_name = '3lmni al 9aid initiative';
    } else {
        // Clear organization field when switching to individual
        formData.value.beneficiary_organization_name = '';
    }
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

// SIMPLIFIED: Validation with reduced beneficiary requirements
const isFormValid = computed(() => {
    const basicValid = formData.value.first_name &&
        formData.value.last_name &&
        formData.value.phone;

    const addressValid = purchaseType.value === 'donation' || formData.value.address;

    // SIMPLIFIED: If beneficiary form is shown, validate only required fields
    let beneficiaryValid = true;
    if (showBeneficiaryForm.value && formData.value.has_beneficiary) {
        if (beneficiaryIsOrganization.value) {
            // Organization: Only organization name required
            beneficiaryValid = !!formData.value.beneficiary_organization_name;
        } else {
            // Individual: At least first name or last name required
            beneficiaryValid = !!(formData.value.beneficiary_first_name || formData.value.beneficiary_last_name);
        }
    }

    return basicValid && addressValid && beneficiaryValid;
});

// Handle form submission with simplified beneficiary data
const completeOrder = async () => {
    if (isProcessing.value || !isFormValid.value) return;

    isProcessing.value = true;

    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        if (!csrfToken) {
            throw new Error('CSRF token not found. Please refresh the page.');
        }

        // SIMPLIFIED: Include only the kept beneficiary fields
        const orderData = {
            is_donation: purchaseType.value === 'donation',
            first_name: formData.value.first_name,
            last_name: formData.value.last_name,
            phone: formData.value.phone,
            address: formData.value.address,
            notes: formData.value.notes,

            // SIMPLIFIED: Beneficiary data (only kept fields)
            has_beneficiary: formData.value.has_beneficiary,
            beneficiary_first_name: formData.value.beneficiary_first_name,
            beneficiary_last_name: formData.value.beneficiary_last_name,
            beneficiary_phone: formData.value.beneficiary_phone,
            beneficiary_organization_name: formData.value.beneficiary_organization_name,
            beneficiary_special_instructions: formData.value.beneficiary_special_instructions,
            beneficiary_is_organization: formData.value.beneficiary_is_organization,
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
                                    <span>Delivery: 1 day to 1 week</span>
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
                                <p>❤️ Receive it from one of our centers</p>
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

                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                            <div class="flex items-start space-x-3">
                                <Truck class="h-5 w-5 text-blue-600 mt-0.5" />
                                <div>
                                    <h4 class="text-sm font-medium text-blue-800">Delivery Information</h4>
                                    <div class="mt-2 space-y-1 text-sm text-blue-700">
                                        <p class="flex items-center space-x-2">
                                            <Clock class="h-3 w-3" />
                                            <span><strong>Delivery Time:</strong> 1 day to 1 week</span>
                                        </p>
                                        <p>📞 Our admin will contact you within 24-48 hours to coordinate delivery</p>
                                    </div>
                                </div>
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
                                : 'Delivery preferences, preferred time slots, special instructions, etc...'"
                            class="flex min-h-[60px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                        ></textarea>
                    </div>
                </CardContent>
            </Card>

            <!-- SIMPLIFIED: Beneficiary Information (Only for Donations) -->
            <Card v-if="purchaseType === 'donation'">
                <CardHeader>
                    <CardTitle class="flex items-center justify-between">
                        <div class="flex items-center space-x-2">
                            <Users class="h-5 w-5" />
                            <span>Beneficiary Information</span>
                            <span class="inline-flex items-center rounded-full border border-gray-300 px-2.5 py-0.5 text-xs font-medium text-gray-700 bg-white">
                                Optional
                            </span>
                        </div>
                        <Button
                            variant="ghost"
                            size="sm"
                            @click="showBeneficiaryForm = !showBeneficiaryForm"
                            class="flex items-center space-x-1"
                        >
                            <span>{{ showBeneficiaryForm ? 'Hide' : 'Add' }} Beneficiary</span>
                            <ChevronDown v-if="!showBeneficiaryForm" class="h-4 w-4" />
                            <ChevronUp v-else class="h-4 w-4" />
                        </Button>
                    </CardTitle>
                </CardHeader>

                <CardContent v-if="showBeneficiaryForm" class="space-y-4">
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <p class="text-sm text-blue-800 font-medium">Who is this donation for?</p>
                        <p class="text-xs text-blue-600 mt-1">This section is completely optional. You can skip it if you prefer.</p>
                    </div>

                    <!-- Beneficiary Type Selection -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <label :class="`cursor-pointer rounded-lg border-2 p-4 ${!beneficiaryIsOrganization ? 'border-primary bg-primary/10' : 'border-border'}`">
                            <input
                                type="radio"
                                :value="false"
                                v-model="beneficiaryIsOrganization"
                                class="sr-only"
                            />
                            <div class="flex items-center space-x-2">
                                <User class="h-5 w-5" />
                                <span class="font-medium">Individual Person</span>
                            </div>
                        </label>

                        <label :class="`cursor-pointer rounded-lg border-2 p-4 ${beneficiaryIsOrganization ? 'border-primary bg-primary/10' : 'border-border'}`">
                            <input
                                type="radio"
                                :value="true"
                                v-model="beneficiaryIsOrganization"
                                class="sr-only"
                            />
                            <div class="flex items-center space-x-2">
                                <Users class="h-5 w-5" />
                                <span class="font-medium">Organization</span>
                            </div>
                        </label>
                    </div>

                    <!-- SIMPLIFIED: Individual Beneficiary Fields -->
                    <div v-if="!beneficiaryIsOrganization" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <Label for="beneficiary_first_name">First Name</Label>
                                <Input
                                    id="beneficiary_first_name"
                                    v-model="formData.beneficiary_first_name"
                                    placeholder="Beneficiary's first name"
                                />
                            </div>
                            <div class="space-y-2">
                                <Label for="beneficiary_last_name">Last Name</Label>
                                <Input
                                    id="beneficiary_last_name"
                                    v-model="formData.beneficiary_last_name"
                                    placeholder="Beneficiary's last name"
                                />
                            </div>
                        </div>

                        <!-- KEPT: Only phone for individual -->
                        <div class="space-y-2">
                            <Label for="beneficiary_phone">Phone (Optional)</Label>
                            <Input
                                id="beneficiary_phone"
                                v-model="formData.beneficiary_phone"
                                type="tel"
                                placeholder="Beneficiary's contact number"
                            />
                        </div>

                        <!-- REMOVED: Email field and Relationship dropdown -->
                    </div>

                    <!-- SIMPLIFIED: Organization Beneficiary Fields -->
                    <!-- SIMPLIFIED: Organization Beneficiary Fields - Auto-filled -->
                    <div v-else class="space-y-4">
                        <div class="space-y-2">
                            <Label for="beneficiary_organization_name">Organization Name</Label>
                            <div class="relative">
                                <Input
                                    id="beneficiary_organization_name"
                                    v-model="formData.beneficiary_organization_name"
                                    readonly
                                    value="3lmni al 9aid initiative"
                                    class="bg-gray-50 text-gray-700 cursor-not-allowed"
                                />
                                <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                <span class="inline-flex items-center rounded-full bg-blue-100 px-2 py-1 text-xs font-medium text-blue-800">
                    Auto-filled
                </span>
                                </div>
                            </div>
                            <p class="text-xs text-gray-600">
                                ℹ️ This donation will be made on behalf of "3lmni al 9aid initiative"
                            </p>
                        </div>
                    </div>

                    <!-- Common Fields -->
                    <div class="space-y-2">
                        <Label for="beneficiary_special_instructions">Special Instructions (Optional)</Label>
                        <textarea
                            id="beneficiary_special_instructions"
                            v-model="formData.beneficiary_special_instructions"
                            rows="2"
                            placeholder="Any special requirements or instructions for the beneficiary..."
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

                        <div v-else class="bg-primary/10 border border-primary/20 rounded-lg p-4">
                            <div class="text-center mb-3">
                                <p class="text-xs text-primary/90">Admin will contact you within 24-48 hours</p>
                            </div>
                            <div class="bg-white rounded-md p-3">
                                <div class="flex items-center space-x-2 mb-2">
                                    <Truck class="h-4 w-4 text-blue-600" />
                                    <span class="text-sm font-medium text-gray-800">Delivery Timeline</span>
                                </div>
                                <div class="space-y-1 text-xs text-gray-600">
                                    <p class="flex items-center space-x-2">
                                        <Clock class="h-3 w-3" />
                                        <span><strong>Expected:</strong> 1 day to 1 week</span>
                                    </p>
                                </div>
                            </div>
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
