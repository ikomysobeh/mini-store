<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3'; // Added usePage import
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { ShoppingCart, Plus, Minus, CheckCircle, XCircle, AlertCircle } from 'lucide-vue-next';
import { ref, computed, watch } from 'vue';

// ============== INLINE AUTHENTICATION LOGIC ==============
const page = usePage()

const isAuthenticated = computed(() => {
  return !!(
    page.props.auth?.user || 
    page.props.user || 
    page.props.auth_user ||
    page.props.authUser
  )
})

const currentUser = computed(() => {
  return page.props.auth?.user || 
         page.props.user || 
         page.props.auth_user ||
         page.props.authUser ||
         null
})

const redirectToLogin = (returnUrl?: string) => {
  const currentPath = returnUrl || window.location.pathname
  const query = `?redirect=${encodeURIComponent(currentPath)}`
  router.visit(`/login${query}`)
}

const requireAuth = (callback: () => void, returnUrl?: string) => {
  if (!isAuthenticated.value) {
    redirectToLogin(returnUrl)
    return false
  }
  
  if (typeof callback === 'function') {
    callback()
  }
  return true
}

const can = (permission: string) => {
  return page.props.can?.[permission] || false
}
// ============== END AUTHENTICATION LOGIC ==============

const { product, user } = defineProps({
    product: { type: Object, required: true },
    user: { type: Object, default: null }
});

// State
const quantity = ref(1);
const selectedColor = ref(null);
const selectedSize = ref(null);
const isAddingToCart = ref(false);

// ADDED: Message state for user-friendly errors
const cartMessage = ref('');
const messageType = ref(''); // 'success', 'error', 'warning'

// Selected variant computed
const selectedVariant = computed(() => {
    if (!product.has_variants || !selectedColor.value || !selectedSize.value) {
        return null;
    }

    return product.variants.find(v =>
        v.color_id === selectedColor.value && v.size_id === selectedSize.value
    );
});

// Available sizes based on selected color
const availableSizes = computed(() => {
    if (!product.has_variants || !selectedColor.value) {
        return product.available_sizes || [];
    }

    const colorVariants = product.variants.filter(v => v.color_id === selectedColor.value);
    const sizeIds = colorVariants.map(v => v.size_id);

    return product.available_sizes.filter(size => sizeIds.includes(size.id));
});

// FIXED: Enhanced price computation
const currentPrice = computed(() => {
    if (selectedVariant.value) {
        return selectedVariant.value.price;
    }
    return product.price;
});

// FIXED: Enhanced stock computation - This was the main issue
const currentStock = computed(() => {
    if (product.has_variants) {
        if (selectedVariant.value) {
            return selectedVariant.value.stock;
        } else {
            // When no specific variant is selected, show total available stock
            const totalVariantStock = product.variants.reduce((total, variant) => {
                return total + parseInt(variant.stock || 0);
            }, 0);
            return totalVariantStock;
        }
    }
    return product.stock || 0;
});

// Stock availability logic
const isInStock = computed(() => {
    if (product.is_donatable) {
        return true;
    }

    if (product.has_variants) {
        if (selectedVariant.value) {
            return selectedVariant.value.stock > 0;
        } else {
            return product.variants.some(variant => variant.stock > 0);
        }
    }

    return (product.stock || 0) > 0;
});

// Add to cart availability
const canAddToCart = computed(() => {
    if (product.is_donatable) {
        return true;
    }

    if (product.has_variants) {
        return selectedVariant.value && selectedVariant.value.stock > 0;
    }

    return isInStock.value;
});

// Max quantity logic
const maxQuantity = computed(() => {
    if (product.is_donatable) return 99;

    if (product.has_variants && selectedVariant.value) {
        return Math.min(selectedVariant.value.stock, 99);
    } else if (product.has_variants && !selectedVariant.value) {
        const maxVariantStock = Math.max(...product.variants.map(v => v.stock || 0));
        return Math.min(maxVariantStock, 99);
    }

    return Math.min(currentStock.value, 99);
});

// Stock status message
const stockStatusMessage = computed(() => {
    if (product.is_donatable) {
        return { type: 'donation', message: 'Available for donation' };
    }

    if (product.has_variants) {
        if (selectedVariant.value) {
            if (selectedVariant.value.stock > 0) {
                return {
                    type: 'in-stock',
                    message: `${selectedVariant.value.stock} in stock for this variant`
                };
            } else {
                return { type: 'out-of-stock', message: 'This variant is out of stock' };
            }
        } else {
            const availableVariants = product.variants.filter(v => v.stock > 0);
            if (availableVariants.length > 0) {
                return {
                    type: 'variants-available',
                    message: `${availableVariants.length} variants available (Total: ${currentStock.value} items)`
                };
            } else {
                return { type: 'out-of-stock', message: 'All variants are out of stock' };
            }
        }
    }

    if (currentStock.value > 0) {
        return { type: 'in-stock', message: `${currentStock.value} in stock` };
    } else {
        return { type: 'out-of-stock', message: 'Out of stock' };
    }
});

