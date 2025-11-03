<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Search } from 'lucide-vue-next';

interface Props {
    searchTerm: string;
    selectedStatus: string;
    selectedType: string;
    dateRange: string;
    showFilters: boolean;
}

interface Emits {
    (e: 'update:searchTerm', value: string): void;
    (e: 'update:selectedStatus', value: string): void;
    (e: 'update:selectedType', value: string): void;
    (e: 'update:dateRange', value: string): void;
    (e: 'clearFilters'): void;
    (e: 'applyFilters'): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

const statusOptions = [
    { value: '', label: 'All Status' },
    { value: 'pending', label: 'Pending' },
    { value: 'processing', label: 'Processing' },
    { value: 'shipped', label: 'Shipped' },
    { value: 'delivered', label: 'Delivered' },
    { value: 'cancelled', label: 'Cancelled' }
];

const typeOptions = [
    { value: '', label: 'All Types' },
    { value: 'purchase', label: 'Purchases' },
    { value: 'donation', label: 'Donations' }
];

const dateRangeOptions = [
    { value: '', label: 'All Time' },
    { value: 'today', label: 'Today' },
    { value: 'week', label: 'This Week' },
    { value: 'month', label: 'This Month' },
    { value: 'year', label: 'This Year' }
];

const updateSearchTerm = (value: string) => {
    emit('update:searchTerm', value);
};

const updateSelectedStatus = (value: string) => {
    emit('update:selectedStatus', value);
};

const updateSelectedType = (value: string) => {
    emit('update:selectedType', value);
};

const updateDateRange = (value: string) => {
    emit('update:dateRange', value);
};
</script>

<template>
    <Card v-if="showFilters" class="mb-6">
        <CardHeader>
            <CardTitle class="text-lg">Filters</CardTitle>
        </CardHeader>
        <CardContent class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4">

                <!-- Search -->
                <div class="space-y-2">
                    <label class="text-sm font-medium">Search Orders</label>
                    <div class="relative">
                        <Search class="absolute left-3 top-3 h-4 w-4 text-muted-foreground" />
                        <Input
                            :value="searchTerm"
                            @input="updateSearchTerm($event.target.value)"
                            placeholder="Order ID, customer..."
                            class="pl-10"
                        />
                    </div>
                </div>

                <!-- Status Filter -->
                <div class="space-y-2">
                    <label class="text-sm font-medium">Status</label>
                    <select
                        :value="selectedStatus"
                        @change="updateSelectedStatus($event.target.value)"
                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                    >
                        <option v-for="option in statusOptions" :key="option.value" :value="option.value">
                            {{ option.label }}
                        </option>
                    </select>
                </div>

                <!-- Type Filter -->
                <div class="space-y-2">
                    <label class="text-sm font-medium">Type</label>
                    <select
                        :value="selectedType"
                        @change="updateSelectedType($event.target.value)"
                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                    >
                        <option v-for="option in typeOptions" :key="option.value" :value="option.value">
                            {{ option.label }}
                        </option>
                    </select>
                </div>

                <!-- Date Range -->
                <div class="space-y-2">
                    <label class="text-sm font-medium">Date Range</label>
                    <select
                        :value="dateRange"
                        @change="updateDateRange($event.target.value)"
                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                    >
                        <option v-for="option in dateRangeOptions" :key="option.value" :value="option.value">
                            {{ option.label }}
                        </option>
                    </select>
                </div>

                <!-- Actions -->
                <div class="space-y-2">
                    <label class="text-sm font-medium">&nbsp;</label>
                    <Button variant="outline" @click="emit('clearFilters')" class="w-full">
                        Clear All
                    </Button>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
