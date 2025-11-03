<script setup lang="ts">
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Info } from 'lucide-vue-next';

interface Props {
    isActive: boolean;
    showDraftWarning?: boolean;
    title?: string;
    activeLabel?: string;
}

interface Emits {
    (e: 'update:isActive', value: boolean): void;
}

const props = withDefaults(defineProps<Props>(), {
    showDraftWarning: true,
    title: 'Publishing',
    activeLabel: 'Active (Visible to customers)'
});

const emit = defineEmits<Emits>();

const updateActive = (value: boolean) => {
    emit('update:isActive', value);
};
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle>{{ title }}</CardTitle>
        </CardHeader>
        <CardContent class="space-y-4">

            <div class="flex items-center space-x-2">
                <Checkbox
                    :checked="isActive"
                    @update:checked="updateActive"
                />
                <Label class="cursor-pointer">{{ activeLabel }}</Label>
            </div>

            <div v-if="!isActive && showDraftWarning" class="p-3 bg-orange-50 border border-orange-200 rounded-lg">
                <div class="flex items-start space-x-2">
                    <Info class="h-4 w-4 text-orange-600 mt-0.5 flex-shrink-0" />
                    <div class="text-sm text-orange-700">
                        <p class="font-medium">Draft Mode</p>
                        <p class="text-xs">This item will not be visible to customers</p>
                    </div>
                </div>
            </div>

        </CardContent>
    </Card>
</template>
