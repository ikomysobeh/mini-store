<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import Navbar from '@/components/Navbar.vue';
import OrderCard from '@/components/orders/OrderCard.vue';
import OrderFilters from '@/components/orders/OrderFilters.vue';
import OrderStats from '@/components/orders/OrderStats.vue';
import Pagination from '@/components/common/Pagination.vue';
import { Package } from 'lucide-vue-next';
import { ref, computed, watch } from 'vue';

// Only get the data specific to this page
const { orders, stats, filters } = defineProps({
    orders: { type: Object, required: true },
    stats: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
});

// DEBUG: Log initial data
console.log('🔍 Orders Index - Initial Data:', {
    orders: orders,
    stats: stats,
    filters: filters
});

// Get shared data from Inertia middleware
const page = usePage();
const auth = computed(() => page.props.auth);
const user = computed(() => auth.value?.user);
const cartItems = computed(() => page.props.cartItems || []);
const categories = computed(() => page.props.categories || []);
const settings = computed(() => page.props.settings || {});

const siteName = computed(() => settings.value.site_name || 'Elegant Store');

// Filter state with proper defaults
const searchTerm = ref(filters.search || '');
const selectedStatus = ref(filters.status || '');
const selectedType = ref(filters.type || '');
const selectedDateRange = ref(filters.date_range || '');

// DEBUG: Watch filter changes
watch([searchTerm, selectedStatus, selectedType, selectedDateRange], ([search, status, type, dateRange]) => {
    console.log('🔄 Filter values changed:', {
        search,
        status,
        type,
        dateRange
    });
}, { deep: true });

// Apply filters
const applyFilters = () => {
    const params = {};
    if (searchTerm.value) params.search = searchTerm.value;
    if (selectedStatus.value) params.status = selectedStatus.value;
    if (selectedType.value) params.type = selectedType.value;
    if (selectedDateRange.value) params.date_range = selectedDateRange.value;

    // DEBUG: Log filter parameters
    console.log('🚀 Applying filters with params:', params);
    console.log('🌐 Current URL params will be:', new URLSearchParams(params).toString());

    router.get('/my-orders', params, {
        preserveState: true,
        preserveScroll: true,
        onStart: () => {
            console.log('🔄 Filter request started');
        },
        onSuccess: (page) => {
            console.log('✅ Filter request successful:', page.props);
        },
        onError: (errors) => {
            console.error('❌ Filter request failed:', errors);
        },
        onFinish: () => {
            console.log('🏁 Filter request finished');
        }
    });
};

const clearFilters = () => {
    console.log('🧹 Clearing all filters');
    searchTerm.value = '';
    selectedStatus.value = '';
    selectedType.value = '';
    selectedDateRange.value = '';

    router.get('/my-orders', {}, {
        onSuccess: () => {
            console.log('✅ Filters cleared successfully');
        }
    });
};

const goToPage = (url) => {
    if (url) {
        console.log('📄 Navigating to page:', url);
        router.visit(url, {
            preserveState: true,
            preserveScroll: false,
        });
    }
};
</script>

<template>
    <div class="min-h-screen bg-background">
        <Head title="My Orders" />

        <!-- Use shared data from middleware -->
        <Navbar
            :categories="categories"
            :cartItems="cartItems"
            :user="user"
            :siteName="siteName"
            :settings="settings"
        />

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">



            <!-- Page Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-foreground mb-2">My Orders</h1>
                <p class="text-muted-foreground">Track and manage your order history</p>
            </div>

            <!-- Order Statistics -->
            <OrderStats :stats="stats" class="mb-8" />

            <!-- Filters -->
            <OrderFilters
                v-model:search="searchTerm"
                v-model:status="selectedStatus"
                v-model:type="selectedType"
                v-model:date-range="selectedDateRange"
                @apply="applyFilters"
                @clear="clearFilters"
                class="mb-8"
            />

            <!-- Orders List -->
            <div class="space-y-6">
                <div v-if="orders.data.length === 0" class="text-center py-12">
                    <Package class="h-16 w-16 text-muted-foreground mx-auto mb-4" />
                    <h3 class="text-lg font-medium text-foreground mb-2">No Orders Found</h3>
                    <p class="text-muted-foreground mb-4">
                        {{ Object.values(filters).some(f => f) ? 'No orders match your current filters.' : "You haven't placed any orders yet." }}
                    </p>
                    <button
                        v-if="Object.values(filters).some(f => f)"
                        @click="clearFilters"
                        class="px-4 py-2 bg-primary text-primary-foreground rounded-md hover:bg-primary/90"
                    >
                        Clear Filters
                    </button>
                </div>

                <div v-else>
                    <OrderCard
                        v-for="order in orders.data"
                        :key="order.id"
                        :order="order"
                    />
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="orders.data.length > 0" class="mt-8">
                <Pagination
                    :links="orders.links"
                    @go-to-page="goToPage"
                />
            </div>

        </div>
    </div>
</template>
