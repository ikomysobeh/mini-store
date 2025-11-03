<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import {
    FolderOpen, Plus, Edit, Trash2, Eye,
    CheckCircle, AlertCircle
} from 'lucide-vue-next';
import { computed } from 'vue';

interface Category {
    id: number;
    name: string;
    slug: string;
    description?: string;
    image?: string;
    is_active: boolean;
    products_count: number;
    created_at: string;
}

interface Props {
    categories: Category[];
    selectedCategories: number[];
    showCheckboxes?: boolean;
}

interface Emits {
    (e: 'update:selectedCategories', value: number[]): void;
    (e: 'toggleSelectAll'): void;
    (e: 'deleteCategory', categoryId: number, categoryName: string): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

// Computed
const isAllSelected = computed(() => {
    return props.categories.length > 0 &&
        props.selectedCategories.length === props.categories.length;
});

const isIndeterminate = computed(() => {
    return props.selectedCategories.length > 0 &&
        props.selectedCategories.length < props.categories.length;
});

// Helper functions
const formatDate = (date: string) => {
    if (!date) return 'N/A';
    return new Date(date).toLocaleDateString('nl-NL', {
        day: '2-digit',
        month: 'short',
        year: 'numeric'
    });
};

const getStatusColor = (isActive: boolean) => {
    return isActive
        ? 'bg-green-100 text-green-800 border-green-200'
        : 'bg-red-100 text-red-800 border-red-200';
};

const getStatusIcon = (isActive: boolean) => {
    return isActive ? CheckCircle : AlertCircle;
};

// Event handlers
const toggleSelectAll = () => {
    emit('toggleSelectAll');
};

const toggleCategory = (categoryId: number) => {
    const currentSelected = [...props.selectedCategories];
    const index = currentSelected.indexOf(categoryId);

    if (index > -1) {
        currentSelected.splice(index, 1);
    } else {
        currentSelected.push(categoryId);
    }

    emit('update:selectedCategories', currentSelected);
};

const deleteCategory = (categoryId: number, categoryName: string) => {
    emit('deleteCategory', categoryId, categoryName);
};
</script>

<template>
    <Card class="shadow-sm">
        <CardContent class="p-0">
            <div class="overflow-x-auto">
                <table class="w-full" v-if="categories.length > 0">
                    <!-- Table Header -->
                    <thead class="border-b bg-muted/30">
                    <tr>
                        <th v-if="showCheckboxes" class="p-4 text-left w-12">
                            <input
                                type="checkbox"
                                :checked="isAllSelected"
                                :indeterminate="isIndeterminate"
                                @change="toggleSelectAll"
                                class="rounded border-border"
                            />
                        </th>
                        <th class="p-4 text-left font-medium">Name</th>
                        <th class="p-4 text-left font-medium">Slug</th>
                        <th class="p-4 text-left font-medium">Products</th>
                        <th class="p-4 text-left font-medium">Status</th>
                        <th class="p-4 text-left font-medium">Created</th>
                        <th class="p-4 text-right font-medium">Actions</th>
                    </tr>
                    </thead>

                    <!-- Table Body -->
                    <tbody>
                    <tr
                        v-for="category in categories"
                        :key="category.id"
                        class="border-b hover:bg-muted/30 transition-colors"
                    >
                        <!-- Checkbox -->
                        <td v-if="showCheckboxes" class="p-4">
                            <input
                                type="checkbox"
                                :checked="selectedCategories.includes(category.id)"
                                @change="toggleCategory(category.id)"
                                class="rounded border-border"
                            />
                        </td>

                        <!-- Name & Description -->
                        <td class="p-4">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 rounded-lg overflow-hidden bg-muted flex-shrink-0">
                                    <img
                                        v-if="category.image"
                                        :src="category.image"
                                        :alt="category.name"
                                        class="w-full h-full object-cover"
                                    />
                                    <div v-else class="w-full h-full flex items-center justify-center">
                                        <FolderOpen class="h-5 w-5 text-muted-foreground" />
                                    </div>
                                </div>

                                <div class="min-w-0 flex-1">
                                    <p class="font-medium truncate">{{ category.name || 'Untitled' }}</p>
                                    <p v-if="category.description"
                                       class="text-sm text-muted-foreground truncate max-w-xs">
                                        {{ category.description }}
                                    </p>
                                </div>
                            </div>
                        </td>

                        <!-- Slug -->
                        <td class="p-4">
                            <code class="bg-muted px-2 py-1 rounded text-sm font-mono">
                                {{ category.slug || 'no-slug' }}
                            </code>
                        </td>

                        <!-- Product Count -->
                        <td class="p-4">
                            <Badge variant="outline" class="text-xs">
                                {{ category.products_count || 0 }} product{{ category.products_count !== 1 ? 's' : '' }}
                            </Badge>
                        </td>

                        <!-- Status -->
                        <td class="p-4">
                            <Badge
                                :class="getStatusColor(category.is_active)"
                                class="text-xs px-2 py-1 border flex items-center w-fit"
                            >
                                <component
                                    :is="getStatusIcon(category.is_active)"
                                    class="h-3 w-3 mr-1"
                                />
                                {{ category.is_active ? 'Active' : 'Inactive' }}
                            </Badge>
                        </td>

                        <!-- Created Date -->
                        <td class="p-4">
                            <div class="text-sm text-muted-foreground">
                                {{ formatDate(category.created_at) }}
                            </div>
                        </td>

                        <!-- Actions -->
                        <td class="p-4">
                            <div class="flex items-center justify-end space-x-1">
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    as="a"
                                    :href="`/products?category=${category.slug}`"
                                    target="_blank"
                                    title="View Category"
                                    class="h-8 w-8 p-0"
                                >
                                    <Eye class="h-4 w-4" />
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    as="a"
                                    :href="`/admin/categories/${category.id}/edit`"
                                    title="Edit Category"
                                    class="h-8 w-8 p-0"
                                >
                                    <Edit class="h-4 w-4" />
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    @click="deleteCategory(category.id, category.name)"
                                    class="h-8 w-8 p-0 text-red-600 hover:text-red-700 hover:bg-red-50"
                                    title="Delete Category"
                                >
                                    <Trash2 class="h-4 w-4" />
                                </Button>
                            </div>
                        </td>
                    </tr>
                    </tbody>
                </table>

                <!-- Empty State -->
                <div v-else class="text-center py-16">
                    <div class="w-24 h-24 bg-muted rounded-full flex items-center justify-center mx-auto mb-6">
                        <FolderOpen class="h-12 w-12 text-muted-foreground" />
                    </div>
                    <h3 class="text-xl font-semibold mb-2">No categories found</h3>
                    <p class="text-muted-foreground mb-6 max-w-md mx-auto">
                        {{ selectedCategories.length ? 'No categories match your selection' : 'Create your first product category to organize your products' }}
                    </p>
                    <Button as="a" href="/admin/categories/create" size="lg">
                        <Plus class="h-5 w-5 mr-2" />
                        Add First Category
                    </Button>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
