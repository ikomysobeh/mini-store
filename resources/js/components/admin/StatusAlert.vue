<script setup lang="ts">
import { Card, CardContent } from '@/components/ui/card';
import { Info, AlertCircle, CheckCircle, AlertTriangle } from 'lucide-vue-next';

interface Props {
    type?: 'info' | 'warning' | 'success' | 'error';
    title?: string;
    message: string;
    className?: string;
    show?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    type: 'info',
    className: 'mb-6',
    show: true
});

const getConfig = (type: string) => {
    const configs = {
        info: {
            icon: Info,
            bgColor: 'bg-blue-50',
            borderColor: 'border-blue-200',
            textColor: 'text-blue-700',
            iconColor: 'text-blue-600'
        },
        warning: {
            icon: AlertTriangle,
            bgColor: 'bg-orange-50',
            borderColor: 'border-orange-200',
            textColor: 'text-orange-700',
            iconColor: 'text-orange-600'
        },
        success: {
            icon: CheckCircle,
            bgColor: 'bg-green-50',
            borderColor: 'border-green-200',
            textColor: 'text-green-700',
            iconColor: 'text-green-600'
        },
        error: {
            icon: AlertCircle,
            bgColor: 'bg-red-50',
            borderColor: 'border-red-200',
            textColor: 'text-red-700',
            iconColor: 'text-red-600'
        }
    };
    return configs[type] || configs.info;
};

const config = getConfig(props.type);
</script>

<template>
    <Card
        v-if="show"
        :class="[className, config.borderColor, config.bgColor]"
    >
        <CardContent class="p-4">
            <div class="flex items-center space-x-2">
                <component :is="config.icon" :class="[config.iconColor, 'h-4 w-4']" />
                <div>
                    <p v-if="title" :class="[config.textColor, 'text-sm font-medium']">
                        {{ title }}
                    </p>
                    <p :class="[config.textColor, title ? 'text-xs' : 'text-sm font-medium']">
                        {{ message }}
                    </p>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
