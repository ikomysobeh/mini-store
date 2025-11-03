<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import {
    Package, Plus, Edit, Trash2, Eye, ArrowUpDown,
    Heart, AlertCircle, CheckCircle, Clock
} from 'lucide-vue-next';
import { computed } from 'vue';

interface Product {
    id: number;
    name: string;
    slug: string;
    price: number;
    original_price?: number;
    stock: number;
    is_active: boolean;
    is_donatable: boolean;
    image?: string;
    created_at: string;
    category?: {
        name: string;
    };
}

interface Props {
    products: Product[];
    selectedProducts: number[];
    sortBy: string;
    sortDirection: string;
    searchTerm?: string;
    selectedCategory?: string;
    selectedStatus?: string;
    currency?: string;
    locale?: string;
}

interface Emits {
    (e: 'update:selectedProducts', value: number[]): void;
    (e: 'toggleSort', column: string): void;
    (e: 'deleteProduct', productId: number): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

// Helper functions
const formatPrice = (price: number) => {
    return new Intl.NumberFormat(props.locale || 'nl-NL', {
        style: 'currency',
        currency: props.currency || 'EUR'
    }).format(price || 0);
};

const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString(props.locale || 'nl-NL', {
        day: '2-digit',
        month: 'short',
        year: 'numeric'
    });
};

const getStatusColor = (isActive: boolean, stock: number) => {
    if (!isActive) return 'bg-red-100 text-red-800 border-red-200';
    if (stock === 0) return 'bg-yellow-100 text-yellow-800 border-yellow-200';
    return 'bg-green-100 text-green-800 border-green-200';
};

const getStatusText = (isActive: boolean, stock: number) => {
    if (!isActive) return 'Inactive';
    if (stock === 0) return 'Out of Stock';
    return 'Active';
};

const getStatusIcon = (isActive: boolean, stock: number) => {
    if (!isActive) return AlertCircle;
    if (stock === 0) return Clock;
    return CheckCircle;
};

const getStockColor = (stock: number) => {
    if (stock === 0) return 'text-red-600';
    if (stock < 10) return 'text-orange-600';
    return 'text-green-600';
};

// Computed
const isAllSelected = computed(() => {
    return props.products.length > 0 && props.selectedProducts.length === props.products.length;
});

const isIndeterminate = computed(() => {
    return props.selectedProducts.length > 0 && props.selectedProducts.length < props.products.length;
});

const hasFilters = computed(() => {
    return props.searchTerm || props.selectedCategory || props.selectedStatus;
});

// Event handlers
const toggleSelectAll = () => {
    if (isAllSelected.value) {
        emit('update:selectedProducts', []);
    } else {
        emit('update:selectedProducts', props.products.map(product => product.id));
    }
};

const toggleProduct = (productId: number) => {
    const currentSelected = [...props.selectedProducts];
    const index = currentSelected.indexOf(productId);

    if (index > -1) {
        currentSelected.splice(index, 1);
    } else {
        currentSelected.push(productId);
    }

    emit('update:selectedProducts', currentSelected);
};
</script>

