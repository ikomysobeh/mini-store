<!-- resources/js/Pages/Admin/Products/Index.vue -->
<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Package,
    Filter,
    Download,
    Plus,
    Trash2,
    Search,
    Eye,
    Edit,
    MoreHorizontal,
    ArrowUpDown,
    ArrowUp,
    ArrowDown
} from 'lucide-vue-next';
import { ref, computed, watch } from 'vue';

const { products, categories, filters, stats, error } = defineProps({
    products: { type: Object, required: true },
    categories: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
    stats: { type: Object, default: () => ({}) },
    error: { type: String, default: null }
});

// State
const searchTerm = ref(filters.search || '');
const selectedCategory = ref(filters.category || '');
const selectedStatus = ref(filters.status || '');
const sortBy = ref(filters.sort || 'created_at');
const sortDirection = ref(filters.direction || 'desc');
const selectedProducts = ref([]);
const showFilters = ref(false);

// Status options
const statusOptions = [
    { value: '', label: 'All Status' },
    { value: 'active', label: 'Active' },
    { value: 'inactive', label: 'Inactive' },
    { value: 'donatable', label: 'Donatable' },
    { value: 'has_variants', label: 'Has Variants' },
    { value: 'no_variants', label: 'No Variants' },
    { value: 'low_stock', label: 'Low Stock' },
    { value: 'out_of_stock', label: 'Out of Stock' },
];

// Header actions
const headerActions = [
    {
        label: 'Export All',
        icon: Download,
        variant: 'outline',
        onClick: exportProducts
    },
    {
        label: 'Add Product',
        icon: Plus,
        href: '/admin/products/create'
    }
];

// Computed
const hasSelectedProducts = computed(() => selectedProducts.value.length > 0);

// Methods

function viewProduct(productId: number) {
    router.visit(`/admin/products/${productId}`, {
        preserveState: false,
        preserveScroll: false,
    });
}

function editProduct(productId: number) {
    router.visit(`/admin/products/${productId}/edit`, {
        preserveState: false,
        preserveScroll: false,
    });
}
function exportSelectedProducts() {
    if (selectedProducts.value.length === 0) {
        alert('Please select products to export');
        return;
    }

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '/admin/products/export-selected';
    form.style.display = 'none';

    // CSRF token
    const csrfToken = document.querySelector('meta[name="csrf-token"]');
    if (csrfToken) {
        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = '_token';
        csrfInput.value = csrfToken.getAttribute('content');
        form.appendChild(csrfInput);
    }

    // Product IDs
    selectedProducts.value.forEach(productId => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'product_ids[]';
        input.value = productId;
        form.appendChild(input);
    });

    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);

    selectedProducts.value = [];
}

function exportProducts() {
    const params = new URLSearchParams();
    if (searchTerm.value) params.append('search', searchTerm.value);
    if (selectedCategory.value) params.append('category', selectedCategory.value);
    if (selectedStatus.value) params.append('status', selectedStatus.value);

    window.open(`/admin/products/export?${params.toString()}`, '_blank');
}

function applyFilters() {
    const params = {};

    if (searchTerm.value) params.search = searchTerm.value;
    if (selectedCategory.value) params.category = selectedCategory.value;
    if (selectedStatus.value) params.status = selectedStatus.value;
    if (sortBy.value) params.sort = sortBy.value;
    if (sortDirection.value) params.direction = sortDirection.value;

    router.get('/admin/products', params, {
        preserveState: true,
        preserveScroll: true,
    });
}

function clearFilters() {
    searchTerm.value = '';
    selectedCategory.value = '';
    selectedStatus.value = '';
    sortBy.value = 'created_at';
    sortDirection.value = 'desc';
    router.get('/admin/products');
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

function deleteProduct(productId: number) {
    if (confirm('Are you sure you want to delete this product?')) {
        router.delete(`/admin/products/${productId}`, {
            preserveScroll: true
        });
    }
}

function bulkDelete() {
    if (selectedProducts.value.length === 0) return;

    if (confirm(`Are you sure you want to delete ${selectedProducts.value.length} products?`)) {
        router.post('/admin/products/bulk-delete', {
            product_ids: selectedProducts.value
        }, {
            preserveScroll: true,
            onSuccess: () => {
                selectedProducts.value = [];
            }
        });
    }
}

function goToPage(url: string) {
    if (url) {
        router.visit(url, {
            preserveState: true,
            preserveScroll: false,
        });
    }
}

function selectAllProducts() {
    if (selectedProducts.value.length === products.data.length) {
        selectedProducts.value = [];
    } else {
        selectedProducts.value = products.data.map(p => p.id);
    }
}

function formatCurrency(value) {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD'
    }).format(value);
}

function getStatusBadgeColor(product) {
    if (!product.is_active) return 'bg-red-100 text-red-800';
    if (product.total_stock === 0) return 'bg-gray-100 text-gray-800';
    if (product.total_stock <= 10) return 'bg-yellow-100 text-yellow-800';
    return 'bg-green-100 text-green-800';
}

