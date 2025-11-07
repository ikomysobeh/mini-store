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
import BeneficiaryInfo from '@/components/admin/BeneficiaryInfo.vue'
// ✅ NEW: Import UI components for status update
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Select, SelectContent, SelectGroup, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Badge } from '@/components/ui/badge'
import { Textarea } from '@/components/ui/textarea'
import { Label } from '@/components/ui/label'
import { Package, Send, Printer, Heart, CheckCircle, Clock, XCircle, AlertCircle } from 'lucide-vue-next'
import { ref, computed, watch } from 'vue'
import AdminLayout from '@/Layouts/AdminLayout.vue'

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
        payment_date?: string
        paid_at?: string
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

// ✅ UPDATED: New status options
const statusOptions = [
    { value: 'pending', label: 'Pending', color: 'yellow', icon: Clock },
    { value: 'processing', label: 'Processing', color: 'blue', icon: AlertCircle },
    { value: 'failed', label: 'Failed', color: 'red', icon: XCircle },
    { value: 'success', label: 'Success', color: 'green', icon: CheckCircle },
    { value: 'done', label: 'Done', color: 'gray', icon: CheckCircle }
]

// State for status update
const selectedStatus = ref(order.status)

// Form for status updates
const statusForm = useForm({
    status: order.status,
    notes: order.notes || ''
})

// Watch for status changes
watch(selectedStatus, (newStatus) => {
    statusForm.status = newStatus
})

// Check if status has changed
const hasStatusChanged = computed(() => {
    return statusForm.status !== order.status
})

// Check if notes have changed
const hasNotesChanged = computed(() => {
    return statusForm.notes !== (order.notes || '')
})

// Check if anything has changed
const hasChanges = computed(() => {
    return hasStatusChanged.value || hasNotesChanged.value
})

// Get current status option
const currentStatusOption = computed(() => {
    return statusOptions.find(opt => opt.value === order.status)
})

// Get status color class
const getStatusColorClass = (color: string) => {
    const colors: Record<string, string> = {
        yellow: 'bg-yellow-100 text-yellow-800 border-yellow-300',
        blue: 'bg-blue-100 text-blue-800 border-blue-300',
        red: 'bg-red-100 text-red-800 border-red-300',
        green: 'bg-green-100 text-green-800 border-green-300',
        gray: 'bg-gray-100 text-gray-800 border-gray-300'
    }
    return colors[color] || 'bg-gray-100 text-gray-800 border-gray-300'
}

// Update order status
const updateStatus = () => {
    statusForm.put(`/admin/orders/${order.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            selectedStatus.value = order.status
        }
    })
}

// Header actions
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

const pageTitle = computed(() => {
    return order.is_donation ? `Order #${order.id} (Donation)` : `Order #${order.id}`
})

const pageDescription = computed(() => {
    return `Placed on ${formatDate(order.created_at)}`
})

const hasBeneficiary = computed(() => {
    return order.is_donation && order.beneficiary
})

const breadcrumbs = [
    { label: 'Orders', href: '/admin/orders' },
    { label: `#${order.id}`, href: null }
]
</script>

<template>
    <AdminLayout title="Orders Management" :breadcrumbs="breadcrumbs">
        <div class="min-h-screen bg-background text-foreground">
            <Head :title="pageTitle" />

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
                        <!-- ✅ NEW: Status Update Card -->
                        <Card>
                            <CardHeader>
                                <CardTitle class="flex items-center justify-between">
                                    <span>Order Status</span>
                                    <Badge 
                                        v-if="currentStatusOption" 
                                        :class="getStatusColorClass(currentStatusOption.color)"
                                        class="border"
                                    >
                                        <component 
                                            :is="currentStatusOption.icon" 
                                            class="h-3 w-3 mr-1"
                                        />
                                        {{ currentStatusOption.label }}
                                    </Badge>
                                </CardTitle>
                            </CardHeader>
                            <CardContent class="space-y-4">
                                <!-- Status Select -->
                                <div class="space-y-2">
                                    <Label for="status">Update Status</Label>
                                    <Select v-model="selectedStatus">
                                        <SelectTrigger id="status" class="w-full">
                                            <SelectValue placeholder="Select status" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectGroup>
                                                <SelectItem 
                                                    v-for="option in statusOptions" 
                                                    :key="option.value" 
                                                    :value="option.value"
                                                >
                                                    <div class="flex items-center space-x-2">
                                                        <component 
                                                            :is="option.icon" 
                                                            class="h-4 w-4"
                                                            :class="{
                                                                'text-yellow-600': option.color === 'yellow',
                                                                'text-blue-600': option.color === 'blue',
                                                                'text-red-600': option.color === 'red',
                                                                'text-green-600': option.color === 'green',
                                                                'text-gray-600': option.color === 'gray'
                                                            }"
                                                        />
                                                        <span>{{ option.label }}</span>
                                                    </div>
                                                </SelectItem>
                                            </SelectGroup>
                                        </SelectContent>
                                    </Select>
                                </div>

                                <!-- Notes Textarea -->
                               

                                <!-- Update Button -->
                                <Button
                                    @click="updateStatus"
                                    :disabled="!hasChanges || statusForm.processing"
                                    class="w-full"
                                    size="lg"
                                >
                                    <span v-if="statusForm.processing">Updating...</span>
                                    <span v-else-if="!hasChanges">No Changes</span>
                                    <span v-else>Update Order</span>
                                </Button>
                            </CardContent>
                        </Card>

                        <!-- Order Summary Component -->
                        <OrderSummary
                            :order="order"
                            currency="USD"
                            locale="en-US"
                        />

                        <!-- Customer Information Component -->
                        <CustomerInfo
                            :order="order"
                            locale="en-US"
                        />

                        <!-- Beneficiary Information (Only for donations) -->
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
