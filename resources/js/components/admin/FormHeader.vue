<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { ArrowLeft } from 'lucide-vue-next';

interface Action {
    label: string;
    onClick?: () => void;
    variant?: string;
    disabled?: boolean;
    icon?: any;
}

interface Props {
    title: string;
    description?: string;
    icon?: any;
    iconColor?: string;
    backUrl?: string;
    actions?: Action[];
}

const {
    title,
    description,
    icon,
    iconColor = 'text-primary',
    backUrl,
    actions = []
} = defineProps<Props>();
</script>

<template>
    <div class="border-b bg-background">
        <div class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center space-x-4">
                    <Button v-if="backUrl" variant="ghost" as="a" :href="backUrl" class="p-2">
                        <ArrowLeft class="h-5 w-5" />
                    </Button>
                    <div class="flex items-center space-x-3">
                        <div v-if="icon" class="p-2 bg-green-100 rounded-lg">
                            <component :is="icon" :class="`h-6 w-6 ${iconColor}`" />
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-foreground">{{ title }}</h1>
                            <p v-if="description" class="text-sm text-muted-foreground">
                                {{ description }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="flex items-center space-x-3">
                    <Button
                        v-for="(action, index) in actions"
                        :key="index"
                        :variant="action.variant || 'default'"
                        :disabled="action.disabled"
                        @click="action.onClick"
                    >
                        <component v-if="action.icon" :is="action.icon" class="h-4 w-4 mr-2" />
                        {{ action.label }}
                    </Button>
                </div>
            </div>
        </div>
    </div>
</template>
