<script setup lang="ts">
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { computed } from 'vue';

interface Props {
    modelValue: string;
    label: string;
    placeholder?: string;
    required?: boolean;
    maxLength: number;
    error?: string;
    helpText?: string;
    type?: 'input' | 'textarea';
    rows?: number;
    class?: string;
}

interface Emits {
    (e: 'update:modelValue', value: string): void;
}

const props = withDefaults(defineProps<Props>(), {
    type: 'input',
    rows: 3,
    required: false
});

const emit = defineEmits<Emits>();

const characterCount = computed(() => {
    return props.modelValue?.length || 0;
});

const isNearLimit = computed(() => {
    return characterCount.value > props.maxLength * 0.8;
});

const isOverLimit = computed(() => {
    return characterCount.value > props.maxLength;
});

const counterColor = computed(() => {
    if (isOverLimit.value) return 'text-destructive';
    if (isNearLimit.value) return 'text-orange-600';
    return 'text-muted-foreground';
});

const inputClass = computed(() => {
    const baseClass = props.class || '';
    const errorClass = (props.error || isOverLimit.value) ? 'border-destructive' : '';

    if (props.type === 'textarea') {
        return `flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 ${baseClass} ${errorClass}`;
    }

    return `${baseClass} ${errorClass}`;
});
</script>

<template>
    <div class="space-y-2">
        <Label :for="label.toLowerCase().replace(/\s+/g, '_')">
            {{ label }}
            <span v-if="required" class="text-destructive">*</span>
        </Label>

        <!-- Input -->
        <Input
            v-if="type === 'input'"
            :id="label.toLowerCase().replace(/\s+/g, '_')"
            :model-value="modelValue"
            @update:model-value="emit('update:modelValue', $event)"
            :placeholder="placeholder"
            :maxlength="maxLength"
            :class="inputClass"
        />

        <!-- Textarea -->
        <textarea
            v-else
            :id="label.toLowerCase().replace(/\s+/g, '_')"
            :value="modelValue"
            @input="emit('update:modelValue', ($event.target as HTMLTextAreaElement).value)"
            :placeholder="placeholder"
            :rows="rows"
            :maxlength="maxLength"
            :class="inputClass"
        ></textarea>

        <!-- Error and Character Count -->
        <div class="flex justify-between text-xs">
            <div>
                <p v-if="error" class="text-destructive">
                    {{ error }}
                </p>
            </div>
            <span :class="counterColor">
                {{ characterCount }}/{{ maxLength }}
            </span>
        </div>

        <!-- Help Text -->
        <p v-if="helpText" class="text-xs text-muted-foreground">
            {{ helpText }}
        </p>
    </div>
</template>