// ADDED: Message display helpers
const getMessageIcon = computed(() => {
    switch (messageType.value) {
        case 'success': return CheckCircle;
        case 'error': return XCircle;
        case 'warning': return AlertCircle;
        default: return null;
    }
});

const getMessageClasses = computed(() => {
    const baseClasses = 'flex items-center space-x-2 p-3 rounded-lg text-sm font-medium';
    switch (messageType.value) {
        case 'success': return baseClasses + ' bg-success/10 text-success border border-success/20';
        case 'error': return baseClasses + ' bg-destructive/10 text-destructive border border-destructive/20';
        case 'warning': return baseClasses + ' bg-warning/10 text-warning border border-warning/20';
        default: return baseClasses + ' bg-info/10 text-info border border-info/20';
    }
});

// Watch for color changes to reset size
watch(selectedColor, () => {
    selectedSize.value = null;
});

// Methods
const formatPrice = (price) => parseFloat(price.toString()).toFixed(2);

const selectColor = (colorId) => {
    selectedColor.value = colorId;
};

const selectSize = (sizeId) => {
    selectedSize.value = sizeId;
};

const incrementQuantity = () => {
    if (quantity.value < maxQuantity.value) {
        quantity.value++;
    }
};

const decrementQuantity = () => {
    if (quantity.value > 1) {
        quantity.value--;
    }
};

// ADDED: Show message helper
const showMessage = (message, type) => {
    cartMessage.value = message;
    messageType.value = type;

    // Auto-hide success messages after 3 seconds
    if (type === 'success') {
        setTimeout(() => {
            cartMessage.value = '';
            messageType.value = '';
        }, 3000);
    }
};

