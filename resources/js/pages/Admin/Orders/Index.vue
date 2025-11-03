<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import PageHeader from '@/components/admin/PageHeader.vue';
import StatsCards from '@/components/admin/StatsCards.vue';
import OrdersFilters from '@/components/admin/OrdersFilters.vue';
import BulkActions from '@/components/admin/BulkActions.vue';
import OrdersTable from '@/components/admin/OrdersTable.vue';
import Pagination from '@/components/admin/Pagination.vue';
import { ShoppingCart, Filter, Download, RefreshCw } from 'lucide-vue-next';
import { ref, computed, watch } from 'vue';

const { orders, filters, stats } = defineProps({
    orders: {
        type: Object,
        required: true
    },
    filters: {
        type: Object,
        default: () => ({})
    },
    stats: {
        type: Object,
        default: () => ({
            total: 0,
            pending: 0,
            processing: 0,
            shipped: 0,
            delivered: 0,
            cancelled: 0,
            total_revenue: 0,
            donations_total: 0
        })
    }
});

// State
const searchTerm = ref(filters.search || '');
const selectedStatus = ref(filters.status || '');
const selectedType = ref(filters.type || '');
const dateRange = ref(filters.date_range || '');
const sortBy = ref(filters.sort || 'created_at');
const sortDirection = ref(filters.direction || 'desc');
const selectedOrders = ref([]);
const showFilters = ref(false);

// FIXED: Export selected orders function
function exportSelectedOrders() {
    if (selectedOrders.value.length === 0) {
        alert('Please select orders to export');
        return;
    }

    // Use form submission for POST request
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '/admin/orders/export-selected';
    form.style.display = 'none';

    // Add CSRF token
    const csrfToken = document.querySelector('meta[name="csrf-token"]');
    if (csrfToken) {
        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = '_token';
        csrfInput.value = csrfToken.getAttribute('content');
        form.appendChild(csrfInput);
    }

    // Add selected order IDs
    selectedOrders.value.forEach(orderId => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'order_ids[]';
        input.value = orderId;
        form.appendChild(input);
    });

    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);

    // Clear selection after export
    selectedOrders.value = [];
}

// Export all orders with current filters
function exportOrders() {
    const params = new URLSearchParams();
    if (searchTerm.value) params.append('search', searchTerm.value);
    if (selectedStatus.value) params.append('status', selectedStatus.value);
    if (selectedType.value) params.append('type', selectedType.value);
    if (dateRange.value) params.append('date_range', dateRange.value);

    // Open in new window to trigger download
    window.open(`/admin/orders/export?${params.toString()}`, '_blank');
}

// Header actions
const headerActions = [
    {
        label: 'Filters',
        icon: Filter,
        variant: 'outline',
        onClick: () => { showFilters.value = !showFilters.value; }
    },
    {
        label: 'Export All',
        icon: Download,
        variant: 'outline',
        onClick: exportOrders // FIXED: Now properly connected
    },
    {
        label: 'Refresh',
        icon: RefreshCw,
        variant: 'outline',
        onClick: applyFilters
    }
];

// FIXED: Bulk actions now properly connected
const bulkActions = [
    {
        label: 'Export Selected',
        icon: Download,
        onClick: exportSelectedOrders // FIXED: Now properly connected instead of console.log
    }
];

// Actions
function applyFilters() {
    const params = {};

    if (searchTerm.value) params.search = searchTerm.value;
    if (selectedStatus.value) params.status = selectedStatus.value;
    if (selectedType.value) params.type = selectedType.value;
    if (dateRange.value) params.date_range = dateRange.value;
    if (sortBy.value) params.sort = sortBy.value;
    if (sortDirection.value) params.direction = sortDirection.value;

    router.get('/admin/orders', params, {
        preserveState: true,
        preserveScroll: true,
    });
}

function clearFilters() {
    searchTerm.value = '';
    selectedStatus.value = '';
    selectedType.value = '';
    dateRange.value = '';
    sortBy.value = 'created_at';
    sortDirection.value = 'desc';
    router.get('/admin/orders');
}

function toggleSort(column: string) {
    if (sortBy.value === column) {
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortBy.value = column;
        sortDirection.value = 'asc';
    }
    applyFilters();
}

function updateOrderStatus(orderId: number, status: string) {
    router.patch(`/admin/orders/${orderId}/status`, {
        status: status
    }, {
        preserveScroll: true
    });
}

function goToPage(url: string) {
    if (url) {
        router.visit(url, {
            preserveState: true,
            preserveScroll: false,
        });
    }
}

function cancelBulkSelection() {
    selectedOrders.value = [];
}

// Watch for search changes
let searchTimeout;
watch(searchTerm, (newValue) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        applyFilters();
    }, 500);
});

watch([selectedStatus, selectedType, dateRange], () => {
    applyFilters();
});
</script>

<template>
    <AdminLayout
        title="Orders Management"
        :breadcrumbs="breadcrumbs"
    >
        <Head title="Orders Management" />

        <!-- Page Header Component -->
        <PageHeader
            title="Orders"
            description="Manage customer orders and donations"
            :icon="ShoppingCart"
            icon-color="text-blue-600"
            :actions="headerActions"
        />

        <div class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

            <!-- Stats Cards Component -->
            <StatsCards
                :stats="stats"
                currency="USD"
                locale="en-US"
            />

            <!-- Filters Component -->
            <OrdersFilters
                v-model:search-term="searchTerm"
                v-model:selected-status="selectedStatus"
                v-model:selected-type="selectedType"
                v-model:date-range="dateRange"
                :show-filters="showFilters"
                @clear-filters="clearFilters"
                @apply-filters="applyFilters"
            />

            <!-- Bulk Actions Component -->
            <BulkActions
                :selected-count="selectedOrders.length"
                item-name="order"
                :actions="bulkActions"
                @cancel="cancelBulkSelection"
            />

            <!-- Orders Table Component -->
            <OrdersTable
                :orders="orders.data"
                v-model:selected-orders="selectedOrders"
                :sort-by="sortBy"
                :sort-direction="sortDirection"
                currency="USD"
                locale="en-US"
                @toggle-sort="toggleSort"
                @update-order-status="updateOrderStatus"
            />

            <!-- Pagination Component -->
            <Pagination
                :links="orders.links"
                @go-to-page="goToPage"
            />

        </div>
    </AdminLayout>
</template>
