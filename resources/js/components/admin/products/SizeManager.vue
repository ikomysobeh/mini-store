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

// New size names (English and Arabic)
const newSizeNameEn = ref('');
const newSizeNameAr = ref('');

// Common size options with translations
const commonSizes = [
    { 
        category: 'clothing', 
        sizes: [
            { en: 'XS', ar: 'صغير جداً' },
            { en: 'S', ar: 'صغير' },
            { en: 'M', ar: 'متوسط' },
            { en: 'L', ar: 'كبير' },
            { en: 'XL', ar: 'كبير جداً' },
            { en: 'XXL', ar: 'كبير جداً جداً' }
        ]
    },
    { 
        category: 'shoes', 
        sizes: [
            { en: '36', ar: '٣٦' },
            { en: '37', ar: '٣٧' },
            { en: '38', ar: '٣٨' },
            { en: '39', ar: '٣٩' },
            { en: '40', ar: '٤٠' },
            { en: '41', ar: '٤١' },
            { en: '42', ar: '٤٢' },
            { en: '43', ar: '٤٣' },
            { en: '44', ar: '٤٤' },
            { en: '45', ar: '٤٥' }
        ]
    },
    { 
        category: 'general', 
        sizes: [
            { en: 'Small', ar: 'صغير' },
            { en: 'Medium', ar: 'متوسط' },
            { en: 'Large', ar: 'كبير' },
            { en: 'One Size', ar: 'مقاس واحد' }
        ]
    }
];

// Generate unique ID for new sizes
const generateId = () => 'temp_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);

// Add new size
const addSize = (sizeData = null) => {
    let nameEn, nameAr;
    
    if (sizeData) {
        // Adding from common sizes
        nameEn = sizeData.en;
        nameAr = sizeData.ar;
    } else {
        // Adding from form inputs
        nameEn = newSizeNameEn.value.trim();
        nameAr = newSizeNameAr.value.trim();
    }

    // At least one name must be provided
    if (!nameEn && !nameAr) return;

    // Check if size already exists (check both English and Arabic names)
    if (sizes.some(size => 
        (nameEn && size.name_en?.toLowerCase() === nameEn.toLowerCase()) ||
        (nameAr && size.name_ar === nameAr)
    )) {
        return;
    }

    const updatedSizes = [...sizes, {
        id: generateId(),
        name_en: nameEn,
        name_ar: nameAr,
        name: nameEn || nameAr, // Fallback for display
        category_type: 'general',
        is_active: true,
    }];

    emit('update:sizes', updatedSizes);

    // Reset form
    newSizeNameEn.value = '';
    newSizeNameAr.value = '';
};

// Remove size
const removeSize = (sizeId) => {
    const updatedSizes = sizes.filter(size => size.id !== sizeId);
    emit('update:sizes', updatedSizes);
};

// Add multiple common sizes
const addCommonSizes = (sizeList) => {
    sizeList.forEach(sizeData => {
        if (!sizes.some(size => 
            (sizeData.en && size.name_en?.toLowerCase() === sizeData.en.toLowerCase()) ||
            (sizeData.ar && size.name_ar === sizeData.ar)
        )) {
            addSize(sizeData);
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

            <div class="space-y-2">
                <div class="flex gap-2">
                    <div class="flex-1">
                        <Label class="text-xs text-muted-foreground">Name (English)</Label>
                        <Input
                            v-model="newSizeNameEn"
                            placeholder="e.g., Medium"
                            @keydown.enter="handleEnter"
                            class="h-9 mt-1"
                        />
                    </div>
                    <div class="flex-1">
                        <Label class="text-xs text-muted-foreground">Name (Arabic)</Label>
                        <Input
                            v-model="newSizeNameAr"
                            placeholder="مثال: متوسط"
                            @keydown.enter="handleEnter"
                            class="h-9 mt-1"
                            dir="rtl"
                        />
                    </div>
                </div>

                <Button
                    type="button"
                    @click="addSize()"
                    size="sm"
                    :disabled="!newSizeNameEn.trim() && !newSizeNameAr.trim()"
                    class="w-full"
                >
                    <Plus class="h-4 w-4 mr-1" />
                    Add Size
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
                            :key="size.en"
                            type="button"
                            variant="outline"
                            size="sm"
                            @click="addSize(size)"
                            :disabled="sizes.some(s => s.name_en === size.en || s.name_ar === size.ar)"
                            class="h-7 px-2 text-xs"
                        >
                            {{ size.en }} / {{ size.ar }}
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
                        <div class="space-y-1">
                            <div class="px-2 py-1 bg-primary/10 text-primary rounded text-sm font-medium">
                                {{ size.name_en || size.name }}
                            </div>
                            <div v-if="size.name_ar" class="px-2 py-1 bg-secondary/10 text-secondary-foreground rounded text-sm" dir="rtl">
                                {{ size.name_ar }}
                            </div>
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
