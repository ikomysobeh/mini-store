<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import PageHeader from '@/components/admin/PageHeader.vue';
import SearchBar from '@/components/admin/SearchBar.vue';
import BulkActions from '@/components/admin/BulkActions.vue';
import CategoriesTable from '@/components/admin/CategoriesTable.vue';
import SummaryStats from '@/components/admin/SummaryStats.vue';
import {
    FolderOpen, Plus, Trash2
} from 'lucide-vue-next';
import { ref, computed } from 'vue';
import AdminLayout from '@/layouts/AdminLayout.vue';

const { categories, filters } = defineProps({
    categories: {
        type: [Object, Array],
        default: () => ({ data: [], total: 0 })
    },
    filters: {
        type: Object,
        default: () => ({})
    }
});

// Ensure categories is properly structured
const categoriesData = computed(() => {
    if (Array.isArray(categories)) {
        return { data: categories, total: categories.length };
    }
    if (categories && typeof categories === 'object') {
        return {
            data: categories.data || [],
            total: categories.total || categories.data?.length || 0
        };
    }
    return { data: [], total: 0 };
});

// State
const searchTerm = ref(filters.search || '');
const selectedCategories = ref([]);

// Header configuration
const headerActions = [
    {
        label: 'Add Category',
        href: '/admin/categories/create',
        icon: Plus
    }
];

// Bulk actions configuration
const bulkActions = [
    {
        label: 'Delete Selected',
        icon: Trash2,
        variant: 'destructive',
        destructive: true,
        onClick: bulkDelete
    }
];

// Summary stats configuration
const summaryStats = computed(() => [
    {
        label: 'Total Categories',
        value: categoriesData.value.total || 0,
        color: 'primary',
        icon: FolderOpen
    },
    {
        label: 'Active',
        value: categoriesData.value.data.filter(c => c.is_active).length,
        color: 'green'
    },
    {
        label: 'Inactive',
        value: categoriesData.value.data.filter(c => !c.is_active).length,
        color: 'red'
    },
    {
        label: 'Total Products',
        value: categoriesData.value.data.reduce((sum, c) => sum + (c.products_count || 0), 0),
        color: 'blue'
    }
]);

// Event handlers
const handleSearch = (value: string) => {
    const params = {};
    if (value) params.search = value;

    router.get('/admin/categories', params, {
        preserveState: true,
        preserveScroll: true,
    });
};

const clearSearch = () => {
    searchTerm.value = '';
    router.get('/admin/categories', {}, {
        preserveState: true,
        preserveScroll: true,
    });
};

const toggleSelectAll = () => {
    const data = categoriesData.value.data;
    if (selectedCategories.value.length === data.length) {
        selectedCategories.value = [];
    } else {
        selectedCategories.value = data.map(category => category.id);
    }
};

const deleteCategory = (categoryId: number, categoryName: string) => {
    if (confirm(`Are you sure you want to delete "${categoryName}"? Products in this category will be uncategorized.`)) {
        router.delete(`/admin/categories/${categoryId}`, {
            preserveScroll: true
        });
    }
};

function bulkDelete() {
    if (selectedCategories.value.length === 0) return;

    if (confirm(`Are you sure you want to delete ${selectedCategories.value.length} categories?`)) {
        router.post('/admin/categories/bulk-delete', {
            category_ids: selectedCategories.value
        }, {
            preserveScroll: true,
            onSuccess: () => {
                selectedCategories.value = [];
            }
        });
    }
}

const cancelBulkSelection = () => {
    selectedCategories.value = [];
};
</script>

<template>
    <AdminLayout
        title="Orders Management"
        :breadcrumbs="breadcrumbs"
    >
    <div class="min-h-screen bg-muted/20">
        <Head title="Categories Management" />

        <!-- Page Header Component -->
        <PageHeader
            title="Categories"
            description="Manage product categories"
            :icon="FolderOpen"
            icon-color="text-purple-600"
            :actions="headerActions"
        />

        <div class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

            <!-- Search Bar Component -->
            <SearchBar
                v-model="searchTerm"
                placeholder="Search categories..."
                :show-clear="true"
                @search="handleSearch"
                @clear="clearSearch"
            />

            <!-- Bulk Actions Component -->
            <BulkActions
                :selected-count="selectedCategories.length"
                item-name="categor"
                :actions="bulkActions"
                @cancel="cancelBulkSelection"
            />

            <!-- Categories Table Component -->
            <CategoriesTable
                :categories="categoriesData.data"
                v-model:selected-categories="selectedCategories"
                :show-checkboxes="true"
                @toggle-select-all="toggleSelectAll"
                @delete-category="deleteCategory"
            />

            <!-- Summary Stats Component -->
            <SummaryStats
                :stats="summaryStats"
                :columns="4"
            />

        </div>
    </div>
    </AdminLayout>
</template>
