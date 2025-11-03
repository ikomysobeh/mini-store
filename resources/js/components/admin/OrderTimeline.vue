<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Calendar, Clock, Package, Truck, CheckCircle, XCircle } from 'lucide-vue-next';

interface TimelineEvent {
    status: string;
    title: string;
    description: string;
    created_at: string;
}

interface Props {
    events: TimelineEvent[];
    locale?: string;
}

const { events, locale = 'nl-NL' } = defineProps<Props>();

const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString(locale, {
        day: '2-digit',
        month: 'long',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
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

const getStatusColor = (status: string) => {
    const colors = {
        pending: 'text-yellow-600 bg-yellow-100',
        processing: 'text-blue-600 bg-blue-100',
        shipped: 'text-purple-600 bg-purple-100',
        delivered: 'text-green-600 bg-green-100',
        cancelled: 'text-red-600 bg-red-100'
    };
    return colors[status] || 'text-gray-600 bg-gray-100';
};
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle class="flex items-center space-x-2">
                <Calendar class="h-5 w-5" />
                <span>Order Timeline</span>
            </CardTitle>
        </CardHeader>
        <CardContent>
            <div v-if="events.length === 0" class="text-center py-8">
                <Calendar class="h-12 w-12 text-muted-foreground mx-auto mb-4" />
                <p class="text-muted-foreground">No timeline events available</p>
            </div>

            <div v-else class="space-y-6">
                <div
                    v-for="(event, index) in events"
                    :key="index"
                    class="relative flex items-start space-x-4"
                >
                    <!-- Timeline Icon -->
                    <div class="flex-shrink-0 relative">
                        <div :class="`w-10 h-10 rounded-full flex items-center justify-center ${getStatusColor(event.status)}`">
                            <component :is="getStatusIcon(event.status)" class="h-5 w-5" />
                        </div>
                        <!-- Timeline Line -->
                        <div
                            v-if="index < events.length - 1"
                            class="absolute top-10 left-1/2 transform -translate-x-px w-0.5 h-6 bg-border"
                        ></div>
                    </div>

                    <!-- Timeline Content -->
                    <div class="flex-1 min-w-0 pb-6">
                        <div class="flex items-center justify-between">
                            <h4 class="text-sm font-semibold text-foreground">{{ event.title }}</h4>
                            <time class="text-xs text-muted-foreground font-medium">
                                {{ formatDate(event.created_at) }}
                            </time>
                        </div>
                        <p class="text-sm text-muted-foreground mt-1">{{ event.description }}</p>
                    </div>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
