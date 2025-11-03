<script setup lang="ts">
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { computed } from 'vue';

interface Props {
    name: string;
    slug: string;
    shortDescription: string;
    description: string;
    errors: Record<string, string>;
    autoGenerateSlug?: boolean;
}

interface Emits {
    (e: 'update:name', value: string): void;
    (e: 'update:slug', value: string): void;
    (e: 'update:shortDescription', value: string): void;
    (e: 'update:description', value: string): void;
}

const props = withDefaults(defineProps<Props>(), {
    autoGenerateSlug: true
});

const emit = defineEmits<Emits>();

// Helper functions
const generateSlug = (text: string) => {
    return text
        .toLowerCase()
        .replace(/[^\w ]+/g, '')
        .replace(/ +/g, '-');
};

const slugPreview = computed(() => {
    return props.slug || generateSlug(props.name) || 'product-slug';
});

// Event handlers
const updateName = (value: string) => {
    emit('update:name', value);

    // Auto-generate slug if enabled and slug is empty
    if (props.autoGenerateSlug && value && !props.slug) {
        emit('update:slug', generateSlug(value));
    }
};
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle>Basic Information</CardTitle>
        </CardHeader>
        <CardContent class="space-y-6">

            <!-- Product Name -->
            <div class="space-y-2">
                <Label for="name">
                    Product Name <span class="text-destructive">*</span>
                </Label>
                <Input
                    id="name"
                    :model-value="name"
                    @update:model-value="updateName"
                    placeholder="Enter product name"
                    class="text-lg"
                    :class="{ 'border-destructive': errors.name }"
                />
                <p v-if="errors.name" class="text-sm text-destructive">
                    {{ errors.name }}
                </p>
            </div>

            <!-- Slug -->
            <div class="space-y-2">
                <Label for="slug">Product URL Slug</Label>
                <div class="flex">
                    <span class="inline-flex items-center px-3 rounded-l-md border border-r-0 border-input bg-muted text-sm text-muted-foreground">
                        /products/
                    </span>
                    <Input
                        id="slug"
                        :model-value="slug"
                        @update:model-value="emit('update:slug', $event)"
                        :placeholder="generateSlug(name) || 'product-slug'"
                        class="rounded-l-none"
                    />
                </div>
                <p class="text-xs text-muted-foreground">
                    Preview: /products/{{ slugPreview }}
                </p>
            </div>

            <!-- Short Description -->
            <div class="space-y-2">
                <Label for="short_description">Short Description</Label>
                <Input
                    id="short_description"
                    :model-value="shortDescription"
                    @update:model-value="emit('update:shortDescription', $event)"
                    placeholder="Brief product summary (used in listings)"
                    maxlength="160"
                />
                <p class="text-xs text-muted-foreground">
                    {{ shortDescription.length }}/160 characters
                </p>
            </div>

            <!-- Full Description -->
            <div class="space-y-2">
                <Label for="description">Description</Label>
                <textarea
                    id="description"
                    :value="description"
                    @input="emit('update:description', ($event.target as HTMLTextAreaElement).value)"
                    placeholder="Detailed product description..."
                    rows="6"
                    class="flex min-h-[150px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                ></textarea>
            </div>

        </CardContent>
    </Card>
</template>
