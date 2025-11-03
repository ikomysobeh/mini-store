<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { RefreshCw, Clock, Package, Truck, CheckCircle, XCircle } from 'lucide-vue-next';
import { computed } from 'vue';

interface Props {
    order: any;
    currentStatus: string;
    isUpdating: boolean;
    statusOptions: Array<{
        value: string;
        label: string;
        color: string;
    }>;
}

interface Emits {
    (e: 'update:currentStatus', value: string): void;
    (e: 'updateStatus'): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('nl-NL', {
        day: '2-digit',
        month: 'long',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const getStatusColor = (status: string) => {
    const colors = {
        pending: 'bg-yellow-100 text-yellow-800 border-yellow-200',
        processing: 'bg-blue-100 text-blue-800 border-blue-200',
        shipped: 'bg-purple-100 text-purple-800 border-purple-200',
        delivered: 'bg-green-100 text-green-800 border-green-200',
        cancelled: 'bg-red-100 text-red-800 border-red-200'
    };
    return colors[status] || 'bg-gray-100 text-gray-800 border-gray-200';
};

const getStatusIcon = (status: string) => {
    const icons = {
        pending: Clock,
        processing: Package,
        shipped: Truck,
        delivered: CheckCircle,
        cancelled: XCircle
    };
    return icons[status] || Clock;
};

const borderColorClass = computed(() => {
    const colorMap = {
        yellow: 'border-l-yellow-500',
        blue: 'border-l-blue-500',
        purple: 'border-l-purple-500',
        green: 'border-l-green-500',
        red: 'border-l-red-500'
    };

    const statusColor = getStatusColor(props.order.status);
    for (const [color, className] of Object.entries(colorMap)) {
        if (statusColor.includes(color)) return className;
    }
    return 'border-l-gray-500';
});

const canUpdate = computed(() => {
    return !props.isUpdating && props.currentStatus !== props.order.status;
});
</script>

<template>
    <Card :class="`mb-8 border-l-4 ${borderColorClass}`">
        <CardContent class="p-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <Badge
                        :class="getStatusColor(order.status)"
                        class="text-sm px-3 py-2 border flex items-center"
                    >
                        <component
                            :is="getStatusIcon(order.status)"
                            class="h-4 w-4 mr-2"
                        />
                        {{ order.status }}
                    </Badge>
                    <div>
                        <p class="font-semibold">Order Status</p>
                        <p class="text-sm text-muted-foreground">
                            Last updated: {{ formatDate(order.updated_at) }}
                        </p>
                    </div>
                </div>

                <!-- Status Update -->
                <div class="flex items-center space-x-3">
                    <select
                        :value="currentStatus"
                        @change="emit('update:currentStatus', ($event.target as HTMLSelectElement).value)"
                        class="border rounded px-3 py-2"
                        :disabled="isUpdating"
                    >
                        <option v-for="status in statusOptions" :key="status.value" :value="status.value">
                            {{ status.label }}
                        </option>
                    </select>
                    <Button
                        @click="emit('updateStatus')"
                        :disabled="!canUpdate"
                        size="sm"
                    >
                        <RefreshCw :class="{ 'animate-spin': isUpdating }" class="h-4 w-4 mr-2" />
                        Update
                    </Button>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
