<script setup lang="ts">
import { Card, CardContent } from '@/components/ui/card';
import { CheckCircle, XCircle, AlertTriangle, Info, X } from 'lucide-vue-next';
import { usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

interface Props {
    dismissible?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    dismissible: true
});

const page = usePage();
const dismissed = ref(new Set());

// Get flash messages from Inertia
const messages = computed(() => {
    const flash = page.props.flash as any;
    const messageTypes = ['success', 'error', 'warning', 'info'];

    return messageTypes
        .filter(type => flash?.[type] && !dismissed.value.has(type))
        .map(type => ({
            type,
            message: flash[type],
            id: `${type}-${Date.now()}`
        }));
});

const getMessageConfig = (type: string) => {
    const configs = {
        success: {
            icon: CheckCircle,
            bgClass: 'bg-primary/10',
            borderClass: 'border-primary/20',
            iconClass: 'text-primary',
            textClass: 'text-primary-foreground'
        },
        error: {
            icon: XCircle,
            bgClass: 'bg-destructive/10',
            borderClass: 'border-destructive/20',
            iconClass: 'text-destructive',
            textClass: 'text-destructive'
        },
        warning: {
            icon: AlertTriangle,
            bgClass: 'bg-warning/10',
            borderClass: 'border-warning/20',
            iconClass: 'text-warning',
            textClass: 'text-warning'
        },
        info: {
            icon: Info,
            bgClass: 'bg-secondary/10',
            borderClass: 'border-secondary/20',
            iconClass: 'text-secondary',
            textClass: 'text-secondary'
        }
    };
    return configs[type] || configs.info;
};

const dismissMessage = (type: string) => {
    dismissed.value.add(type);
};
</script>

<template>
    <div v-if="messages.length > 0" class="space-y-4 mb-6">
        <div
            v-for="message in messages"
            :key="message.id"
            :class="`${getMessageConfig(message.type).borderClass} ${getMessageConfig(message.type).bgClass} border rounded-lg`"
        >
            <div class="p-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <component
                            :is="getMessageConfig(message.type).icon"
                            :class="`h-5 w-5 ${getMessageConfig(message.type).iconClass}`"
                        />
                        <p :class="`text-sm font-medium ${getMessageConfig(message.type).textClass}`">
                            {{ message.message }}
                        </p>
                    </div>
                    <button
                        v-if="dismissible"
                        @click="dismissMessage(message.type)"
                        :class="`${getMessageConfig(message.type).iconClass} hover:opacity-70`"
                    >
                        <X class="h-4 w-4" />
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
