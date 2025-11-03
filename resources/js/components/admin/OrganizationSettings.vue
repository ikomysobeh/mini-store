<script setup lang="ts">
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

interface Props {
    sortOrder: number;
    title?: string;
    helpText?: string;
}

interface Emits {
    (e: 'update:sortOrder', value: number): void;
}

const props = withDefaults(defineProps<Props>(), {
    title: 'Organization',
    helpText: 'Lower numbers appear first in listings'
});

const emit = defineEmits<Emits>();

const updateSortOrder = (event: Event) => {
    const target = event.target as HTMLInputElement;
    const value = parseInt(target.value) || 0;
    emit('update:sortOrder', value);
};
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle>{{ title }}</CardTitle>
        </CardHeader>
        <CardContent class="space-y-4">

            <div class="space-y-2">
                <Label for="sort_order">Sort Order</Label>
                <Input
                    id="sort_order"
                    :value="sortOrder"
                    @input="updateSortOrder"
                    type="number"
                    min="0"
                    placeholder="0"
                />
                <p v-if="helpText" class="text-xs text-muted-foreground">
                    {{ helpText }}
                </p>
            </div>

        </CardContent>
    </Card>
</template>
