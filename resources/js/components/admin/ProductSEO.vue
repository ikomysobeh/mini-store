<script setup lang="ts">
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { computed } from 'vue';

interface Props {
    metaTitle: string;
    metaDescription: string;
    productName?: string;
    shortDescription?: string;
}

interface Emits {
    (e: 'update:metaTitle', value: string): void;
    (e: 'update:metaDescription', value: string): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

const metaTitleLength = computed(() => {
    return (props.metaTitle || props.productName || '').length;
});

const metaDescriptionLength = computed(() => {
    return (props.metaDescription || props.shortDescription || '').length;
});

const titleColor = computed(() => {
    const length = metaTitleLength.value;
    if (length > 60) return 'text-red-600';
    if (length > 50) return 'text-orange-600';
    return 'text-muted-foreground';
});

const descriptionColor = computed(() => {
    const length = metaDescriptionLength.value;
    if (length > 160) return 'text-red-600';
    if (length > 140) return 'text-orange-600';
    return 'text-muted-foreground';
});
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle class="flex items-center space-x-2">
                <span>SEO Settings</span>
                <Badge variant="outline" class="text-xs">Optional</Badge>
            </CardTitle>
        </CardHeader>
        <CardContent class="space-y-6">

            <div class="space-y-2">
                <Label for="meta_title">Meta Title</Label>
                <Input
                    id="meta_title"
                    :model-value="metaTitle"
                    @update:model-value="emit('update:metaTitle', $event)"
                    :placeholder="productName || 'Product meta title'"
                    maxlength="70"
                />
                <div class="flex items-center justify-between">
                    <p :class="`text-xs ${titleColor}`">
                        {{ metaTitleLength }}/60 characters (optimal)
                    </p>
                    <div v-if="metaTitleLength > 60" class="text-xs text-red-600">
                        May be truncated in search results
                    </div>
                </div>
            </div>

            <div class="space-y-2">
                <Label for="meta_description">Meta Description</Label>
                <textarea
                    id="meta_description"
                    :value="metaDescription"
                    @input="emit('update:metaDescription', ($event.target as HTMLTextAreaElement).value)"
                    :placeholder="shortDescription || 'Brief description for search engines'"
                    rows="3"
                    maxlength="180"
                    class="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                ></textarea>
                <div class="flex items-center justify-between">
                    <p :class="`text-xs ${descriptionColor}`">
                        {{ metaDescriptionLength }}/160 characters (optimal)
                    </p>
                    <div v-if="metaDescriptionLength > 160" class="text-xs text-red-600">
                        May be truncated in search results
                    </div>
                </div>
            </div>

            <!-- SEO Preview -->
            <div class="mt-6 p-4 bg-muted/30 rounded-lg">
                <h4 class="text-sm font-medium mb-2">Search Engine Preview</h4>
                <div class="space-y-1">
                    <div class="text-blue-600 text-lg font-medium truncate">
                        {{ metaTitle || productName || 'Product Title' }}
                    </div>
                    <div class="text-green-600 text-sm truncate">
                        yoursite.com/products/product-slug
                    </div>
                    <div class="text-sm text-muted-foreground line-clamp-2">
                        {{ metaDescription || shortDescription || 'Product description will appear here...' }}
                    </div>
                </div>
            </div>

        </CardContent>
    </Card>
</template>