function getStatusText(product) {
    if (!product.is_active) return 'Inactive';
    if (product.total_stock === 0) return 'Out of Stock';
    if (product.total_stock <= 10) return 'Low Stock';
    return 'Active';
}

// Watch for search changes
let searchTimeout;
watch(searchTerm, (newValue) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        applyFilters();
    }, 500);
});

watch([selectedCategory, selectedStatus], () => {
    applyFilters();
});
</script>

<template>
    <AdminLayout title="Products Management">
        <Head title="Products Management" />

        <!-- Page Header -->
        <PageHeader
            title="Products"
            description="Manage your product catalog"
            :icon="Package"
            icon-color="text-primary"
            :actions="headerActions"
        />

        <div class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

            <!-- Error Alert -->
            <div v-if="error" class="mb-6 p-4 bg-red-50 border border-red-200 rounded-md">
                <div class="text-red-600 text-sm">{{ error }}</div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <Card>
                    <CardContent class="p-6">
                        <div class="flex items-center">
                            <div class="p-2 bg-primary/20 rounded-lg">
                                <Package class="h-6 w-6 text-primary" />
                            </div>
                            <div class="ml-4">
                                <p class="text-2xl font-bold">{{ stats.total || 0 }}</p>
                                <p class="text-sm text-muted-foreground">Total Products</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent class="p-6">
                        <div class="flex items-center">
                            <div class="p-2 bg-secondary/20 rounded-lg">
                                <Package class="h-6 w-6 text-secondary" />
                            </div>
                            <div class="ml-4">
                                <p class="text-2xl font-bold">{{ stats.active || 0 }}</p>
                                <p class="text-sm text-muted-foreground">Active Products</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent class="p-6">
                        <div class="flex items-center">
                            <div class="p-2 bg-accent/20 rounded-lg">
                                <Package class="h-6 w-6 text-accent" />
                            </div>
                            <div class="ml-4">
                                <p class="text-2xl font-bold">{{ stats.with_variants || 0 }}</p>
                                <p class="text-sm text-muted-foreground">With Variants</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent class="p-6">
                        <div class="flex items-center">
                            <div class="p-2 bg-warning/20 rounded-lg">
                                <Package class="h-6 w-6 text-warning" />
                            </div>
                            <div class="ml-4">
                                <p class="text-2xl font-bold">{{ formatCurrency(stats.total_value || 0) }}</p>
                                <p class="text-sm text-muted-foreground">Total Value</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Filters -->
            <Card class="mb-6">
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <CardTitle class="flex items-center">
                            <Filter class="h-5 w-5 mr-2" />
                            Filters
                        </CardTitle>
                        <Button
                            variant="ghost"
                            size="sm"
                            @click="showFilters = !showFilters"
                        >
                            {{ showFilters ? 'Hide' : 'Show' }}
                        </Button>
                    </div>
                </CardHeader>
                <CardContent v-if="showFilters" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <!-- Search -->
                        <div class="relative">
                            <Search class="absolute left-3 top-3 h-4 w-4 text-muted-foreground" />
                            <Input
                                v-model="searchTerm"
                                placeholder="Search products..."
                                class="pl-10"
                            />
                        </div>

                        <!-- Category -->
                        <select
                            v-model="selectedCategory"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                        >
                            <option value="">All Categories</option>
                            <option v-for="category in categories" :key="category.id" :value="category.id">
                                {{ category.name }}
                            </option>
                        </select>

                        <!-- Status -->
                        <select
                            v-model="selectedStatus"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                        >
                            <option v-for="status in statusOptions" :key="status.value" :value="status.value">
                                {{ status.label }}
                            </option>
                        </select>

                        <!-- Clear Filters -->
                        <Button variant="outline" @click="clearFilters">
                            Clear Filters
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <!-- Bulk Actions -->
            <Card v-if="hasSelectedProducts" class="mb-6">
                <CardContent class="p-4">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600">
                            {{ selectedProducts.length }} products selected
                        </span>
                        <div class="flex space-x-2">
                            <Button variant="outline" size="sm" @click="exportSelectedProducts">
                                <Download class="h-4 w-4 mr-2" />
                                Export Selected
                            </Button>
                            <Button variant="destructive" size="sm" @click="bulkDelete">
                                <Trash2 class="h-4 w-4 mr-2" />
                                Delete Selected
                            </Button>
                            <Button variant="ghost" size="sm" @click="selectedProducts = []">
                                Cancel
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Products Table -->
            <Card>
                <CardContent class="p-0">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="black">
                            <tr>
                                <th class="p-4 text-left">
                                    <Checkbox
                                        :checked="selectedProducts.length === products.data.length && products.data.length > 0"
                                        @update:checked="selectAllProducts"
                                    />
                                </th>
                                <th class="p-4 text-left">Image</th>
                                <th
                                    class="p-4 text-left cursor-pointer "
                                    @click="toggleSort('name')"
                                >
                                    <div class="flex items-center">
                                        Name
                                        <ArrowUpDown class="h-4 w-4 ml-1" />
                                    </div>
                                </th>
                                <th class="p-4 text-left">Category</th>
                                <th
                                    class="p-4 text-left cursor-pointer "
                                    @click="toggleSort('price')"
                                >
                                    <div class="flex items-center">
                                        Price
                                        <ArrowUpDown class="h-4 w-4 ml-1" />
                                    </div>
                                </th>
                                <th
                                    class="p-4 text-left cursor-pointer "
                                    @click="toggleSort('stock')"
                                >
                                    <div class="flex items-center">
                                        Stock
                                        <ArrowUpDown class="h-4 w-4 ml-1" />
                                    </div>
                                </th>
                                <th class="p-4 text-left">Variants</th>
                                <th class="p-4 text-left">Status</th>
                                <th class="p-4 text-left">Actions</th>
                            </tr>
                            </thead>
                            <tbody class="divide-y">
                            <tr v-for="product in products.data" :key="product.id" class="">
                                <td class="p-4">
                                    <Checkbox
                                        :checked="selectedProducts.includes(product.id)"
                                        @update:checked="(checked) => {
                                                if (checked) {
                                                    selectedProducts.push(product.id);
                                                } else {
                                                    selectedProducts = selectedProducts.filter(id => id !== product.id);
                                                }
                                            }"
                                    />
                                </td>
                                <td class="p-4">
                                    <div class="w-12 h-12 bg-gray-100 rounded-md overflow-hidden">
                                        <img
                                            v-if="product.image"
                                            :src="product.image"
                                            :alt="product.name"
                                            class="w-full h-full object-cover"
                                        />
                                        <div v-else class="w-full h-full flex items-center justify-center">
                                            <Package class="h-6 w-6 text-muted-foreground" />
                                        </div>
                                    </div>
                                </td>
                                <td class="p-4">
                                    <div>
                                        <div class="font-medium">{{ product.name }}</div>
                                        <div class="text-sm text-gray-600">{{ product.slug }}</div>
                                    </div>
                                </td>
                                <td class="p-4">
                                    <span class="text-sm text-muted-foreground">{{ product.category?.name || 'N/A' }}</span>
                                </td>
                                <td class="p-4">
                                    <div v-if="product.has_variants && product.price_range">
                                        <span class="text-sm">{{ formatCurrency(product.price_range.min) }}</span>
                                        <span class="text-gray-400"> - </span>
                                        <span class="text-sm">{{ formatCurrency(product.price_range.max) }}</span>
                                    </div>
                                    <div v-else>
                                        <span class="font-medium">{{ formatCurrency(product.price) }}</span>
                                    </div>
                                </td>
                                <td class="p-4">
                                    <span class="font-medium">{{ product.total_stock }}</span>
                                </td>
                                <td class="p-4">
                                    <div v-if="product.has_variants">
                                        <Badge class="mb-1">{{ product.variants_count }} variants</Badge>
                                        <div class="text-xs text-muted-foreground">
                                            <div>Colors: {{ product.available_colors?.join(', ') || 'None' }}</div>
                                            <div>Sizes: {{ product.available_sizes?.join(', ') || 'None' }}</div>
                                        </div>
                                    </div>
                                    <span v-else class="text-sm text-muted-foreground">No variants</span>
                                </td>
                                <td class="p-4">
                                    <Badge :class="getStatusBadgeColor(product)">
                                        {{ getStatusText(product) }}
                                    </Badge>
                                    <Badge v-if="product.is_donatable" class="ml-1 bg-blue-100 text-blue-800">
                                        Donatable
                                    </Badge>
                                </td>
                                <td class="p-4">
                                    <div class="flex space-x-2">
                                        <!-- View Button -->
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            @click="viewProduct(product.id)"
                                            title="View Product"
                                        >
                                            <Eye class="h-4 w-4" />
                                        </Button>

                                        <!-- Edit Button -->
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            @click="editProduct(product.id)"
                                            title="Edit Product"
                                        >
                                            <Edit class="h-4 w-4" />
                                        </Button>

                                        <!-- Delete Button -->
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            @click="deleteProduct(product.id)"
                                            class="text-red-600 hover:text-red-700"
                                            title="Delete Product"
                                        >
                                            <Trash2 class="h-4 w-4" />
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>

            <!-- Pagination -->
            <div v-if="products.links.length > 3" class="mt-6 flex justify-center">
                <nav class="flex space-x-1">
                    <Button
                        v-for="link in products.links"
                        :key="link.label"
                        :variant="link.active ? 'default' : 'outline'"
                        size="sm"
                        :disabled="!link.url"
                        @click="goToPage(link.url)"
                        v-html="link.label"
                    />
                </nav>
            </div>

        </div>
    </AdminLayout>
</template>
