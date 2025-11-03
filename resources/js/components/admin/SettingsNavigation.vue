<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

interface Tab {
    id: string;
    label: string;
    icon: any;
}

interface Props {
    tabs: Tab[];
    activeTab: string;
}

interface Emits {
    (e: 'update:activeTab', value: string): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();
</script>

<template>
    <div class="sticky top-24">
        <Card>
            <CardHeader>
                <CardTitle class="text-lg">Settings Sections</CardTitle>
            </CardHeader>
            <CardContent class="p-0">
                <nav class="space-y-1">
                    <button
                        v-for="tab in tabs"
                        :key="tab.id"
                        @click="emit('update:activeTab', tab.id)"
                        :class="[
                            'w-full flex items-center space-x-3 px-4 py-3 text-left text-sm font-medium transition-colors',
                            activeTab === tab.id
                                ? 'bg-primary text-primary-foreground'
                                : 'text-muted-foreground hover:text-foreground hover:bg-muted'
                        ]"
                    >
                        <component :is="tab.icon" class="h-4 w-4" />
                        <span>{{ tab.label }}</span>
                    </button>
                </nav>
            </CardContent>
        </Card>
    </div>
</template>
