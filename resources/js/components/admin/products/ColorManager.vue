<!-- resources/js/components/admin/products/ColorManager.vue -->
<script setup lang="ts">
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Button } from '@/components/ui/button';
import { Plus, Trash2 } from 'lucide-vue-next';
import { ref, computed } from 'vue';

const { colors } = defineProps({
    colors: { type: Array, default: () => [] },
});

const emit = defineEmits(['update:colors']);

// New color form
const newColor = ref({
    name: '',
    hex: '#000000'
});

// Generate unique ID for new colors
const generateId = () => 'temp_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);

// Add new color
const addColor = () => {
    if (!newColor.value.name.trim()) {
        return;
    }

    const updatedColors = [...colors, {
        id: generateId(),
        name: newColor.value.name.trim(),
        hex: newColor.value.hex,
        is_active: true,
    }];

    emit('update:colors', updatedColors);

    // Reset form
    newColor.value = {
        name: '',
        hex: '#000000'
    };
};

// Remove color
const removeColor = (colorId) => {
    const updatedColors = colors.filter(color => color.id !== colorId);
    emit('update:colors', updatedColors);
};

// Handle enter key in input
const handleEnter = (event) => {
    event.preventDefault();
    addColor();
};
</script>

<template>
    <div class="space-y-4">

        <!-- Add New Color -->
        <div class="space-y-3 p-4 border rounded-lg bg-muted/20">
            <Label class="text-sm font-medium">Add New Color</Label>

            <div class="flex gap-3">
                <div class="flex-1">
                    <Input
                        v-model="newColor.name"
                        placeholder="Color name (e.g., Red)"
                        @keydown.enter="handleEnter"
                        class="h-9"
                    />
                </div>

                <div class="relative">
                    <input
                        v-model="newColor.hex"
                        type="color"
                        class="w-16 h-9 rounded border border-input cursor-pointer"
                        :style="{ backgroundColor: newColor.hex }"
                    />
                </div>

                <Button
                    type="button"
                    @click="addColor"
                    size="sm"
                    :disabled="!newColor.name.trim()"
                >
                    <Plus class="h-4 w-4 mr-1" />
                    Add
                </Button>
            </div>
        </div>

        <!-- Colors List -->
        <div v-if="colors.length > 0" class="space-y-2">
            <Label class="text-sm font-medium">Selected Colors ({{ colors.length }})</Label>

            <div class="space-y-2">
                <div
                    v-for="color in colors"
                    :key="color.id"
                    class="flex items-center justify-between p-3 border rounded-lg bg-background hover:bg-muted/50 transition-colors"
                >
                    <div class="flex items-center space-x-3">
                        <div
                            class="w-6 h-6 rounded-full border-2 border-gray-200"
                            :style="{ backgroundColor: color.hex }"
                            :title="color.hex"
                        ></div>
                        <span class="font-medium">{{ color.name }}</span>
                        <span class="text-sm text-muted-foreground">{{ color.hex }}</span>
                    </div>

                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        @click="removeColor(color.id)"
                        class="text-red-600 hover:text-red-700 hover:bg-red-50"
                    >
                        <Trash2 class="h-4 w-4" />
                    </Button>
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div v-else class="text-center py-6 text-muted-foreground">
            <div class="text-sm">No colors added yet</div>
            <div class="text-xs">Add colors to create product variants</div>
        </div>

    </div>
</template>
