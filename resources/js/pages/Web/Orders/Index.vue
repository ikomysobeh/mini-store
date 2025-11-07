<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import Navbar from '@/components/Navbar.vue';
import OrderCard from '@/components/orders/OrderCard.vue';
import OrderFilters from '@/components/orders/OrderFilters.vue';
import OrderStats from '@/components/orders/OrderStats.vue';
import Pagination from '@/components/common/Pagination.vue';
import { Package, ShoppingBag } from 'lucide-vue-next';
import { ref, computed, watch } from 'vue';

// ✅ FIX: Proper prop types with defaults
const props = defineProps({
    orders: { 
        type: Object, 
        required: true,
        // ✅ Default structure for empty orders
        default: () => ({
            data: [],
            links: [],
            meta: {
                current_page: 1,
                last_page: 1,
                per_page: 10,
                total: 0,
            }
        })
    },
    stats: { 
        type: Object, 
        required: true,
        // ✅ Default structure for empty stats
        default: () => ({
            total_orders: 0,
            total_purchases: 0,
            total_donations: 0,
            total_spent: 0,
            this_year_spent: 0,
            recent_orders: 0,
            orders_with_variants: 0,
            favorite_colors: [],
        })
    },
    filters: { 
        type: Object, 
        default: () => ({})
    },
});

// DEBUG: Log initial data
console.log('🔍 Orders Index - Initial Data:', {
    orders: props.orders,
    stats: props.stats,
    filters: props.filters
});

// Get shared data from Inertia middleware
const page = usePage();
const auth = computed(() => page.props.auth);
const user = computed(() => auth.value?.user);
const cartItems = computed(() => page.props.cartItems || []);
const categories = computed(() => page.props.categories || []);
const settings = computed(() => page.props.settings || {});

const siteName = computed(() => settings.value.site_name || 'Elegant Store');

// ✅ FIX: Safe access to orders data
const hasOrders = computed(() => {
    return props.orders && props.orders.data && props.orders.data.length > 0;
});

const hasFiltersApplied = computed(() => {
    return Object.values(props.filters).some(f => f && f !== '');
});

// Filter state with proper defaults
const searchTerm = ref(props.filters.search || '');
const selectedStatus = ref(props.filters.status || '');
const selectedType = ref(props.filters.type || '');
const selectedDateRange = ref(props.filters.date_range || '');

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
    const params: Record<string, any> = {};
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

const goToPage = (url: string) => {
    if (url) {
        console.log('📄 Navigating to page:', url);
        router.visit(url, {
            preserveState: true,
            preserveScroll: false,
        });
    }
};

const goToShop = () => {
    router.visit('/products');
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

            <!-- ✅ FIX: Only show stats if there are orders -->
            <OrderStats v-if="stats.total_orders > 0" :stats="stats" class="mb-8" />

            <!-- ✅ FIX: Only show filters if there are orders -->
            <OrderFilters
                v-if="stats.total_orders > 0 || hasFiltersApplied"
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
                <!-- ✅ IMPROVED: Better empty state handling -->
                <div v-if="!hasOrders" class="text-center py-16 bg-card rounded-lg border border-border">
                    <Package class="h-20 w-20 text-muted-foreground mx-auto mb-4" />
                    <h3 class="text-xl font-semibold text-card-foreground mb-2">
                        {{ hasFiltersApplied ? 'No Orders Found' : 'No Orders Yet' }}
                    </h3>
                    <p class="text-muted-foreground mb-6 max-w-md mx-auto">
                        {{ hasFiltersApplied 
                            ? 'No orders match your current filters. Try adjusting your search criteria.' 
                            : "You haven't placed any orders yet. Start shopping to see your orders here!" 
                        }}
                    </p>
                    
                    <!-- Action buttons -->
                    <div class="flex justify-center gap-4">
                        <button
                            v-if="hasFiltersApplied"
                            @click="clearFilters"
                            class="px-6 py-3 bg-secondary text-secondary-foreground rounded-md hover:bg-secondary/90 transition-colors"
                        >
                            Clear Filters
                        </button>
                        <button
                            v-else
                            @click="goToShop"
                            class="px-6 py-3 bg-primary text-primary-foreground rounded-md hover:bg-primary/90 transition-colors flex items-center gap-2"
                        >
                            <ShoppingBag class="h-5 w-5" />
                            Start Shopping
                        </button>
                    </div>
                </div>

                <!-- ✅ FIX: Safe rendering of orders -->
                <div v-else>
                    <OrderCard
                        v-for="order in orders.data"
                        :key="order.id"
                        :order="order"
                    />
                </div>
            </div>

            <!-- ✅ FIX: Only show pagination if there are orders -->
            <div v-if="hasOrders && orders.links" class="mt-8">
                <Pagination
                    :links="orders.links"
                    @go-to-page="goToPage"
                />
            </div>

        </div>
    </div>
</template>
