<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Plus, Minus, Trash2 } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

interface CartItem {
    id: number;
    name: string;
    price: number;
    quantity: number;
    original_price?: number;
    image?: string;
    slug?: string;
    variant_display?: {  // ✅ NEW
        has_variant: boolean;
        color_name: string | null;
        color_hex: string | null;
        size_name: string | null;
        sku: string | null;
    };
    product?: {
        name: string;
        slug: string;
        image?: string;
        stock: number;
        is_donatable: boolean;
        category?: {
            name: string;
        };
    };
    category?: {
        name: string;
    };
}

interface Props {
    cartItems: CartItem[];
    quantities: Record<number, number>;
    updatingItems: Set<number>;
}

interface Emits {
    (e: 'incrementQuantity', itemId: number): void;
    (e: 'decrementQuantity', itemId: number): void;
    (e: 'updateQuantity', itemId: number, quantity: number): void;
    (e: 'removeItem', itemId: number): void;
    (e: 'clearCart'): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

// Create reactive refs for quantities to ensure proper binding
const localQuantities = ref<Record<number, number>>({});

// Initialize local quantities when component mounts or props change
watch(() => props.cartItems, (newItems) => {
    newItems.forEach(item => {
        if (!localQuantities.value[item.id]) {
            localQuantities.value[item.id] = props.quantities[item.id] || item.quantity || 1;
        }
    });
}, { immediate: true });

// Update local quantities when props.quantities change
watch(() => props.quantities, (newQuantities) => {
    Object.keys(newQuantities).forEach(key => {
        const itemId = parseInt(key);
        if (newQuantities[itemId] !== undefined) {
            localQuantities.value[itemId] = newQuantities[itemId];
        }
    });
}, { deep: true });

// Helper functions
const formatPrice = (price: number) => {
    return parseFloat(price.toString()).toFixed(2);
};

const getDisplayQuantity = (item: CartItem) => {
    return localQuantities.value[item.id] || item.quantity || 1;
};

const handleQuantityInput = (itemId: number, event: Event) => {
    const target = event.target as HTMLInputElement;
    const newQuantity = Math.max(1, parseInt(target.value) || 1);

    localQuantities.value[itemId] = newQuantity;

    const currentItem = props.cartItems.find(item => item.id === itemId);
    const currentQuantity = props.quantities[itemId] || (currentItem ? currentItem.quantity : 1);

    if (newQuantity !== currentQuantity) {
        emit('updateQuantity', itemId, newQuantity);
    }
};

const handleIncrement = (itemId: number) => {
    const currentQuantity = getDisplayQuantity(props.cartItems.find(item => item.id === itemId)!);
    localQuantities.value[itemId] = currentQuantity + 1;
    emit('incrementQuantity', itemId);
};

const handleDecrement = (itemId: number) => {
    const currentQuantity = getDisplayQuantity(props.cartItems.find(item => item.id === itemId)!);
    if (currentQuantity > 1) {
        localQuantities.value[itemId] = currentQuantity - 1;
        emit('decrementQuantity', itemId);
    }
};

const getItemTotal = (item: CartItem) => {
    const quantity = getDisplayQuantity(item);
    return item.price * quantity;
};
</script>

<template>
    <div class="lg:col-span-2 space-y-4">
        <!-- Header with Clear Button -->
        <div class="flex justify-between items-center">
            <p class="text-sm text-muted-foreground">
                {{ cartItems.length }} {{ t('cart.itemsInCart') }}
            </p>
            <Button
                variant="outline"
                size="sm"
                @click="emit('clearCart')"
                class="text-destructive hover:text-destructive"
            >
                <Trash2 class="h-4 w-4 mr-2" />
                {{ t('cart.empty') }}
            </Button>
        </div>

        <!-- Items Card -->
        <Card>
            <CardContent class="p-0">
                <div
                    v-for="(item, index) in cartItems"
                    :key="item.id"
                    class="border-b last:border-b-0"
                >
                    <div class="p-6 flex items-center space-x-4">
                        <!-- Product Image -->
                        <div class="flex-shrink-0">
                            <img
                                :src="item.product?.image || item.image || '/placeholder-product.jpg'"
                                :alt="item.product?.name || item.name"
                                class="w-20 h-20 object-cover rounded-lg border"
                            />
                        </div>

                        <!-- Product Info -->
                        <div class="flex-grow min-w-0">
                            <h3 class="font-semibold text-lg mb-1">
                                <a
                                    :href="`/products/${item.product?.slug || item.slug}`"
                                    class="hover:text-primary transition-colors"
                                >
                                    {{ item.product?.name || item.name }}
                                </a>
                            </h3>
                            <p class="text-muted-foreground text-sm mb-2">
                                {{ item.product?.category?.name || item.category?.name || 'Uncategorized' }}
                            </p>

                            <!-- ✅ NEW: Variant Display -->
                            <div v-if="item.variant_display?.has_variant" class="text-sm text-muted-foreground space-y-1 mb-2">
                                <div v-if="item.variant_display.color_name" class="flex items-center gap-2">
                                    <span class="font-medium">{{ t('product.selectColor') }}:</span>
                                    <div class="flex items-center gap-1">
                                        <div 
                                            class="w-4 h-4 rounded-full border border-gray-300"
                                            :style="{ backgroundColor: item.variant_display.color_hex }"
                                        ></div>
                                        <span>{{ item.variant_display.color_name }}</span>
                                    </div>
                                </div>
                                
