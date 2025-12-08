<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Search, Filter, X } from 'lucide-vue-next';
import { watch, ref } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

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
                {{ t('orders.filterOrders') }}
            </CardTitle>
        </CardHeader>
        <CardContent class="p-4">

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 xl:grid-cols-5 gap-4">

                <!-- Search Input -->
                <div class="space-y-2">
                    <Label for="search">{{ t('orders.searchOrders') }}</Label>
                    <div class="relative">
                        <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                        <Input
                            id="search"
                            v-model="localSearch"
                            @input="updateSearch(localSearch)"
                            :placeholder="t('orders.searchPlaceholder')"
                            class="pl-10"
                        />
                    </div>
                </div>

                <!-- Status Filter -->
                <div class="space-y-2">
                    <Label for="status">{{ t('checkout.status') }}</Label>
                    <select
                        id="status"
                        v-model="localStatus"
                        @change="updateStatus(localStatus)"
                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        <option value="">{{ t('orders.allStatuses') }}</option>
                        <option value="pending">{{ t('orders.status.pending') }}</option>
                        <option value="processing">{{ t('orders.status.processing') }}</option>
                        <option value="shipped">{{ t('orders.status.shipped') }}</option>
                        <option value="delivered">{{ t('orders.status.delivered') }}</option>
                        <option value="cancelled">{{ t('orders.status.cancelled') }}</option>
                    </select>
                </div>

                <!-- Type Filter -->
                <div class="space-y-2">
                    <Label for="type">{{ t('orders.orderType') }}</Label>
                    <select
                        id="type"
                        v-model="localType"
                        @change="updateType(localType)"
                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        <option value="">{{ t('orders.allOrders') }}</option>
                        <option value="purchase">{{ t('orders.purchases') }}</option>
                        <option value="donation">{{ t('orders.donations') }}</option>
                    </select>
                </div>

                <!-- Date Range Filter -->
                <div class="space-y-2">
                    <Label for="dateRange">{{ t('orders.dateRange') }}</Label>
                    <select
                        id="dateRange"
                        v-model="localDateRange"
                        @change="updateDateRange(localDateRange)"
                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        <option value="">{{ t('orders.allTime') }}</option>
                        <option value="last_month">{{ t('orders.lastMonth') }}</option>
                        <option value="last_3_months">{{ t('orders.last3Months') }}</option>
                        <option value="last_year">{{ t('orders.lastYear') }}</option>
                        <option value="this_year">{{ t('orders.thisYear') }}</option>
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
                            {{ t('common.apply') }}
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
                    <span>{{ t('orders.activeFilters') }}</span>
                    <div class="flex flex-wrap gap-2">
                        <span v-if="localSearch" class="px-2 py-1 bg-info/20 text-info rounded text-xs">
                            {{ t('common.search') }}: "{{ localSearch }}"
                        </span>
                        <span v-if="localStatus" class="px-2 py-1 bg-success/20 text-success rounded text-xs">
                            {{ t('checkout.status') }}: {{ t(`orders.status.${localStatus}`) }}
                        </span>
                        <span v-if="localType" class="px-2 py-1 bg-primary/20 text-primary rounded text-xs">
                            {{ t('orders.type') }}: {{ localType === 'purchase' ? t('orders.purchases') : t('orders.donations') }}
                        </span>
                        <span v-if="localDateRange" class="px-2 py-1 bg-warning/20 text-warning rounded text-xs">
                            {{ t('orders.range') }}: {{ 
                                localDateRange === 'last_month' ? t('orders.lastMonth') :
                                localDateRange === 'last_3_months' ? t('orders.last3Months') :
                                localDateRange === 'last_year' ? t('orders.lastYear') :
                                localDateRange === 'this_year' ? t('orders.thisYear') : localDateRange
                            }}
                        </span>
                    </div>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
