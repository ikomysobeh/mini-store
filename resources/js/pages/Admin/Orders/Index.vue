<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Search, Eye, Download, Filter, Users, Heart, ShoppingCart, User } from 'lucide-vue-next'
import { ref, computed } from 'vue'

// Props from controller
const { orders, filters, statuses, stats } = defineProps<{
    orders: {
        data: Array<any>
        links: Array<any>
        meta: any
    }
    filters: {
        search?: string
        status?: string
        is_donation?: boolean
        has_beneficiary?: string // NEW: Add this filter
    }
    statuses: Array<any>
    stats: {
        total: number
        pending: number
        processing: number
        shipped: number
        delivered: number
        cancelled: number
        total_revenue: number
        donations_total: number
        donations_with_beneficiary: number // NEW
        donations_without_beneficiary: number // NEW
        total_beneficiaries: number // NEW
    }
}>()

// Filter state
const searchQuery = ref(filters.search || '')
const selectedStatus = ref(filters.status || '')
const selectedDonationType = ref(filters.is_donation?.toString() || '')
const selectedBeneficiaryFilter = ref(filters.has_beneficiary || '') // NEW

// FIXED: Navigation function for view button
const viewOrder = (orderId: number) => {
    router.get(`/admin/orders/${orderId}`)
}

// Helper functions
const formatPrice = (amount: number) => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD'
    }).format(amount)
}

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    })
}

// NEW: Get beneficiary display name
const getBeneficiaryDisplay = (order: any) => {
    if (!order.beneficiary) {
        return order.is_donation ? 'Not specified' : '-'
    }

    if (order.beneficiary.is_organization) {
        return order.beneficiary.organization_name || 'Unknown Organization'
    }

    const firstName = order.beneficiary.first_name || ''
    const lastName = order.beneficiary.last_name || ''
    return `${firstName} ${lastName}`.trim() || 'Unknown Person'
}

// Status styling
const getStatusClass = (status: string) => {
    const classes = {
        pending: 'bg-yellow-100 text-yellow-800',
        processing: 'bg-blue-100 text-blue-800',
     failed: 'bg-red-100 text-red-800',        // ✅ NEW
        success: 'bg-green-100 text-green-800',   // ✅ NEW
        done: 'bg-gray-100 text-gray-800'         // ✅ NEW
    }
    return classes[status] || 'bg-gray-100 text-gray-800'
}

// Apply filters
const applyFilters = () => {
    const params = new URLSearchParams()

    if (searchQuery.value) params.append('search', searchQuery.value)
    if (selectedStatus.value) params.append('status', selectedStatus.value)
    if (selectedDonationType.value) params.append('is_donation', selectedDonationType.value)
    if (selectedBeneficiaryFilter.value) params.append('has_beneficiary', selectedBeneficiaryFilter.value) // NEW

    router.get('/admin/orders', Object.fromEntries(params), {
        preserveState: true,
        replace: true
    })
}

// Clear filters
const clearFilters = () => {
    searchQuery.value = ''
    selectedStatus.value = ''
    selectedDonationType.value = ''
    selectedBeneficiaryFilter.value = '' // NEW

    router.get('/admin/orders', {}, {
        preserveState: true,
        replace: true
    })
}

// Export functionality
const exportOrders = () => {
    const params = new URLSearchParams()
    if (searchQuery.value) params.append('search', searchQuery.value)
    if (selectedStatus.value) params.append('status', selectedStatus.value)
    if (selectedDonationType.value) params.append('is_donation', selectedDonationType.value)
    if (selectedBeneficiaryFilter.value) params.append('has_beneficiary', selectedBeneficiaryFilter.value) // NEW

    window.location.href = `/admin/orders/export?${params.toString()}`
}
</script>

