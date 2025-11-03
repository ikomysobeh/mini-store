<script setup lang="ts">
interface Props {
    totalProducts: number;
    selectedCategory?: string;
    categories: Array<{
        id: number;
        name: string;
        slug: string;
    }>;
}

const { totalProducts, selectedCategory, categories } = defineProps<Props>();

const getCategoryName = (categorySlug: string) => {
    const category = categories.find(cat => cat.slug === categorySlug);
    return category ? category.name : categorySlug;
};
</script>

<template>
    <div class="mb-8">
        <h1 class="text-3xl font-bold mb-2">All Products</h1>
        <p class="text-muted-foreground">
            {{ totalProducts.toLocaleString() }} products found
            <span v-if="selectedCategory" class="ml-2">
                in <span class="capitalize font-medium">{{ getCategoryName(selectedCategory) }}</span>
            </span>
        </p>
    </div>
</template>
