<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { computed } from 'vue';

interface Props {
    name: string;
    slug: string;
    description: string;
    imagePreview?: string | null;
    title?: string;
    baseUrl?: string;
}

const props = withDefaults(defineProps<Props>(), {
    title: 'Preview',
    baseUrl: '/categories'
});

// Helper function
const generateSlug = (name: string) => {
    if (!name) return '';
    return name.toLowerCase().replace(/[^\w ]+/g, '').replace(/ +/g, '-');
};

const previewSlug = computed(() => {
    return props.slug || generateSlug(props.name) || 'category-slug';
});

const previewUrl = computed(() => {
    return `${props.baseUrl}/${previewSlug.value}`;
});
</script>

<template>
    <Card v-if="name">
        <CardHeader>
            <CardTitle class="text-lg">{{ title }}</CardTitle>
        </CardHeader>
        <CardContent class="space-y-4">

            <div v-if="imagePreview" class="aspect-video">
                <img
                    :src="imagePreview"
                    :alt="`${name} preview`"
                    class="w-full h-full object-cover rounded-lg border"
                />
            </div>

            <div class="space-y-3">
                <h3 class="font-semibold text-lg">{{ name }}</h3>
                <p v-if="description" class="text-sm text-muted-foreground leading-relaxed">
                    {{ description }}
                </p>
                <div class="flex items-center space-x-2 text-xs">
                    <span class="text-muted-foreground">URL:</span>
                    <code class="bg-muted px-2 py-1 rounded text-xs font-mono">
                        {{ previewUrl }}
                    </code>
                </div>
            </div>

        </CardContent>
    </Card>
</template>
