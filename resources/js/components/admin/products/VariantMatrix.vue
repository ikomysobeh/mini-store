<!-- resources/js/components/admin/products/VariantMatrix.vue -->
<script setup lang="ts">
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Badge } from '@/components/ui/badge';
import { RefreshCw, Trash2, Eye, EyeOff } from 'lucide-vue-next';
import { ref, computed, watch } from 'vue';

const { variants, basePrice } = defineProps({
    variants: { type: Array, default: () => [] },
    basePrice: { type: [Number, String], default: 0 },
});

const emit = defineEmits(['update:variants']);

// Local state for variants
const localVariants = ref([...variants]);

// Watch for prop changes
watch(() => variants, (newVariants) => {
    localVariants.value = [...newVariants];
}, { deep: true });

// Emit changes
const emitUpdate = () => {
    emit('update:variants', localVariants.value);
};

// Update variant field
const updateVariant = (index, field, value) => {
    localVariants.value[index][field] = value;
    emitUpdate();
};

// Remove variant
const removeVariant = (index) => {
    localVariants.value.splice(index, 1);
    emitUpdate();
};

// Bulk operations
const bulkSetStock = ref('');
const bulkSetPriceAdjustment = ref('');

const applyBulkStock = () => {
    const stock = parseInt(bulkSetStock.value);
    if (isNaN(stock) || stock < 0) return;

    localVariants.value.forEach(variant => {
        variant.stock = stock;
    });
    emitUpdate();
    bulkSetStock.value = '';
};

const applyBulkPriceAdjustment = () => {
    const adjustment = parseFloat(bulkSetPriceAdjustment.value);
    if (isNaN(adjustment)) return;

    localVariants.value.forEach(variant => {
        variant.price_adjustment = adjustment;
    });
    emitUpdate();
    bulkSetPriceAdjustment.value = '';
};

const toggleAllActive = () => {
    const allActive = localVariants.value.every(v => v.is_active);
    localVariants.value.forEach(variant => {
        variant.is_active = !allActive;
    });
    emitUpdate();
};

// Computed values
const totalVariants = computed(() => localVariants.value.length);
const activeVariants = computed(() => localVariants.value.filter(v => v.is_active).length);
const totalStock = computed(() => localVariants.value.reduce((sum, v) => sum + (parseInt(v.stock) || 0), 0));

// Get final price for variant
const getFinalPrice = (variant) => {
    const base = parseFloat(basePrice) || 0;
    const adjustment = parseFloat(variant.price_adjustment) || 0;
    return base + adjustment;
};
</script>

