<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3'
import FormHeader from '@/components/admin/FormHeader.vue'
import OrderStatusBanner from '@/components/admin/OrderStatusBanner.vue'
import OrderItems from '@/components/admin/OrderItems.vue'
import OrderTimeline from '@/components/admin/OrderTimeline.vue'
import OrderSummary from '@/components/admin/OrderSummary.vue'
import CustomerInfo from '@/components/admin/CustomerInfo.vue'
import ShippingInfo from '@/components/admin/ShippingInfo.vue'
import PaymentInfo from '@/components/admin/PaymentInfo.vue'
import OrderNotes from '@/components/admin/OrderNotes.vue'
// NEW: Import beneficiary component (we'll create this)
import BeneficiaryInfo from '@/components/admin/BeneficiaryInfo.vue'
import { Package, Send, Printer, Heart } from 'lucide-vue-next'
import { ref, computed } from 'vue'
import AdminLayout from '@/Layouts/AdminLayout.vue'

// FIXED: Match the expected props structure from your existing code
const { order, orderHistory } = defineProps<{
    order: {
        id: number
        customer: {
            user?: {
                name: string
                email: string
                created_at: string
            }
            first_name?: string
            last_name?: string
            phone?: string
            email?: string
            created_at?: string
            total_orders?: number
            total_spent?: number
        }
        beneficiary?: {
            id: number
            first_name?: string
            last_name?: string
            phone?: string
            email?: string
            organization_name?: string
            relationship_to_donor?: string
            special_instructions?: string
            is_organization: boolean
        }
        items: Array<any>
        subtotal: number
        shipping_cost?: number
        tax?: number
        discount?: number
        total: number
        status: string
        is_donation: boolean
        notes?: string
        created_at: string
        payment_method?: string
        payment_status: string
        transaction_id?: string
        payment_date?: string
        shipping_address?: {
            address_line1: string
            address_line2?: string
            city: string
            state?: string
            postal_code: string
            country: string
        }
        shipping_name?: string
    }
    orderHistory?: Array<any>
}>()

// State
const isUpdatingStatus = ref(false)

// Form for status updates
const statusForm = useForm({
    status: order.status,
    notes: order.notes || ''
})

// FIXED: Status options (match the expected structure)
const statusOptions = [
    { value: 'pending', label: 'Pending', color: 'yellow' },
    { value: 'processing', label: 'Processing', color: 'blue' },
    { value: 'shipped', label: 'Shipped', color: 'purple' },
    { value: 'delivered', label: 'Delivered', color: 'green' },
    { value: 'cancelled', label: 'Cancelled', color: 'red' }
]

// FIXED: Header actions
const headerActions = computed(() => [
    {
        label: 'Send Email',
        icon: Send,
        variant: 'outline',
        onClick: sendOrderEmail
    },
    {
        label: 'Print',
        icon: Printer,
        variant: 'outline',
        onClick: printOrder
    }
])

// Actions
const updateStatus = () => {
    if (statusForm.status === order.status) return

    isUpdatingStatus.value = true
    statusForm.put(`/admin/orders/${order.id}/status`, {
        onSuccess: () => {
            isUpdatingStatus.value = false
        },
        onError: () => {
            isUpdatingStatus.value = false
        }
    })
}

const sendOrderEmail = () => {
    router.post(`/admin/orders/${order.id}/send-email`, {}, {
        preserveScroll: true
    })
}

const printOrder = () => {
    window.open(`/admin/orders/${order.id}/print`, '_blank')
}

const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('nl-NL', {
        day: '2-digit',
        month: 'long',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    })
}

// Page title with donation badge
const pageTitle = computed(() => {
    return order.is_donation ? `Order #${order.id} (Donation)` : `Order #${order.id}`
})

const pageDescription = computed(() => {
    return `Placed on ${formatDate(order.created_at)}`
})

// NEW: Check if order has beneficiary
const hasBeneficiary = computed(() => {
    return order.is_donation && order.beneficiary
})

// FIXED: Breadcrumbs
const breadcrumbs = [
    { label: 'Orders', href: '/admin/orders' },
    { label: `#${order.id}`, href: null }
]
</script>

<template>
    <AdminLayout title="Orders Management" :breadcrumbs="breadcrumbs">
        <div class="min-h-screen bg-background text-foreground">
            <Head :title="pageTitle" />

            <!-- Form Header Component -->
            <div class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Main Content -->
                    <div class="lg:col-span-2 space-y-8">
                        <!-- Order Items Component -->
                        <OrderItems
                            :items="order.items"
                            currency="USD"
                            locale="en-US"
                        />
                    </div>

                    <!-- Sidebar -->
                    <div class="lg:col-span-1 space-y-6">
                        <!-- Order Summary Component -->
                        <OrderSummary
                            :order="order"
                            currency="USD"
                            locale="en-US"
                        />

                        <!-- FIXED: Customer Information Component -->
                        <CustomerInfo
                            :order="order"
                            locale="en-US"
                        />

                        <!-- NEW: Beneficiary Information (Only for donations) -->
                        <BeneficiaryInfo
                            v-if="order.is_donation"
                            :beneficiary="order.beneficiary"
                            :has-beneficiary="hasBeneficiary"
                        />

                        <!-- Shipping Information Component -->
                        <ShippingInfo
                            v-if="!order.is_donation && order.shipping_address"
                            :order="order"
                        />

                        <!-- Payment Information Component -->
                        <PaymentInfo
                            :order="order"
                            locale="en-US"
                        />

                        <!-- Order Notes Component -->
                        <OrderNotes
                            v-if="order.notes"
                            :notes="order.notes"
                            title="Order Notes"
                        />
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
