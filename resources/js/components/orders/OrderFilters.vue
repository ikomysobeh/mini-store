<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Search, Filter, X } from 'lucide-vue-next';
import { watch, ref } from 'vue';

const props = defineProps({
    search: { type: String, default: '' },
    status: { type: String, default: '' },
    type: { type: String, default: '' },
    dateRange: { type: String, default: '' },
});

const emit = defineEmits(['update:search', 'update:status', 'update:type', 'update:date-range', 'apply', 'clear']);

// Create local reactive references
const localSearch = ref(props.search);
const localStatus = ref(props.status);
const localType = ref(props.type);
const localDateRange = ref(props.dateRange);

// Watch props and update local values
watch(() => props.search, (val) => localSearch.value = val);
watch(() => props.status, (val) => localStatus.value = val);
watch(() => props.type, (val) => localType.value = val);
watch(() => props.dateRange, (val) => localDateRange.value = val);

// Update handlers - emit immediately when values change
const updateSearch = (value) => {
    console.log('🔍 Search updated:', value);
    localSearch.value = value;
    emit('update:search', value);
};

const updateStatus = (value) => {
    console.log('📊 Status updated:', value);
    localStatus.value = value;
    emit('update:status', value);
};

const updateType = (value) => {
    console.log('🏷️ Type updated:', value);
    localType.value = value;
    emit('update:type', value);
};

const updateDateRange = (value) => {
    console.log('📅 Date range updated:', value);
    localDateRange.value = value;
    emit('update:date-range', value);
};

const applyFilters = () => {
    console.log('🚀 Apply filters clicked with values:', {
        search: localSearch.value,
        status: localStatus.value,
        type: localType.value,
        dateRange: localDateRange.value
    });

    // Make sure all values are updated before applying
    emit('update:search', localSearch.value);
    emit('update:status', localStatus.value);
    emit('update:type', localType.value);
    emit('update:date-range', localDateRange.value);

    // Small delay to ensure updates are processed
    setTimeout(() => {
        emit('apply');
    }, 10);
};

const clearFilters = () => {
    console.log('🧹 Clear filters clicked');
    localSearch.value = '';
    localStatus.value = '';
    localType.value = '';
    localDateRange.value = '';

    emit('update:search', '');
    emit('update:status', '');
    emit('update:type', '');
    emit('update:date-range', '');

    setTimeout(() => {
        emit('clear');
    }, 10);
};

const hasActiveFilters = () => {
    const active = localSearch.value || localStatus.value || localType.value || localDateRange.value;
    console.log('🎯 Has active filters:', active, {
        search: localSearch.value,
        status: localStatus.value,
        type: localType.value,
        dateRange: localDateRange.value
    });
    return active;
};
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle class="flex items-center">
                <Filter class="h-5 w-5 mr-2" />
                Filter Orders
            </CardTitle>
        </CardHeader>
        <CardContent class="p-4">

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 xl:grid-cols-5 gap-4">

                <!-- Search Input -->
                <div class="space-y-2">
                    <Label for="search">Search Orders</Label>
                    <div class="relative">
                        <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                        <Input
                            id="search"
                            v-model="localSearch"
                            @input="updateSearch(localSearch)"
                            placeholder="Order ID, product name..."
                            class="pl-10"
                        />
                    </div>
                </div>

                <!-- Status Filter -->
                <div class="space-y-2">
                    <Label for="status">Status</Label>
                    <select
                        id="status"
                        v-model="localStatus"
                        @change="updateStatus(localStatus)"
                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        <option value="">All Statuses</option>
                        <option value="pending">Pending</option>
                        <option value="processing">Processing</option>
                        <option value="shipped">Shipped</option>
                        <option value="delivered">Delivered</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>

                <!-- Type Filter -->
                <div class="space-y-2">
                    <Label for="type">Order Type</Label>
                    <select
                        id="type"
                        v-model="localType"
                        @change="updateType(localType)"
                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        <option value="">All Orders</option>
                        <option value="purchase">Purchases</option>
                        <option value="donation">Donations</option>
                    </select>
                </div>

                <!-- Date Range Filter -->
                <div class="space-y-2">
                    <Label for="dateRange">Date Range</Label>
                    <select
                        id="dateRange"
                        v-model="localDateRange"
                        @change="updateDateRange(localDateRange)"
                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        <option value="">All Time</option>
                        <option value="last_month">Last Month</option>
                        <option value="last_3_months">Last 3 Months</option>
                        <option value="last_year">Last Year</option>
                        <option value="this_year">This Year</option>
                    </select>
                </div>

                <!-- Action Buttons -->
                <div class="space-y-2">
                    <Label class="invisible">Actions</Label>
                    <div class="flex space-x-2">
                        <Button
                            @click="applyFilters"
                            class="flex-1"
                            size="sm"
                        >
                            Apply
                        </Button>
                        <Button
                            @click="clearFilters"
                            variant="outline"
                            size="sm"
                            :disabled="!hasActiveFilters()"
                        >
                            <X class="h-4 w-4" />
                        </Button>
                    </div>
                </div>

            </div>

            <!-- Active Filters Display -->
            <div v-if="hasActiveFilters()" class="mt-4 pt-4 border-t border-border">
                <div class="flex items-center space-x-2 text-sm text-muted-foreground">
                    <span>Active filters:</span>
                    <div class="flex flex-wrap gap-2">
                        <span v-if="localSearch" class="px-2 py-1 bg-info/20 text-info rounded text-xs">
                            Search: "{{ localSearch }}"
                        </span>
                        <span v-if="localStatus" class="px-2 py-1 bg-success/20 text-success rounded text-xs">
                            Status: {{ localStatus.charAt(0).toUpperCase() + localStatus.slice(1) }}
                        </span>
                        <span v-if="localType" class="px-2 py-1 bg-primary/20 text-primary rounded text-xs">
                            Type: {{ localType.charAt(0).toUpperCase() + localType.slice(1) }}
                        </span>
                        <span v-if="localDateRange" class="px-2 py-1 bg-warning/20 text-warning rounded text-xs">
                            Range: {{ localDateRange.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase()) }}
                        </span>
                    </div>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
