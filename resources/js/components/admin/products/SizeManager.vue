<!-- resources/js/components/admin/products/SizeManager.vue -->
<script setup lang="ts">
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Button } from '@/components/ui/button';
import { Plus, Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';

const { sizes } = defineProps({
    sizes: { type: Array, default: () => [] },
});

const emit = defineEmits(['update:sizes']);

// New size name
const newSizeName = ref('');

// Common size options
const commonSizes = [
    { category: 'clothing', sizes: ['XS', 'S', 'M', 'L', 'XL', 'XXL'] },
    { category: 'shoes', sizes: ['36', '37', '38', '39', '40', '41', '42', '43', '44', '45'] },
    { category: 'general', sizes: ['Small', 'Medium', 'Large', 'One Size'] }
];

// Generate unique ID for new sizes
const generateId = () => 'temp_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);

// Add new size
const addSize = (sizeName = null) => {
    const name = (sizeName || newSizeName.value).trim();

    if (!name) return;

    // Check if size already exists
    if (sizes.some(size => size.name.toLowerCase() === name.toLowerCase())) {
        return;
    }

    const updatedSizes = [...sizes, {
        id: generateId(),
        name: name,
        category_type: 'general',
        is_active: true,
    }];

    emit('update:sizes', updatedSizes);

    // Reset form
    newSizeName.value = '';
};

// Remove size
const removeSize = (sizeId) => {
    const updatedSizes = sizes.filter(size => size.id !== sizeId);
    emit('update:sizes', updatedSizes);
};

// Add multiple common sizes
const addCommonSizes = (sizeList) => {
    sizeList.forEach(sizeName => {
        if (!sizes.some(size => size.name.toLowerCase() === sizeName.toLowerCase())) {
            addSize(sizeName);
        }
    });
};

// Handle enter key
const handleEnter = (event) => {
    event.preventDefault();
    addSize();
};
</script>

<template>
    <div class="space-y-4">

        <!-- Add New Size -->
        <div class="space-y-3 p-4 border rounded-lg bg-muted/20">
            <Label class="text-sm font-medium">Add New Size</Label>

            <div class="flex gap-2">
                <Input
                    v-model="newSizeName"
                    placeholder="Size name (e.g., M)"
                    @keydown.enter="handleEnter"
                    class="flex-1 h-9"
                />

                <Button
                    type="button"
                    @click="addSize()"
                    size="sm"
                    :disabled="!newSizeName.trim()"
                >
                    <Plus class="h-4 w-4 mr-1" />
                    Add
                </Button>
            </div>
        </div>

        <!-- Common Size Templates -->
        <div class="space-y-3">
            <Label class="text-sm font-medium">Quick Add Common Sizes</Label>

            <div class="space-y-2">
                <div
                    v-for="category in commonSizes"
                    :key="category.category"
                    class="flex items-center gap-2 text-sm"
                >
                    <span class="w-20 text-muted-foreground capitalize">{{ category.category }}:</span>
                    <div class="flex flex-wrap gap-1">
                        <Button
                            v-for="size in category.sizes"
                            :key="size"
                            type="button"
                            variant="outline"
                            size="sm"
                            @click="addSize(size)"
                            :disabled="sizes.some(s => s.name === size)"
                            class="h-7 px-2 text-xs"
                        >
                            {{ size }}
                        </Button>
                    </div>
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        @click="addCommonSizes(category.sizes)"
                        class="ml-2 h-7 px-2 text-xs"
                    >
                        Add All
                    </Button>
                </div>
            </div>
        </div>

        <!-- Sizes List -->
        <div v-if="sizes.length > 0" class="space-y-2">
            <Label class="text-sm font-medium">Selected Sizes ({{ sizes.length }})</Label>

            <div class="space-y-2">
                <div
                    v-for="size in sizes"
                    :key="size.id"
                    class="flex items-center justify-between p-3 border rounded-lg bg-background hover:bg-muted/50 transition-colors"
                >
                    <div class="flex items-center space-x-3">
                        <div class="px-2 py-1 bg-primary/10 text-primary rounded text-sm font-medium">
                            {{ size.name }}
                        </div>
                    </div>

                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        @click="removeSize(size.id)"
                        class="text-red-600 hover:text-red-700 hover:bg-red-50"
                    >
                        <Trash2 class="h-4 w-4" />
                    </Button>
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div v-else class="text-center py-6 text-muted-foreground">
            <div class="text-sm">No sizes added yet</div>
            <div class="text-xs">Add sizes to create product variants</div>
        </div>

    </div>
</template>