                                <div v-if="item.variant_display.size_name" class="flex items-center gap-2">
                                    <span class="font-medium">{{ t('product.selectSize') }}:</span>
                                    <span>{{ item.variant_display.size_name }}</span>
                                </div>
                                
                                <div v-if="item.variant_display.sku" class="text-xs text-gray-500">
                                    SKU: {{ item.variant_display.sku }}
                                </div>
                            </div>

                            <!-- Product Badges -->
                            <div class="flex space-x-2 mb-2">
                                <Badge
                                    v-if="item.product?.is_donatable"
                                    variant="secondary"
                                    class="text-xs"
                                >
                                    {{ t('cart.donationItem') }}
                                </Badge>
                                <Badge
                                    v-if="item.product?.stock !== undefined && item.product.stock < 10"
                                    variant="outline"
                                    class="text-xs text-orange-600"
                                >
                                    {{ t('cart.onlyLeft', { count: item.product.stock }) }}
                                </Badge>
                            </div>

                            <!-- Mobile Price & Actions -->
                            <div class="sm:hidden mt-4 flex items-center justify-between">
                                <div class="flex flex-col">
                                    <span class="text-lg font-bold text-primary">
                                        ${{ formatPrice(item.price) }}
                                    </span>
                                    <span
                                        v-if="item.original_price && item.original_price > item.price"
                                        class="text-sm text-muted-foreground line-through"
                                    >
                                        ${{ formatPrice(item.original_price) }}
                                    </span>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <!-- Mobile Quantity Controls -->
                                    <div class="flex items-center border rounded-md">
                                        <Button
                                            size="sm"
                                            variant="ghost"
                                            @click="handleDecrement(item.id)"
                                            :disabled="updatingItems.has(item.id) || getDisplayQuantity(item) <= 1"
                                            class="h-8 w-8 p-0"
                                        >
                                            <Minus class="h-3 w-3" />
                                        </Button>

                                        <input
                                            :value="getDisplayQuantity(item)"
                                            @input="handleQuantityInput(item.id, $event)"
                                            :disabled="updatingItems.has(item.id)"
                                            class="h-8 w-12 text-center border-0 text-sm bg-transparent focus:outline-none"
                                            type="number"
                                            min="1"
                                            max="99"
                                        />

                                        <Button
                                            size="sm"
                                            variant="ghost"
                                            @click="handleIncrement(item.id)"
                                            :disabled="updatingItems.has(item.id)"
                                            class="h-8 w-8 p-0"
                                        >
                                            <Plus class="h-3 w-3" />
                                        </Button>
                                    </div>

                                    <!-- Mobile Remove Button -->
                                    <Button
                                        size="sm"
                                        variant="ghost"
                                        @click="emit('removeItem', item.id)"
                                        class="text-destructive hover:text-destructive h-8 w-8 p-0"
                                    >
                                        <Trash2 class="h-3 w-3" />
                                    </Button>
                                </div>
                            </div>
                        </div>

                        <!-- Desktop Price -->
                        <div class="hidden sm:block text-right min-w-0">
                            <div class="text-lg font-bold text-primary">
                                ${{ formatPrice(item.price) }}
                            </div>
                            <div
                                v-if="item.original_price && item.original_price > item.price"
                                class="text-sm text-muted-foreground line-through"
                            >
                                ${{ formatPrice(item.original_price) }}
                            </div>
                        </div>

                        <!-- Desktop Quantity Controls -->
                        <div class="hidden sm:flex items-center border rounded-md">
                            <Button
                                size="sm"
                                variant="ghost"
                                @click="handleDecrement(item.id)"
                                :disabled="updatingItems.has(item.id) || getDisplayQuantity(item) <= 1"
                                class="h-10 w-10 p-0"
                            >
                                <Minus class="h-4 w-4" />
                            </Button>

                            <input
                                :value="getDisplayQuantity(item)"
                                @input="handleQuantityInput(item.id, $event)"
                                :disabled="updatingItems.has(item.id)"
                                class="h-10 w-16 text-center border-0 bg-transparent focus:outline-none"
                                type="number"
                                min="1"
                                max="99"
                            />

                            <Button
                                size="sm"
                                variant="ghost"
                                @click="handleIncrement(item.id)"
                                :disabled="updatingItems.has(item.id)"
                                class="h-10 w-10 p-0"
                            >
                                <Plus class="h-4 w-4" />
                            </Button>
                        </div>

                        <!-- Desktop Total Price -->
                        <div class="hidden sm:block text-right min-w-0">
                            <div class="text-lg font-bold">
                               ${{ formatPrice(getItemTotal(item)) }}
                            </div>
                            <div v-if="updatingItems.has(item.id)" class="text-xs text-muted-foreground">
                                {{ t('cart.updating') }}
                            </div>
                        </div>

                        <!-- Desktop Remove Button -->
                        <div class="hidden sm:block">
                            <Button
                                size="sm"
                                variant="ghost"
                                @click="emit('removeItem', item.id)"
                                class="text-destructive hover:text-destructive"
                                :title="t('cart.removeItem')"
                            >
                                <Trash2 class="h-4 w-4" />
                            </Button>
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>
    </div>
</template>

<style scoped>
/* Custom number input styling */
input[type="number"]::-webkit-outer-spin-button,
input[type="number"]::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}

input[type="number"] {
    -moz-appearance: textfield;
}
</style>