<template>
    <Card class="shadow-sm">
        <CardContent class="p-0">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <!-- Table Header -->
                    <thead class="border-b bg-muted/30">
                    <tr>
                        <th class="p-4 text-left">
                            <input
                                type="checkbox"
                                :checked="isAllSelected"
                                :indeterminate="isIndeterminate"
                                @change="toggleSelectAll"
                                class="rounded border-border"
                            />
                        </th>
                        <th class="p-4 text-left">
                            <button
                                @click="emit('toggleSort', 'name')"
                                class="flex items-center space-x-1 hover:text-primary font-medium"
                            >
                                <span>Product</span>
                                <ArrowUpDown class="h-4 w-4" />
                            </button>
                        </th>
                        <th class="p-4 text-left font-medium">Category</th>
                        <th class="p-4 text-left">
                            <button
                                @click="emit('toggleSort', 'price')"
                                class="flex items-center space-x-1 hover:text-primary font-medium"
                            >
                                <span>Price</span>
                                <ArrowUpDown class="h-4 w-4" />
                            </button>
                        </th>
                        <th class="p-4 text-left">
                            <button
                                @click="emit('toggleSort', 'stock')"
                                class="flex items-center space-x-1 hover:text-primary font-medium"
                            >
                                <span>Stock</span>
                                <ArrowUpDown class="h-4 w-4" />
                            </button>
                        </th>
                        <th class="p-4 text-left font-medium">Status</th>
                        <th class="p-4 text-left">
                            <button
                                @click="emit('toggleSort', 'created_at')"
                                class="flex items-center space-x-1 hover:text-primary font-medium"
                            >
                                <span>Created</span>
                                <ArrowUpDown class="h-4 w-4" />
                            </button>
                        </th>
                        <th class="p-4 text-right font-medium">Actions</th>
                    </tr>
                    </thead>

                    <!-- Table Body -->
                    <tbody>
                    <tr
                        v-for="product in products"
                        :key="product.id"
                        class="border-b hover:bg-muted/30 transition-colors"
                    >
                        <!-- Checkbox -->
                        <td class="p-4">
                            <input
                                type="checkbox"
                                :checked="selectedProducts.includes(product.id)"
                                @change="toggleProduct(product.id)"
                                class="rounded border-border"
                            />
                        </td>

                        <!-- Product Info -->
                        <td class="p-4">
                            <div class="flex items-center space-x-3">
                                <div class="flex-shrink-0">
                                    <img
                                        :src="product.image || '/placeholder-product.jpg'"
                                        :alt="product.name"
                                        class="w-12 h-12 object-cover rounded-lg border"
                                    />
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="font-medium text-sm truncate">{{ product.name }}</p>
                                    <div class="flex items-center space-x-2 text-xs text-muted-foreground">
                                        <span>ID: {{ product.id }}</span>
                                        <Badge v-if="product.is_donatable" variant="outline" class="text-xs">
                                            <Heart class="h-3 w-3 mr-1 text-red-500" />
                                            Donation
                                        </Badge>
                                    </div>
                                </div>
                            </div>
                        </td>

                        <!-- Category -->
                        <td class="p-4">
                            <Badge variant="outline" class="text-xs">
                                {{ product.category?.name || 'Uncategorized' }}
                            </Badge>
                        </td>

                        <!-- Price -->
                        <td class="p-4">
                            <div class="font-medium">{{ formatPrice(product.price) }}</div>
                            <div v-if="product.original_price && product.original_price > product.price"
                                 class="text-xs text-muted-foreground line-through">
                                {{ formatPrice(product.original_price) }}
                            </div>
                        </td>

                        <!-- Stock -->
                        <td class="p-4">
                            <div :class="['font-medium', getStockColor(product.stock)]">
                                {{ product.stock || 0 }}
                            </div>
                            <div v-if="product.stock < 10 && product.stock > 0" class="text-xs text-orange-600">
                                Low stock
                            </div>
                        </td>

                        <!-- Status -->
                        <td class="p-4">
                            <Badge
                                :class="getStatusColor(product.is_active, product.stock)"
                                class="text-xs px-2 py-1 border flex items-center w-fit"
                            >
                                <component
                                    :is="getStatusIcon(product.is_active, product.stock)"
                                    class="h-3 w-3 mr-1"
                                />
                                {{ getStatusText(product.is_active, product.stock) }}
                            </Badge>
                        </td>

                        <!-- Created Date -->
                        <td class="p-4">
                            <div class="text-sm">{{ formatDate(product.created_at) }}</div>
                        </td>

                        <!-- Actions -->
                        <td class="p-4">
                            <div class="flex items-center justify-end space-x-1">
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    as="a"
                                    :href="`/products/${product.slug}`"
                                    target="_blank"
                                    title="View Product"
                                    class="h-8 w-8 p-0"
                                >
                                    <Eye class="h-4 w-4" />
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    as="a"
                                    :href="`/admin/products/${product.id}/edit`"
                                    title="Edit Product"
                                    class="h-8 w-8 p-0"
                                >
                                    <Edit class="h-4 w-4" />
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    @click="emit('deleteProduct', product.id)"
                                    class="h-8 w-8 p-0 text-red-600 hover:text-red-700 hover:bg-red-50"
                                    title="Delete Product"
                                >
                                    <Trash2 class="h-4 w-4" />
                                </Button>
                            </div>
                        </td>
                    </tr>
                    </tbody>
                </table>

                <!-- Empty State -->
                <div v-if="products.length === 0" class="text-center py-16">
                    <div class="w-24 h-24 bg-muted rounded-full flex items-center justify-center mx-auto mb-6">
                        <Package class="h-12 w-12 text-muted-foreground" />
                    </div>
                    <h3 class="text-xl font-semibold mb-2">No products found</h3>
                    <p class="text-muted-foreground mb-6 max-w-md mx-auto">
                        {{ hasFilters ? 'Try adjusting your filters to find what you\'re looking for.' : 'Get started by adding your first product to the catalog.' }}
                    </p>
                    <Button as="a" href="/admin/products/create" size="lg">
                        <Plus class="h-5 w-5 mr-2" />
                        {{ hasFilters ? 'Add Product' : 'Add First Product' }}
                    </Button>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
