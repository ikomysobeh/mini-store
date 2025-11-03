<script setup lang="ts">
import { Card, CardContent } from '@/components/ui/card';
import { CheckCircle, XCircle, AlertTriangle, Info } from 'lucide-vue-next';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

interface Props {
    className?: string;
}

const props = withDefaults(defineProps<Props>(), {
    className: 'mb-6'
});

const page = usePage();

// Get flash messages from Inertia
const successMessage = computed(() => page.props.flash?.success);
const errorMessage = computed(() => page.props.flash?.error);
const warningMessage = computed(() => page.props.flash?.warning);
const infoMessage = computed(() => page.props.flash?.info);

// Determine which message to show (priority: error, warning, success, info)
const activeMessage = computed(() => {
    if (errorMessage.value) return { type: 'error', message: errorMessage.value };
    if (warningMessage.value) return { type: 'warning', message: warningMessage.value };
    if (successMessage.value) return { type: 'success', message: successMessage.value };
    if (infoMessage.value) return { type: 'info', message: infoMessage.value };
    return null;
});

const getAlertConfig = (type: string) => {
    const configs = {
        success: {
            icon: CheckCircle,
            bgClass: 'bg-green-50',
            borderClass: 'border-green-200',
            iconClass: 'text-green-600',
            textClass: 'text-green-700'
        },
        error: {
            icon: XCircle,
            bgClass: 'bg-red-50',
            borderClass: 'border-red-200',
            iconClass: 'text-red-600',
            textClass: 'text-red-700'
        },
        warning: {
            icon: AlertTriangle,
            bgClass: 'bg-orange-50',
            borderClass: 'border-orange-200',
            iconClass: 'text-orange-600',
            textClass: 'text-orange-700'
        },
        info: {
            icon: Info,
            bgClass: 'bg-blue-50',
            borderClass: 'border-blue-200',
            iconClass: 'text-blue-600',
            textClass: 'text-blue-700'
        }
    };
    return configs[type] || configs.info;
};
</script>

<template>
    <div v-if="activeMessage" :class="className">
        <Card :class="`${getAlertConfig(activeMessage.type).borderClass} ${getAlertConfig(activeMessage.type).bgClass}`">
            <CardContent class="p-4">
                <div class="flex items-center space-x-2">
                    <component
                        :is="getAlertConfig(activeMessage.type).icon"
                        :class="`h-4 w-4 ${getAlertConfig(activeMessage.type).iconClass}`"
                    />
                    <p :class="`text-sm font-medium ${getAlertConfig(activeMessage.type).textClass}`">
                        {{ activeMessage.message }}
                    </p>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
