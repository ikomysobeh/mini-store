<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card, CardContent } from '@/components/ui/card';
import { Search, X } from 'lucide-vue-next';
import { ref, watch } from 'vue';

interface Props {
    modelValue: string;
    placeholder?: string;
    showClear?: boolean;
    className?: string;
}

interface Emits {
    (e: 'update:modelValue', value: string): void;
    (e: 'search', value: string): void;
    (e: 'clear'): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

const localValue = ref(props.modelValue);

// Watch for external changes
watch(() => props.modelValue, (newValue) => {
    localValue.value = newValue;
});

// Watch for local changes
watch(localValue, (newValue) => {
    emit('update:modelValue', newValue);
    emit('search', newValue);
});

const clearSearch = () => {
    localValue.value = '';
    emit('clear');
};
</script>

<template>
    <Card :class="`mb-6 ${className || ''}`">
        <CardContent class="p-4">
            <div class="flex space-x-4">
                <div class="relative flex-1">
                    <Search class="absolute left-3 top-3 h-4 w-4 text-muted-foreground" />
                    <Input
                        v-model="localValue"
                        :placeholder="placeholder || 'Search...'"
                        class="pl-10"
                    />
                    <button
                        v-if="localValue && showClear"
                        @click="clearSearch"
                        class="absolute right-3 top-3 text-muted-foreground hover:text-foreground"
                    >
                        <X class="h-4 w-4" />
                    </button>
                </div>
                <Button v-if="showClear" variant="outline" @click="clearSearch">
                    Clear
                </Button>
            </div>
        </CardContent>
    </Card>
</template>
