<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Search } from 'lucide-vue-next';

interface Category {
    id: number;
    name: string;
    slug: string;
}

interface Props {
    searchTerm: string;
    selectedCategory: string;
    selectedStatus: string;
    categories: Category[];
    showFilters: boolean;
}

interface Emits {
    (e: 'update:searchTerm', value: string): void;
    (e: 'update:selectedCategory', value: string): void;
    (e: 'update:selectedStatus', value: string): void;
    (e: 'clearFilters'): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

const statusOptions = [
    { value: '', label: 'All Status' },
    { value: 'active', label: 'Active' },
    { value: 'inactive', label: 'Inactive' },
    { value: 'out_of_stock', label: 'Out of Stock' }
];
</script>

<template>
    <Card v-if="showFilters" class="mb-6">
        <CardHeader>
            <CardTitle class="text-lg">Filters</CardTitle>
        </CardHeader>
        <CardContent class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">

                <!-- Search -->
                <div class="space-y-2">
                    <label class="text-sm font-medium">Search Products</label>
                    <div class="relative">
                        <Search class="absolute left-3 top-3 h-4 w-4 text-muted-foreground" />
                        <Input
                            :value="searchTerm"
                            @input="emit('update:searchTerm', ($event.target as HTMLInputElement).value)"
                            placeholder="Search by name..."
                            class="pl-10"
                        />
                    </div>
                </div>

                <!-- Category Filter -->
                <div class="space-y-2">
                    <label class="text-sm font-medium">Category</label>
                    <select
                        :value="selectedCategory"
                        @change="emit('update:selectedCategory', ($event.target as HTMLSelectElement).value)"
                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                    >
                        <option value="">All Categories</option>
                        <option v-for="category in categories" :key="category.id" :value="category.slug">
                            {{ category.name }}
                        </option>
                    </select>
                </div>

                <!-- Status Filter -->
                <div class="space-y-2">
                    <label class="text-sm font-medium">Status</label>
                    <select
                        :value="selectedStatus"
                        @change="emit('update:selectedStatus', ($event.target as HTMLSelectElement).value)"
                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                    >
                        <option v-for="option in statusOptions" :key="option.value" :value="option.value">
                            {{ option.label }}
                        </option>
                    </select>
                </div>

                <!-- Actions -->
                <div class="space-y-2">
                    <label class="text-sm font-medium">&nbsp;</label>
                    <div class="flex space-x-2">
                        <Button variant="outline" @click="emit('clearFilters')" class="flex-1">
                            Clear
                        </Button>
                    </div>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
