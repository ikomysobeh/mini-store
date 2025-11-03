<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Trash2 } from 'lucide-vue-next';

interface Action {
    label: string;
    icon?: any;
    variant?: string;
    onClick: () => void;
    destructive?: boolean;
}

interface Props {
    selectedCount: number;
    itemName?: string;
    actions?: Action[];
    className?: string;
}

interface Emits {
    (e: 'cancel'): void;
}

const {
    selectedCount,
    itemName = 'item',
    actions = [],
    className = 'mb-6'
} = defineProps<Props>();

const emit = defineEmits<Emits>();

const cancel = () => {
    emit('cancel');
};

const getItemLabel = () => {
    return selectedCount > 1 ? `${itemName}s` : itemName;
};
</script>

<template>
    <div v-if="selectedCount > 0" :class="className">
        <Card class="border-orange-200 bg-orange-50">
            <CardContent class="p-4">
                <div class="flex items-center justify-between">
                    <p class="font-medium text-orange-900">
                        {{ selectedCount }} {{ getItemLabel() }} selected
                    </p>
                    <div class="flex items-center space-x-2">
                        <Button
                            v-for="(action, index) in actions"
                            :key="index"
                            :variant="action.destructive ? 'destructive' : action.variant || 'outline'"
                            size="sm"
                            @click="action.onClick"
                        >
                            <component v-if="action.icon" :is="action.icon" class="h-4 w-4 mr-2" />
                            {{ action.label }}
                        </Button>
                        <Button variant="outline" size="sm" @click="cancel">
                            Cancel
                        </Button>
                    </div>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
