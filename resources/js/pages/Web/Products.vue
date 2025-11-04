<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import Navbar from '@/components/Navbar.vue';
import PageHeader from '@/components/products/PageHeader.vue';
import ProductFilters from '@/components/products/ProductFilters.vue';
import ProductsGrid from '@/components/products/ProductsGrid.vue';
import { ref, computed, watch, nextTick } from 'vue';

const { products, categories, filters, auth, cartItems, settings } = defineProps({
    products: { type: Object, required: true },
    categories: { type: Array, required: true },
    filters: { type: Object, default: () => ({}) },
    auth: { type: Object, default: () => ({}) },
    cartItems: { type: Array, default: () => [] },
    settings: { type: Object, default: () => ({}) },
});

const siteName = settings.site_name || 'Elegant Store';
const user = auth.user;

// Filter state
const searchTerm = ref(filters.search || '');
const selectedCategory = ref(filters.category || '');
const onlyDonatable = ref(filters.donatable || false);
const sortBy = ref(filters.sort || 'name');
const showFilters = ref(false);

// Computed properties
const hasActiveFilters = computed(() => {
    return searchTerm.value || selectedCategory.value || onlyDonatable.value;
});

const activeFilters = computed(() => {
    const filters = {};
    if (searchTerm.value) filters.search = searchTerm.value;
    if (selectedCategory.value) filters.category = selectedCategory.value;
    if (onlyDonatable.value) filters.donatable = onlyDonatable.value;
    return filters;
});

const filteredProducts = computed(() => {
    let result = products.data || [];

    // Client-side sorting
    switch (sortBy.value) {
        case 'price_asc':
            result = [...result].sort((a, b) => parseFloat(a.price) - parseFloat(b.price));
            break;
        case 'price_desc':
            result = [...result].sort((a, b) => parseFloat(b.price) - parseFloat(a.price));
            break;
        case 'name':
            result = [...result].sort((a, b) => a.name.localeCompare(b.name));
            break;
        case 'name_desc':
            result = [...result].sort((a, b) => b.name.localeCompare(a.name));
            break;
        case 'newest':
            result = [...result].sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
            break;
        case 'featured':
            result = [...result].sort((a, b) => (b.is_featured ? 1 : 0) - (a.is_featured ? 1 : 0));
            break;
    }

    return result;
});

// Filter functions
const applyFilters = () => {
    console.log('Applying filters:', {
        search: searchTerm.value,
        category: selectedCategory.value,
        donatable: onlyDonatable.value,
        sort: sortBy.value
    });

    const params = {};

    if (searchTerm.value) params.search = searchTerm.value;
    if (selectedCategory.value) params.category = selectedCategory.value;
    if (onlyDonatable.value) params.donatable = true;
    if (sortBy.value && sortBy.value !== 'name') params.sort = sortBy.value;

    router.get('/products', params, {
        preserveState: true,
        preserveScroll: true,
    });
};

// FIXED: Enhanced clear filters function
const clearFilters = () => {
    console.log('Clearing all filters');

    // Reset all reactive values
    searchTerm.value = '';
    selectedCategory.value = '';
    onlyDonatable.value = false;
    sortBy.value = 'name';

    // Force reactivity update
    nextTick(() => {
        console.log('After clearing - values:', {
            search: searchTerm.value,
            category: selectedCategory.value,
            donatable: onlyDonatable.value,
            sort: sortBy.value
        });

        // Navigate to clean URL
        router.get('/products', {}, {
            preserveState: false, // Don't preserve state to ensure fresh load
            preserveScroll: false,
        });
    });
};

const toggleFilters = () => {
    showFilters.value = !showFilters.value;
};

const goToPage = (url) => {
    if (url) {
        router.visit(url, {
            preserveState: true,
            preserveScroll: false,
        });
    }
};

// Watch for changes with debounce
let searchTimeout;
watch(searchTerm, (newValue) => {
    console.log('Search term changed to:', newValue);
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        applyFilters();
    }, 500);
});

watch([selectedCategory, onlyDonatable, sortBy], ([newCategory, newDonatable, newSort]) => {
    console.log('Filters changed:', {
        category: newCategory,
        donatable: newDonatable,
        sort: newSort
    });
    applyFilters();
});
</script>

<template>
    <div class="min-h-screen bg-background text-foreground">
        <Head title="Products" />

        <!-- Navbar -->
        <Navbar
            :categories="categories"
            :cartItems="cartItems"
            :user="user"
            :siteName="siteName"
            :settings="settings"
        />

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Page Header Component -->
            <PageHeader
                :totalProducts="products.total || 0"
                :selectedCategory="selectedCategory"
                :categories="categories"
            />

            <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">

                <!-- Sidebar Filters Component -->
                <div class="lg:col-span-1">
                    <ProductFilters
                        :categories="categories"
                        v-model:searchTerm="searchTerm"
                        v-model:selectedCategory="selectedCategory"
                        v-model:onlyDonatable="onlyDonatable"
                        v-model:sortBy="sortBy"
                        :showFilters="showFilters"
                        @toggleFilters="toggleFilters"
                        @clearFilters="clearFilters"
                    />
                </div>

                <!-- Products Grid Component -->
                <div class="lg:col-span-3">
                    <ProductsGrid
                        :products="filteredProducts"
                        :paginationLinks="products.links"
                        :hasActiveFilters="hasActiveFilters"
                        :activeFilters="activeFilters"
                        :categories="categories"
                        :user="user"
                        @clearFilters="clearFilters"
                        @goToPage="goToPage"
                    />
                </div>

            </div>
        </div>
    </div>
</template>