<template>
    <div class="space-y-4">

        <!-- Summary Stats -->
        <div v-if="totalVariants > 0" class="grid grid-cols-1 md:grid-cols-3 gap-4 p-4 bg-muted/30 rounded-lg">
            <div class="text-center">
                <div class="text-2xl font-bold text-primary">{{ totalVariants }}</div>
                <div class="text-sm text-muted-foreground">Total Variants</div>
            </div>
            <div class="text-center">
                <div class="text-2xl font-bold text-green-600">{{ activeVariants }}</div>
                <div class="text-sm text-muted-foreground">Active Variants</div>
            </div>
            <div class="text-center">
                <div class="text-2xl font-bold text-blue-600">{{ totalStock }}</div>
                <div class="text-sm text-muted-foreground">Total Stock</div>
            </div>
        </div>

        <!-- Bulk Operations -->
        <div v-if="totalVariants > 0" class="p-4 border rounded-lg space-y-3">
            <h4 class="font-medium">Bulk Operations</h4>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <!-- Bulk Stock -->
                <div class="flex gap-2">
                    <Input
                        v-model="bulkSetStock"
                        type="number"
                        min="0"
                        placeholder="Set all stock"
                        class="h-8 text-sm"
                    />
                    <Button
                        @click="applyBulkStock"
                        size="sm"
                        variant="outline"
                        :disabled="!bulkSetStock"
                    >
                        Apply Stock
                    </Button>
                </div>

                <!-- Bulk Price Adjustment -->
                <div class="flex gap-2">
                    <Input
                        v-model="bulkSetPriceAdjustment"
                        type="number"
                        step="0.01"
                        placeholder="Set all adjustments"
                        class="h-8 text-sm"
                    />
                    <Button
                        @click="applyBulkPriceAdjustment"
                        size="sm"
                        variant="outline"
                        :disabled="!bulkSetPriceAdjustment"
                    >
                        Apply Price
                    </Button>
                </div>

                <!-- Toggle All Active -->
                <Button
                    @click="toggleAllActive"
                    size="sm"
                    variant="outline"
                    class="w-full"
                >
                    <EyeOff v-if="activeVariants === totalVariants" class="h-4 w-4 mr-2" />
                    <Eye v-else class="h-4 w-4 mr-2" />
                    {{ activeVariants === totalVariants ? 'Deactivate All' : 'Activate All' }}
                </Button>
            </div>
        </div>

        <!-- Variants Table -->
        <div v-if="totalVariants > 0" class="border rounded-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-muted/50">
                    <tr>
                        <th class="text-left p-3 font-medium text-sm">Color</th>
                        <th class="text-left p-3 font-medium text-sm">Size</th>
                        <th class="text-left p-3 font-medium text-sm">Stock</th>
                        <th class="text-left p-3 font-medium text-sm">Price Adj.</th>
                        <th class="text-left p-3 font-medium text-sm">Final Price</th>
                        <th class="text-left p-3 font-medium text-sm">Status</th>
                        <th class="text-left p-3 font-medium text-sm">Actions</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y">
                    <tr
                        v-for="(variant, index) in localVariants"
                        :key="`${variant.color_id}-${variant.size_id}`"
                        class="hover:bg-muted/30 transition-colors"
                        :class="{ 'opacity-50': !variant.is_active }"
                    >
                        <!-- Color -->
                        <td class="p-3">
                            <div class="flex items-center space-x-2">
                                <div
                                    class="w-5 h-5 rounded-full border border-gray-300"
                                    :style="{ backgroundColor: variant.color_hex }"
                                ></div>
                                <span class="text-sm font-medium">{{ variant.color_name }}</span>
                            </div>
                        </td>

                        <!-- Size -->
                        <td class="p-3">
                            <Badge variant="outline">{{ variant.size_name }}</Badge>
                        </td>

                        <!-- Stock -->
                        <td class="p-3">
                            <Input
                                :value="variant.stock"
                                @input="updateVariant(index, 'stock', parseInt($event.target.value) || 0)"
                                type="number"
                                min="0"
                                class="h-8 w-20 text-sm"
                            />
                        </td>

                        <!-- Price Adjustment -->
                        <td class="p-3">
                            <div class="flex items-center space-x-1">
                                <span class="text-sm">$</span>
                                <Input
                                    :value="variant.price_adjustment"
                                    @input="updateVariant(index, 'price_adjustment', parseFloat($event.target.value) || 0)"
                                    type="number"
                                    step="0.01"
                                    class="h-8 w-20 text-sm"
                                />
                            </div>
                        </td>

                        <!-- Final Price -->
                        <td class="p-3">
                                <span class="text-sm font-medium text-green-600">
                                    ${{ getFinalPrice(variant).toFixed(2) }}
                                </span>
                        </td>

                        <!-- Status -->
                        <td class="p-3">
                            <div class="flex items-center space-x-2">
                                <Checkbox
                                    :checked="variant.is_active"
                                    @update:checked="updateVariant(index, 'is_active', $event)"
                                />
                                <span class="text-xs" :class="variant.is_active ? 'text-green-600' : 'text-red-600'">
                                        {{ variant.is_active ? 'Active' : 'Inactive' }}
                                    </span>
                            </div>
                        </td>

                        <!-- Actions -->
                        <td class="p-3">
                            <Button
                                @click="removeVariant(index)"
                                size="sm"
                                variant="ghost"
                                class="text-red-600 hover:text-red-700 hover:bg-red-50"
                            >
                                <Trash2 class="h-4 w-4" />
                            </Button>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Empty State -->
        <div v-else class="text-center py-8 text-muted-foreground border rounded-lg">
            <RefreshCw class="h-12 w-12 mx-auto mb-3 opacity-50" />
            <h3 class="font-medium mb-1">No Variants Generated</h3>
            <p class="text-sm">Add colors and sizes above to automatically generate product variants</p>
        </div>

    </div>
</template>
