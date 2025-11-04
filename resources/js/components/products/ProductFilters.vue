<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { Search, Filter, X } from 'lucide-vue-next';
import { computed } from 'vue';

interface Category {
    id: number;
    name: string;
    slug: string;
    products_count?: number;
}

interface Props {
    categories: Category[];
    searchTerm: string;
    selectedCategory: string;
    onlyDonatable: boolean;
    sortBy: string;
    showFilters: boolean;
}

interface Emits {
    (e: 'update:searchTerm', value: string): void;
    (e: 'update:selectedCategory', value: string): void;
    (e: 'update:onlyDonatable', value: boolean): void;
    (e: 'update:sortBy', value: string): void;
    (e: 'toggleFilters'): void;
    (e: 'clearFilters'): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

// Computed properties
const hasActiveFilters = computed(() => {
    return props.searchTerm || props.selectedCategory || props.onlyDonatable;
});

const activeFiltersCount = computed(() => {
    return (props.searchTerm ? 1 : 0) +
        (props.selectedCategory ? 1 : 0) +
        (props.onlyDonatable ? 1 : 0);
});

// Event handlers - FIXED
const handleSearchInput = (event: Event) => {
    const target = event.target as HTMLInputElement;
    console.log('Search input changed to:', target.value);
    emit('update:searchTerm', target.value);
};

const handleAllCategoriesClick = () => {
    console.log('All Categories clicked');
    emit('update:selectedCategory', '');
};

const handleCategoryClick = (categorySlug: string) => {
    console.log('Category clicked:', categorySlug);
    // If clicking the same category, unselect it
    if (props.selectedCategory === categorySlug) {
        emit('update:selectedCategory', '');
    } else {
        emit('update:selectedCategory', categorySlug);
    }
};

const handleDonatableChange = (checked: boolean | string) => {
    const isChecked = checked === true || checked === 'true';
    console.log('Donatable changed to:', isChecked);
    emit('update:onlyDonatable', isChecked);
};

const handleSortChange = (value: string) => {
    console.log('Sort changed to:', value);
    emit('update:sortBy', value);
};

const toggleFilters = () => {
    emit('toggleFilters');
};

const clearFilters = () => {
    emit('clearFilters');
};
</script>

<template>
    <div class="sticky top-24 space-y-6">

        <!-- Mobile Filter Toggle -->
        <div class="lg:hidden">
            <Button variant="outline" @click="toggleFilters" class="w-full justify-between">
                <div class="flex items-center space-x-2">
                    <Filter class="h-4 w-4" />
                    <span>Filters</span>
                </div>
                <Badge v-if="hasActiveFilters" variant="secondary">
                    {{ activeFiltersCount }}
                </Badge>
            </Button>
        </div>

        <!-- Filters Panel -->
        <Card :class="{ 'hidden lg:block': !showFilters }">
            <CardContent class="p-6 space-y-6">
                <!-- Search -->
                <div class="space-y-2">
                    <Label for="search">Search Products</Label>
                    <div class="relative">
                        <Search class="absolute left-3 top-3 h-4 w-4 text-muted-foreground" />
                        <Input
                            id="search"
                            :value="searchTerm"
                            @input="handleSearchInput"
                            placeholder="Search products..."
                            class="pl-10"
                        />
                    </div>
                </div>

                <Separator />

                <!-- Categories -->
                <div class="space-y-3">
                    <Label>Categories</Label>
                    <div class="space-y-2 max-h-64 overflow-y-auto">
                        <!-- All Categories Option -->
                        <div class="flex items-center space-x-2">
                            <Checkbox
                                :checked="selectedCategory === ''"
                                @click="handleAllCategoriesClick"
                                id="all-categories"
                            />
                            <Label
                                for="all-categories"
                                class="text-sm font-normal cursor-pointer"
                                @click="handleAllCategoriesClick"
                            >
                                All Categories
                            </Label>
                        </div>

                        <!-- Individual Categories -->
                        <div
                            v-for="category in categories"
                            :key="category.id"
                            class="flex items-center justify-between space-x-2"
                        >
                            <div class="flex items-center space-x-2">
                                <Checkbox
                                    :checked="selectedCategory === category.slug"
                                    @click="() => handleCategoryClick(category.slug)"
                                    :id="`category-${category.id}`"
                                />
                                <Label
                                    :for="`category-${category.id}`"
                                    class="text-sm font-normal cursor-pointer capitalize"
                                    @click="() => handleCategoryClick(category.slug)"
                                >
                                    {{ category.name }}
                                </Label>
                            </div>
                            <Badge v-if="category.products_count" variant="outline" class="text-xs">
                                {{ category.products_count }}
                            </Badge>
                        </div>
                    </div>
                </div>

                <Separator />

                <!-- Product Type -->
                <div class="space-y-3">
                    <Label>Product Type</Label>
                    <div class="space-y-2">
                        <div class="flex items-center space-x-2">
                            <Checkbox
                                :checked="onlyDonatable"
                                @update:checked="handleDonatableChange"
                                id="donations-only"
                            />
                            <Label for="donations-only" class="text-sm font-normal cursor-pointer">
                                Donations Only
                            </Label>
                        </div>
                    </div>
                </div>

                <Separator />

                <!-- Sort By -->
                <div class="space-y-3">
                    <Label for="sort">Sort By</Label>
                    <Select :value="sortBy" @update:value="handleSortChange">
                        <SelectTrigger>
                            <SelectValue placeholder="Sort products" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="name">Name (A-Z)</SelectItem>
                            <SelectItem value="name_desc">Name (Z-A)</SelectItem>
                            <SelectItem value="price_asc">Price (Low to High)</SelectItem>
                            <SelectItem value="price_desc">Price (High to Low)</SelectItem>
                            <SelectItem value="newest">Newest First</SelectItem>
                            <SelectItem value="featured">Featured First</SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <Separator />

                <!-- Clear Filters -->
                <Button
                    v-if="hasActiveFilters"
                    variant="outline"
                    @click="clearFilters"
                    class="w-full"
                >
                    <X class="h-4 w-4 mr-2" />
                    Clear All Filters
                </Button>

                <!-- Filter Summary -->
                <div v-if="hasActiveFilters" class="text-xs text-muted-foreground">
                    {{ activeFiltersCount }} active filter{{ activeFiltersCount > 1 ? 's' : '' }}
                </div>

            </CardContent>
        </Card>

        <!-- Quick Filters -->
        <Card class="lg:block hidden">
            <CardContent class="p-4">
                <Label class="text-sm font-medium mb-3 block">Quick Filters</Label>
                <div class="flex flex-wrap gap-2">
                    <Button
                        variant="outline"
                        size="sm"
                        @click="handleDonatableChange(!onlyDonatable)"
                        :class="{ 'bg-warning/10 text-warning': onlyDonatable }"
                    >
                        Donations
                    </Button>
                    <Button
                        variant="outline"
                        size="sm"
                        @click="handleSortChange('price_asc')"
                        :class="{ 'bg-success/10 text-success': sortBy === 'price_asc' }"
                    >
                        Low Price
                    </Button>
                    <Button
                        variant="outline"
                        size="sm"
                        @click="handleSortChange('newest')"
                        :class="{ 'bg-info/10 text-info': sortBy === 'newest' }"
                    >
                        New Arrivals
                    </Button>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