<template>
    <AdminLayout>
        <Head title="Orders Management" />

        <div class="space-y-6">
            <!-- Page Header -->
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold">Orders Management</h1>
                    <p class="text-gray-600 mt-1">Manage customer orders and donations</p>
                </div>
                <Button @click="exportOrders" variant="outline" class="flex items-center space-x-2">
                    <Download class="h-4 w-4" />
                    <span>Export Orders</span>
                </Button>
            </div>

            <!-- ENHANCED: Stats Dashboard -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <Card>
                    <CardContent class="p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Total Orders</p>
                                <p class="text-2xl font-bold">{{ stats.total }}</p>
                            </div>
                            <ShoppingCart class="h-8 w-8 text-blue-600" />
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent class="p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Total Revenue</p>
                                <p class="text-2xl font-bold">{{ formatPrice(stats.total_revenue) }}</p>
                            </div>
                            <div class="h-8 w-8 rounded-full bg-green-100 flex items-center justify-center">
                                <span class="text-green-600 font-bold">$</span>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent class="p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Donations Total</p>
                                <p class="text-2xl font-bold">{{ formatPrice(stats.donations_total) }}</p>
                            </div>
                            <Heart class="h-8 w-8 text-red-600" />
                        </div>
                    </CardContent>
                </Card>

                <!-- NEW: Beneficiaries Stats Card -->
                <Card>
                    <CardContent class="p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Total Beneficiaries</p>
                                <p class="text-2xl font-bold">{{ stats.total_beneficiaries }}</p>
                                <p class="text-xs text-gray-500">
                                    {{ stats.donations_with_beneficiary }} with / {{ stats.donations_without_beneficiary }} without
                                </p>
                            </div>
                            <Users class="h-8 w-8 text-purple-600" />
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- ENHANCED: Filters Section -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center space-x-2">
                        <Filter class="h-5 w-5" />
                        <span>Filters</span>
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <!-- Search -->
                        <div class="space-y-2">
                            <label class="text-sm font-medium">Search</label>
                            <div class="relative">
                                <Search class="absolute left-3 top-3 h-4 w-4 text-gray-400" />
                                <Input
                                    v-model="searchQuery"
                                    placeholder="Search orders, customers, beneficiaries..."
                                    class="pl-10"
                                    @keyup.enter="applyFilters"
                                />
                            </div>
                        </div>

                        <!-- Status Filter -->
                        <div class="space-y-2">
                            <label class="text-sm font-medium">Status</label>
                            <select
                                v-model="selectedStatus"
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                            >
                                <option value="">All Statuses</option>
                                <option v-for="status in statuses" :key="status.value" :value="status.value">
                                    {{ status.label }}
                                </option>
                            </select>
                        </div>

                        <!-- Type Filter -->
                        <div class="space-y-2">
                            <label class="text-sm font-medium">Order Type</label>
                            <select
                                v-model="selectedDonationType"
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                            >
                                <option value="">All Types</option>
                                <option value="0">Purchases Only</option>
                                <option value="1">Donations Only</option>
                            </select>
                        </div>

                        <!-- NEW: Beneficiary Filter -->
                        <div class="space-y-2">
                            <label class="text-sm font-medium">Beneficiary Status</label>
                            <select
                                v-model="selectedBeneficiaryFilter"
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                            >
                                <option value="">All</option>
                                <option value="1">With Beneficiary</option>
                                <option value="0">Without Beneficiary</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex space-x-2 mt-4">
                        <Button @click="applyFilters" class="flex items-center space-x-2">
                            <Search class="h-4 w-4" />
                            <span>Apply Filters</span>
                        </Button>
                        <Button @click="clearFilters" variant="outline">
                            Clear All
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <!-- ENHANCED: Orders Table -->
            <Card>
                <CardHeader>
                    <CardTitle>Orders List</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="overflow-x-auto">
                        <table class="w-full border-collapse">
                            <thead>
                            <tr class="border-b">
                                <th class="text-left p-3 font-medium">ID</th>
                                <th class="text-left p-3 font-medium">Customer</th>
                                <th class="text-left p-3 font-medium">Type</th>
                                <!-- NEW: Beneficiary Column -->
                                <th class="text-left p-3 font-medium">Beneficiary</th>
                                <th class="text-left p-3 font-medium">Amount</th>
                                <th class="text-left p-3 font-medium">Status</th>
                                <th class="text-left p-3 font-medium">Date</th>
                                <th class="text-left p-3 font-medium">Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr v-for="order in orders.data" :key="order.id" class="border-b hover:bg-gray-50">
                                <!-- Order ID -->
                                <td class="p-3">
                                    <span class="font-mono text-sm">#{{ order.id }}</span>
                                </td>

                                <!-- Customer -->
                                <td class="p-3">
                                    <div>
                                        <p class="font-medium">{{ order.customer?.first_name }} {{ order.customer?.last_name }}</p>
                                        <p class="text-sm text-gray-600">{{ order.customer?.user?.email }}</p>
                                    </div>
                                </td>

                                <!-- Type -->
                                <td class="p-3">
                                    <div class="flex items-center space-x-2">
                                        <component :is="order.is_donation ? Heart : ShoppingCart"
                                                   :class="`h-4 w-4 ${order.is_donation ? 'text-red-600' : 'text-blue-600'}`" />
                                        <span :class="`text-sm font-medium ${order.is_donation ? 'text-red-600' : 'text-blue-600'}`">
                                                {{ order.is_donation ? 'Donation' : 'Purchase' }}
                                            </span>
                                    </div>
                                </td>

                                <!-- NEW: Beneficiary -->
                                <td class="p-3">
                                    <div v-if="order.is_donation">
                                        <div v-if="order.beneficiary" class="flex items-center space-x-2">
                                            <component :is="order.beneficiary.is_organization ? Users : User"
                                                       class="h-4 w-4 text-purple-600" />
                                            <div>
                                                <p class="text-sm font-medium">{{ getBeneficiaryDisplay(order) }}</p>
                                                <p class="text-xs text-gray-500">
                                                    {{ order.beneficiary.is_organization ? 'Organization' : 'Individual' }}
                                                    <span v-if="!order.beneficiary.is_organization && order.beneficiary.relationship_to_donor">
                                                            ({{ order.beneficiary.relationship_to_donor }})
                                                        </span>
                                                </p>
                                            </div>
                                        </div>
                                        <span v-else class="text-sm text-gray-500 italic">Not specified</span>
                                    </div>
                                    <span v-else class="text-sm text-gray-400">-</span>
                                </td>

                                <!-- Amount -->
                                <td class="p-3">
                                    <span class="font-bold">{{ formatPrice(order.total) }}</span>
                                </td>

                                <!-- Status -->
                                <td class="p-3">
                                        <span :class="`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ${getStatusClass(order.status)}`">
                                            {{ order.status.charAt(0).toUpperCase() + order.status.slice(1) }}
                                        </span>
                                </td>

                                <!-- Date -->
                                <td class="p-3">
                                    <span class="text-sm text-gray-600">{{ formatDate(order.created_at) }}</span>
                                </td>

                                <!-- FIXED: Actions with proper navigation -->
                                <td class="p-3">
                                    <Button
                                        @click="viewOrder(order.id)"
                                        variant="outline"
                                        size="sm"
                                        class="flex items-center space-x-1 cursor-pointer"
                                    >
                                        <Eye class="h-3 w-3" />
                                        <span>View</span>
                                    </Button>
                                </td>
                            </tr>
                            </tbody>
                        </table>

                        <!-- Empty State -->
                        <div v-if="orders.data.length === 0" class="text-center py-12">
                            <ShoppingCart class="h-12 w-12 text-gray-400 mx-auto mb-4" />
                            <h3 class="text-lg font-medium text-gray-900 mb-2">No orders found</h3>
                            <p class="text-gray-600">Try adjusting your search filters or check back later.</p>
                        </div>
                    </div>

                    <!-- Pagination -->
                    <div v-if="orders.links" class="flex justify-center mt-6">
                        <nav class="flex space-x-1">
                            <template v-for="link in orders.links" :key="link.label">
                                <button
                                    v-if="link.url"
                                    @click="router.get(link.url)"
                                    :class="`px-3 py-2 text-sm rounded-md ${
                                        link.active
                                            ? 'bg-blue-600 text-white font-medium'
                                            : 'bg-white text-gray-700 hover:bg-gray-50 border border-gray-300'
                                    }`"
                                    v-html="link.label"
                                />
                                <span
                                    v-else
                                    :class="`px-3 py-2 text-sm rounded-md ${
                                        link.active
                                            ? 'bg-blue-600 text-white font-medium'
                                            : 'bg-gray-100 text-gray-400 cursor-not-allowed'
                                    }`"
                                    v-html="link.label"
                                />
                            </template>
                        </nav>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AdminLayout>
</template>