// MODIFIED: Enhanced add to cart with authentication check
const addToCart = () => {
    // Check authentication before proceeding
    requireAuth(() => {
        // Original add to cart logic
        if (product.has_variants && !selectedVariant.value) {
            showMessage('Please select both color and size before adding to cart.', 'warning');
            return;
        }

        isAddingToCart.value = true;
        cartMessage.value = '';

        const data = {
            quantity: quantity.value
        };

        if (selectedVariant.value) {
            data.variant_id = selectedVariant.value.id;
        }

        // FIXED: Using fetch for better JSON error handling
        fetch(`/cart/add/${product.id}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify(data)
        })
            .then(async response => {
                const jsonData = await response.json();

                if (response.ok && jsonData.success) {
                    showMessage(jsonData.message || 'Item added to cart successfully!', 'success');
                } else {
                    // Handle error responses
                    showMessage(jsonData.message || 'Failed to add item to cart.', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showMessage('Something went wrong. Please try again.', 'error');
            })
            .finally(() => {
                isAddingToCart.value = false;
            });
    }, window.location.pathname); // Pass current path for redirect after login
};
</script>

<template>
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 mb-12">

        <!-- Product Images -->
        <div class="space-y-6 p-6">
            <div class="relative aspect-square bg-muted rounded-lg overflow-hidden">
                <img
                    :src="product.image || '/placeholder.jpg'"
                    :alt="product.name"
                    class="w-full h-full object-cover"
                />
                <Badge v-if="product.is_donatable" class="absolute top-4 right-4 bg-warning/20 text-warning">
                    Donation
                </Badge>
            </div>
        </div>

        <!-- Product Info -->
        <div class="space-y-6 p-6">

            <!-- Category -->
            <Badge variant="outline" class="capitalize">
                {{ product.category?.name || 'Uncategorized' }}
            </Badge>

            <!-- Product Name -->
            <h1 class="text-3xl font-bold">{{ product.name }}</h1>

            <!-- Price -->
            <div class="space-y-2">
                <span class="text-3xl font-bold text-primary">${{ formatPrice(currentPrice) }}</span>
                <p v-if="product.is_donatable" class="text-sm text-muted-foreground">
                    This is a donation item.
                </p>
            </div>

            <!-- ADDED: Cart Message Display -->
            <div v-if="cartMessage" :class="getMessageClasses">
                <component :is="getMessageIcon" class="h-4 w-4 flex-shrink-0" />
                <span>{{ cartMessage }}</span>
                <button
                    v-if="messageType === 'error' || messageType === 'warning'"
                    @click="cartMessage = ''; messageType = ''"
                    class="ml-auto"
                >
                    <XCircle class="h-4 w-4" />
                </button>
            </div>

            <!-- Authentication Status Info -->
            <div v-if="!isAuthenticated" class="bg-blue-50 border border-blue-200 rounded-lg p-3">
                <p class="text-sm text-blue-800">
                    <span class="font-medium">Not signed in.</span> 
                    You'll be redirected to login when adding items to cart.
                </p>
            </div>

            <!-- Color Selection -->
            <div v-if="product.has_variants && product.available_colors" class="space-y-3">
                <h4 class="font-medium">Color:</h4>
                <div class="flex gap-2">
                    <button
                        v-for="color in product.available_colors"
                        :key="color.id"
                        @click="selectColor(color.id)"
                        :class="[
                            'w-12 h-12 rounded-full border-2 transition-all',
                            selectedColor === color.id ? 'ring-2 ring-primary ring-offset-2' : 'border-gray-300'
                        ]"
                        :style="{ backgroundColor: color.hex_code }"
                        :title="color.name"
                    />
                </div>
                <p v-if="selectedColor" class="text-sm text-gray-600">
                    Selected: {{ product.available_colors.find(c => c.id === selectedColor)?.name }}
                </p>
            </div>

            <!-- Size Selection -->
            <div v-if="product.has_variants && availableSizes.length > 0" class="space-y-3">
                <h4 class="font-medium">Size:</h4>
                <div class="flex gap-2">
                    <button
                        v-for="size in availableSizes"
                        :key="size.id"
                        @click="selectSize(size.id)"
                        :class="[
                            'px-4 py-2 border rounded-md transition-all',
                            selectedSize === size.id
                                ? 'border-primary bg-primary text-white'
                                : 'border-gray-300 hover:border-primary'
                        ]"
                    >
                        {{ size.name }}
                    </button>
                </div>
                <p v-if="selectedSize" class="text-sm text-gray-600">
                    Selected: {{ availableSizes.find(s => s.id === selectedSize)?.name }}
                </p>
            </div>

            <!-- Stock Status -->
            <div class="space-y-2">
                <div
                    v-if="stockStatusMessage.type === 'in-stock' || stockStatusMessage.type === 'variants-available'"
                    class="flex items-center space-x-2 text-success"
                >
                    <div class="w-2 h-2 bg-success rounded-full"></div>
                    <span class="text-sm font-medium">{{ stockStatusMessage.message }}</span>
                </div>
                <div
                    v-else-if="stockStatusMessage.type === 'donation'"
                    class="flex items-center space-x-2 text-info"
                >
                    <div class="w-2 h-2 bg-info rounded-full"></div>
                    <span class="text-sm font-medium">{{ stockStatusMessage.message }}</span>
                </div>
                <div
                    v-else-if="stockStatusMessage.type === 'out-of-stock'"
                    class="flex items-center space-x-2 text-destructive"
                >
                    <div class="w-2 h-2 bg-destructive rounded-full"></div>
                    <span class="text-sm font-medium">{{ stockStatusMessage.message }}</span>
                </div>
                
                <!-- Variant selection reminder -->
                <div v-if="product.has_variants && (!selectedColor || !selectedSize)" class="text-warning text-sm">
                    Please select {{ !selectedColor ? 'color' : '' }}{{ !selectedColor && !selectedSize ? ' and ' : '' }}{{ !selectedSize ? 'size' : '' }}
                </div>
            </div>

            <!-- Quantity and Add to Cart -->
            <div v-if="canAddToCart || stockStatusMessage.type === 'variants-available'" class="space-y-4">
                <div class="space-y-2">
                    <label class="text-sm font-medium">Quantity</label>
                    <div class="flex items-center space-x-3">
                        <div class="flex items-center border rounded-md">
                            <Button
                                size="sm"
                                variant="ghost"
                                @click="decrementQuantity"
                                :disabled="quantity <= 1 || isAddingToCart"
                                class="h-10 w-10 p-0"
                            >
                                <Minus class="h-4 w-4" />
                            </Button>

                            <Input
                                v-model.number="quantity"
                                :max="maxQuantity"
                                min="1"
                                type="number"
                                :disabled="isAddingToCart"
                                class="h-10 w-16 text-center border-0"
                            />

                            <Button
                                size="sm"
                                variant="ghost"
                                @click="incrementQuantity"
                                :disabled="quantity >= maxQuantity || isAddingToCart"
                                class="h-10 w-10 p-0"
                            >
                                <Plus class="h-4 w-4" />
                            </Button>
                        </div>
                        <span class="text-sm text-muted-foreground">
                            Max: {{ maxQuantity }}
                        </span>
                    </div>
                </div>

                <!-- ENHANCED: Add to Cart Button -->
                <Button
                    @click="addToCart"
                    :disabled="!canAddToCart || isAddingToCart"
                    size="lg"
                    class="w-full"
                    :class="isAddingToCart ? 'opacity-75 cursor-not-allowed' : ''"
                >
                    <ShoppingCart v-if="!isAddingToCart" class="h-4 w-4 mr-2" />
                    <div v-else class="animate-spin h-4 w-4 mr-2 border-2 border-white border-t-transparent rounded-full"></div>
                    {{ isAddingToCart ? 'Adding...' : (product.is_donatable ? 'Donate Now' : 'Add to Cart') }}
                </Button>
            </div>

            <!-- Selection Required Message -->
            <div v-else-if="product.has_variants && stockStatusMessage.type === 'variants-available'" class="p-4 bg-info/10 border border-info/20 rounded-lg">
                <p class="text-info font-medium">Please select color and size to continue</p>
            </div>

        </div>
    </div>
</template>

<style scoped>
input[type="number"]::-webkit-outer-spin-button,
input[type="number"]::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}

input[type="number"] {
    -moz-appearance: textfield;
}
</style>
