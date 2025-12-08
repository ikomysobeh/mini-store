<script setup lang="ts">
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { computed, watch } from 'vue';

interface Props {
    // Bilingual fields
    nameEn: string;
    nameAr: string;
    descriptionEn: string;
    descriptionAr: string;
    // Legacy fields (for backward compatibility)
    name?: string;
    slug?: string;
    description?: string;
    errors: Record<string, string>;
    autoGenerateSlug?: boolean;
    maxDescriptionLength?: number;
}

interface Emits {
    (e: 'update:nameEn', value: string): void;
    (e: 'update:nameAr', value: string): void;
    (e: 'update:descriptionEn', value: string): void;
    (e: 'update:descriptionAr', value: string): void;
    // Legacy emits
    (e: 'update:name', value: string): void;
    (e: 'update:slug', value: string): void;
    (e: 'update:description', value: string): void;
}

const props = withDefaults(defineProps<Props>(), {
    autoGenerateSlug: true,
    maxDescriptionLength: 500,
    name: '',
    slug: '',
    description: ''
});

const emit = defineEmits<Emits>();

// Helper functions
const generateSlug = (name: string) => {
    if (!name) return '';
    return name.toLowerCase().replace(/[^\w ]+/g, '').replace(/ +/g, '-');
};

// Auto-generate slug when English name changes
watch(() => props.nameEn, (newName) => {
    if (props.autoGenerateSlug && newName) {
        emit('update:slug', generateSlug(newName));
    }
});

// Computed
const previewSlug = computed(() => {
    return props.slug || generateSlug(props.nameEn) || 'category-slug';
});

const descriptionEnLength = computed(() => {
    return props.descriptionEn?.length || 0;
});

const descriptionArLength = computed(() => {
    return props.descriptionAr?.length || 0;
});

// Event handlers - Bilingual
const updateNameEn = (value: string) => {
    emit('update:nameEn', value);
};

const updateNameAr = (value: string) => {
    emit('update:nameAr', value);
};

const updateDescriptionEn = (value: string) => {
    if (value.length <= props.maxDescriptionLength) {
        emit('update:descriptionEn', value);
    }
};

const updateDescriptionAr = (value: string) => {
    if (value.length <= props.maxDescriptionLength) {
        emit('update:descriptionAr', value);
    }
};

// Legacy handlers
const updateSlug = (value: string) => {
    emit('update:slug', value);
};
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle>Basic Information</CardTitle>
        </CardHeader>
        <CardContent class="space-y-6">

            <!-- Category Name - English -->
            <div class="space-y-2">
                <Label for="name_en">
                    Category Name (English) <span class="text-destructive">*</span>
                </Label>
                <Input
                    id="name_en"
                    :model-value="nameEn"
                    @update:model-value="updateNameEn"
                    placeholder="Electronics, Clothing, Books..."
                    class="text-lg"
                    :class="{ 'border-destructive': errors.name_en }"
                />
                <p v-if="errors.name_en" class="text-sm text-destructive">
                    {{ errors.name_en }}
                </p>
            </div>

            <!-- Category Name - Arabic -->
            <div class="space-y-2">
                <Label for="name_ar">
                    Category Name (Arabic) - اسم الفئة <span class="text-destructive">*</span>
                </Label>
                <Input
                    id="name_ar"
                    :model-value="nameAr"
                    @update:model-value="updateNameAr"
                    placeholder="أدخل اسم الفئة بالعربية"
                    dir="rtl"
                    class="text-lg"
                    :class="{ 'border-destructive': errors.name_ar }"
                />
                <p v-if="errors.name_ar" class="text-sm text-destructive">
                    {{ errors.name_ar }}
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
                        :placeholder="generateSlug(nameEn) || 'category-slug'"
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

            <!-- Description - English -->
            <div class="space-y-2">
                <Label for="description_en">Description (English)</Label>
                <textarea
                    id="description_en"
                    :value="descriptionEn"
                    @input="updateDescriptionEn($event.target.value)"
                    placeholder="Describe this category and what products it contains..."
                    rows="4"
                    :maxlength="maxDescriptionLength"
                    class="flex min-h-[100px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                ></textarea>
                <p class="text-xs text-muted-foreground">
                    {{ descriptionEnLength }}/{{ maxDescriptionLength }} characters
                </p>
                <p v-if="errors.description_en" class="text-sm text-destructive">
                    {{ errors.description_en }}
                </p>
            </div>

            <!-- Description - Arabic -->
            <div class="space-y-2">
                <Label for="description_ar">Description (Arabic) - الوصف</Label>
                <textarea
                    id="description_ar"
                    :value="descriptionAr"
                    @input="updateDescriptionAr($event.target.value)"
                    placeholder="وصف الفئة بالعربية..."
                    dir="rtl"
                    rows="4"
                    :maxlength="maxDescriptionLength"
                    class="flex min-h-[100px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                ></textarea>
                <p class="text-xs text-muted-foreground">
                    {{ descriptionArLength }}/{{ maxDescriptionLength }} characters
                </p>
                <p v-if="errors.description_ar" class="text-sm text-destructive">
                    {{ errors.description_ar }}
                </p>
            </div>

        </CardContent>
    </Card>
</template>
