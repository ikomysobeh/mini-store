<script setup lang="ts">
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { computed, watch } from 'vue';

interface Props {
    name: string;
    slug: string;
    description: string;
    errors: Record<string, string>;
    autoGenerateSlug?: boolean;
    maxDescriptionLength?: number;
}

interface Emits {
    (e: 'update:name', value: string): void;
    (e: 'update:slug', value: string): void;
    (e: 'update:description', value: string): void;
}

const props = withDefaults(defineProps<Props>(), {
    autoGenerateSlug: true,
    maxDescriptionLength: 500
});

const emit = defineEmits<Emits>();

// Helper functions
const generateSlug = (name: string) => {
    if (!name) return '';
    return name.toLowerCase().replace(/[^\w ]+/g, '').replace(/ +/g, '-');
};

// Auto-generate slug when name changes
watch(() => props.name, (newName) => {
    if (props.autoGenerateSlug && newName && !props.slug) {
        emit('update:slug', generateSlug(newName));
    }
});

// Computed
const previewSlug = computed(() => {
    return props.slug || generateSlug(props.name) || 'category-slug';
});

const descriptionLength = computed(() => {
    return props.description.length;
});

// Event handlers
const updateName = (value: string) => {
    emit('update:name', value);
};

const updateSlug = (value: string) => {
    emit('update:slug', value);
};

const updateDescription = (value: string) => {
    if (value.length <= props.maxDescriptionLength) {
        emit('update:description', value);
    }
};
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle>Basic Information</CardTitle>
        </CardHeader>
        <CardContent class="space-y-6">

            <!-- Category Name -->
            <div class="space-y-2">
                <Label for="name">
                    Category Name <span class="text-destructive">*</span>
                </Label>
                <Input
                    id="name"
                    :model-value="name"
                    @update:model-value="updateName"
                    placeholder="Electronics, Clothing, Books..."
                    class="text-lg"
                    :class="{ 'border-destructive': errors.name }"
                />
                <p v-if="errors.name" class="text-sm text-destructive">
                    {{ errors.name }}
                </p>
            </div>

            <!-- Slug -->
            <div class="space-y-2">
                <Label for="slug">Category URL Slug</Label>
                <div class="flex">
                    <span class="inline-flex items-center px-3 rounded-l-md border border-r-0 border-input bg-muted text-sm text-muted-foreground">
                        /categories/
                    </span>
                    <Input
                        id="slug"
                        :model-value="slug"
                        @update:model-value="updateSlug"
                        :placeholder="generateSlug(name) || 'category-slug'"
                        class="rounded-l-none"
                        :class="{ 'border-destructive': errors.slug }"
                    />
                </div>
                <p class="text-xs text-muted-foreground">
                    Preview: /categories/{{ previewSlug }}
                </p>
                <p v-if="errors.slug" class="text-sm text-destructive">
                    {{ errors.slug }}
                </p>
            </div>

            <!-- Description -->
            <div class="space-y-2">
                <Label for="description">Description</Label>
                <textarea
                    id="description"
                    :value="description"
                    @input="updateDescription($event.target.value)"
                    placeholder="Describe this category and what products it contains..."
                    rows="4"
                    :maxlength="maxDescriptionLength"
                    class="flex min-h-[100px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                ></textarea>
                <p class="text-xs text-muted-foreground">
                    {{ descriptionLength }}/{{ maxDescriptionLength }} characters
                </p>
                <p v-if="errors.description" class="text-sm text-destructive">
                    {{ errors.description }}
                </p>
            </div>

        </CardContent>
    </Card>
</template>
