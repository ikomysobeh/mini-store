<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { ArrowLeft } from 'lucide-vue-next';

interface Props {
    title: string;
    description?: string;
    icon?: any;
    iconColor?: string;
    showBackButton?: boolean;
    backLabel?: string;
    onBack?: () => void;
    actions?: Array<{
        label: string;
        href?: string;
        onClick?: () => void;
        variant?: string;
        icon?: any;
        disabled?: boolean;
        loading?: boolean;
    }>;
    statusIndicators?: Array<{
        label: string;
        color: 'green' | 'red' | 'blue' | 'orange' | 'gray';
        active: boolean;
    }>;
    showBorder?: boolean;
    padding?: string;
}

const props = withDefaults(defineProps<Props>(), {
    iconColor: 'text-primary',
    actions: () => [],
    statusIndicators: () => [],
    showBackButton: false,
    backLabel: 'Back',
    showBorder: true,
    padding: 'py-6'
});

const getStatusColor = (color: string, active: boolean) => {
    if (!active) return 'bg-muted';

    const colors = {
        green: 'bg-primary',
        red: 'bg-destructive',
        blue: 'bg-accent',
        orange: 'bg-warning',
        gray: 'bg-muted-foreground'
    };

    return colors[color] || 'bg-gray-500';
};
</script>

<template>
    <div :class="['bg-background', showBorder ? 'border-b' : '']">
        <div class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8" :class="padding">

            <!-- Main Header Row -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">

                <!-- Left Section -->
                <div class="flex items-center space-x-4">
                    <!-- Back Button -->
                    <Button
                        v-if="showBackButton && onBack"
                        @click="onBack"
                        variant="ghost"
                        size="sm"
                        class="flex items-center shrink-0"
                    >
                        <ArrowLeft class="h-4 w-4 mr-2" />
                        {{ backLabel }}
                    </Button>

                    <!-- Icon -->
                    <div v-if="icon" class="p-2 bg-primary/20 rounded-lg shrink-0">
                        <component :is="icon" :class="`h-6 w-6 ${iconColor}`" />
                    </div>

                    <!-- Title & Description -->
                    <div class="min-w-0 flex-1">
                        <h1 class="text-2xl font-bold text-foreground">{{ title }}</h1>
                        <p v-if="description" class="text-sm text-muted-foreground mt-1">
                            {{ description }}
                        </p>
                    </div>
                </div>

                <!-- Right Section - Actions -->
                <div v-if="actions.length > 0" class="flex items-center space-x-3 shrink-0">
                    <Button
                        v-for="(action, index) in actions"
                        :key="index"
                        :variant="action.variant || 'default'"
                        size="sm"
                        :as="action.href ? 'a' : 'button'"
                        :href="action.href"
                        :disabled="action.disabled || action.loading"
                        @click="action.onClick"
                        class="flex items-center"
                    >
                        <component
                            v-if="action.icon"
                            :is="action.icon"
                            class="h-4 w-4 mr-2"
                        />
                        {{ action.loading ? 'Loading...' : action.label }}
                    </Button>
                </div>
            </div>

            <!-- Status Indicators Row -->
            <div v-if="statusIndicators.length > 0" class="mt-4 flex flex-wrap items-center gap-4 text-sm text-muted-foreground">
                <div
                    v-for="(indicator, index) in statusIndicators"
                    :key="index"
                    class="flex items-center"
                >
                    <div :class="[
                        'w-2 h-2 rounded-full mr-2',
                        getStatusColor(indicator.color, indicator.active)
                    ]"></div>
                    <span>{{ indicator.label }}</span>
                </div>
            </div>
        </div>
    </div>
</template>
