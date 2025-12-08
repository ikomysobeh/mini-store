<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from '@/components/ui/dialog';
import { ShoppingCart, Plus, Minus, CheckCircle, XCircle, AlertCircle, ChevronLeft, ChevronRight, Eye, UserPlus, LogIn, Truck } from 'lucide-vue-next';
import { ref, computed, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { useLocale } from '@/composables/useLocale';

const { t } = useI18n();
const { localizedUrl, cartAddUrl } = useLocale();

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

// Auth modal state
const showAuthModal = ref(false);

const redirectToLogin = (returnUrl?: string) => {
    const currentPath = returnUrl || (typeof window !== 'undefined' ? window.location.pathname : localizedUrl('/products'))
    const query = `?redirect=${encodeURIComponent(currentPath)}`
    router.visit(localizedUrl(`/login${query}`))
}
const redirectToRegister = (returnUrl?: string) => {
    const currentPath = returnUrl || (typeof window !== 'undefined' ? window.location.pathname : localizedUrl('/products'))
    const query = `?redirect=${encodeURIComponent(currentPath)}`
    router.visit(localizedUrl(`/register${query}`))
}

// Show modal instead of redirecting
const requireAuth = (callback: () => void, returnUrl?: string) => {
    if (!isAuthenticated.value) {
        showAuthModal.value = true;
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

// Message state for user-friendly errors
const cartMessage = ref('');
const messageType = ref('');

// Image gallery state
const selectedImageIndex = ref(0);
const isLightboxOpen = ref(false);

// Image gallery computed properties
const currentImage = computed(() => {
    if (product.gallery && product.gallery.length > 0) {
        return product.gallery[selectedImageIndex.value]?.url || product.primary_image || product.image;
    }
    return product.primary_image || product.image || '/placeholder.jpg';
});

const hasMultipleImages = computed(() => {
    return product.has_gallery && product.images_count > 1;
});

const imageGallery = computed(() => {
    return product.gallery || [];
});

// Image navigation methods
const selectImage = (index: number) => {
    selectedImageIndex.value = index;
};

const nextImage = () => {
    if (hasMultipleImages.value) {
        selectedImageIndex.value = (selectedImageIndex.value + 1) % imageGallery.value.length;
    }
};

const prevImage = () => {
    if (hasMultipleImages.value) {
        const length = imageGallery.value.length;
        selectedImageIndex.value = (selectedImageIndex.value - 1 + length) % length;
    }
};

const openLightbox = () => {
    isLightboxOpen.value = true;
};

const closeLightbox = () => {
    isLightboxOpen.value = false;
};

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

// Enhanced price computation
const currentPrice = computed(() => {
    if (selectedVariant.value) {
        return selectedVariant.value.price;
    }
    return product.price;
});

// Enhanced stock computation
const currentStock = computed(() => {
    if (product.has_variants) {
        if (selectedVariant.value) {
            return selectedVariant.value.stock;
        } else {
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
    if (product.has_variants) {
        if (selectedVariant.value) {
            if (selectedVariant.value.stock > 0) {
                return {
                    type: 'in-stock',
                    message: `${selectedVariant.value.stock} ${t('product.inStockVariant')}`
                };
            } else {
                return { type: 'out-of-stock', message: t('product.outOfStock') };
            }
        } else {
            const availableVariants = product.variants.filter(v => v.stock > 0);
            if (availableVariants.length > 0) {
                return {
                    type: 'variants-available',
                    message: `${availableVariants.length} ${t('product.variantsAvailable')} (${t('product.total')}: ${currentStock.value} ${t('product.items')})`
                };
            } else {
                return { type: 'out-of-stock', message: t('product.outOfStock') };
            }
        }
    }

    if (currentStock.value > 0) {
        return { type: 'in-stock', message: `${currentStock.value} ${t('product.inStock')}` };
    } else {
        return { type: 'out-of-stock', message: t('product.outOfStock') };
    }
});

// Message display helpers
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

// Show message helper
const showMessage = (message, type) => {
    cartMessage.value = message;
    messageType.value = type;

    if (type === 'success') {
        setTimeout(() => {
            cartMessage.value = '';
            messageType.value = '';
        }, 3000);
    }
};

// ✅ FIXED: Use fetch (keep backend JSON response)
const addToCart = () => {
    requireAuth(() => {
        if (product.has_variants && !selectedVariant.value) {
            showMessage(t('productDetail.selectColorSizeWarning'), 'warning');
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

        fetch(cartAddUrl(product.id), {
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
                    showMessage(jsonData.message || t('productDetail.addedToCartSuccess'), 'success');
                    
                    // ✅ UPDATED: Reload cart data after success
                    router.reload({ only: ['cartItems'] });
                } else {
                    showMessage(jsonData.message || t('productDetail.addToCartFailed'), 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showMessage(t('productDetail.somethingWentWrong'), 'error');
            })
            .finally(() => {
                isAddingToCart.value = false;
            });
    });
};


</script>

<template>
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 mb-12">
        <!-- Product Images with Gallery -->
        <div class="space-y-6 p-6">
            <!-- Main Image Display -->
            <div class="relative aspect-square bg-muted rounded-lg overflow-hidden group">
                <img
                    :src="currentImage"
                    :alt="product.name"
                    class="w-full h-full object-cover cursor-pointer transition-transform group-hover:scale-105"
                    @click="openLightbox"
                />
                <Badge v-if="product.is_donatable" class="absolute top-4 right-4 bg-warning/20 text-warning">
                    {{ t('product.donation') }}
                </Badge>

                <!-- Navigation arrows for multiple images -->
                <div v-if="hasMultipleImages" class="absolute inset-y-0 left-0 right-0 flex items-center justify-between p-4 opacity-0 group-hover:opacity-100 transition-opacity">
                    <button
                        @click="prevImage"
                        class="p-2 bg-black/50 text-white rounded-full hover:bg-black/70 transition-colors"
                    >
                        <ChevronLeft class="h-5 w-5" />
                    </button>
                    <button
                        @click="nextImage"
                        class="p-2 bg-black/50 text-white rounded-full hover:bg-black/70 transition-colors"
                    >
                        <ChevronRight class="h-5 w-5" />
                    </button>
                </div>

                <!-- Image counter -->
                <div v-if="hasMultipleImages" class="absolute top-4 left-4 bg-black/50 text-white px-2 py-1 rounded text-sm">
                    {{ selectedImageIndex + 1 }} / {{ product.images_count }}
                </div>

                <!-- Zoom indicator -->
                <div v-if="currentImage" class="absolute bottom-4 left-4 bg-black/50 text-white px-2 py-1 rounded text-sm opacity-0 group-hover:opacity-100 transition-opacity">
                    <Eye class="h-3 w-3 inline mr-1" />
                    {{ t('product.clickToZoom') }}
                </div>
            </div>

            <!-- Thumbnail Gallery -->
            <div v-if="hasMultipleImages" class="space-y-2">
                <div class="flex space-x-2 overflow-x-auto pb-2">
                    <button
                        v-for="(image, index) in imageGallery"
                        :key="image.id || index"
                        @click="selectImage(index)"
                        :class="[
                            'flex-shrink-0 w-20 h-20 rounded-md overflow-hidden border-2 transition-all',
                            selectedImageIndex === index
                                ? 'border-primary ring-2 ring-primary/20'
                                : 'border-gray-200 hover:border-gray-300'
                        ]"
                    >
                        <img
                            :src="image.url"
                            :alt="image.alt || product.name"
                            class="w-full h-full object-cover"
                        />
                    </button>
                </div>
                <p class="text-sm text-muted-foreground text-center">
                    {{ product.images_count }} {{ t('product.imagesAvailable') }} • {{ t('product.clickThumbnails') }}
                </p>
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
                    {{ t('product.donationItem') }}
                </p>
            </div>

            <!-- Cart Message Display -->
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

            <!-- Color Selection -->
            <div v-if="product.has_variants && product.available_colors" class="space-y-3">
                <h4 class="font-medium">{{ t('product.selectColor') }}:</h4>
                <div class="flex gap-2 flex-wrap">
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
                    {{ t('product.selected') }}: {{ product.available_colors.find(c => c.id === selectedColor)?.name }}
                </p>
            </div>

            <!-- Size Selection -->
            <div v-if="product.has_variants && availableSizes.length > 0" class="space-y-3">
                <h4 class="font-medium">{{ t('product.selectSize') }}:</h4>
                <div class="flex gap-2 flex-wrap">
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
                    {{ t('product.selected') }}: {{ availableSizes.find(s => s.id === selectedSize)?.name }}
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
                    {{ t('product.pleaseSelect') }} {{ !selectedColor ? t('product.color') : '' }}{{ !selectedColor && !selectedSize ? ` ${t('product.and')} ` : '' }}{{ !selectedSize ? t('product.size') : '' }}
                </div>
            </div>

            <!-- Quantity and Add to Cart -->
            <div v-if="canAddToCart || stockStatusMessage.type === 'variants-available'" class="space-y-4">
                <div class="space-y-2">
                    <label class="text-sm font-medium">{{ t('product.quantity') }}</label>
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
                            {{ t('product.max') }}: {{ maxQuantity }}
                        </span>
                    </div>
                </div>

                <!-- Add to Cart Button -->
                <Button
                    @click="addToCart"
                    :disabled="!canAddToCart || isAddingToCart"
                    size="lg"
                    class="w-full"
                    :class="isAddingToCart ? 'opacity-75 cursor-not-allowed' : ''"
                >
                    <ShoppingCart v-if="!isAddingToCart" class="h-4 w-4 mr-2" />
                    <div v-else class="animate-spin h-4 w-4 mr-2 border-2 border-white border-t-transparent rounded-full"></div>
                    {{ t('product.addToCart') }}
                </Button>

                <!-- Delivery Time Notice -->
                <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                    <div class="flex items-start space-x-3">
                        <Truck class="h-5 w-5 text-blue-600 dark:text-blue-400 flex-shrink-0 mt-0.5" />
                        <div class="flex-1">
                            <h4 class="text-sm font-semibold text-blue-900 dark:text-blue-300 mb-1">
                                {{ t('productDetail.information') }}
                            </h4>
                            <p class="text-sm text-blue-800 dark:text-blue-400">
                                {{ t('productDetail.deliveryInfo') }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Selection Required Message -->
            <div v-else-if="product.has_variants && stockStatusMessage.type === 'variants-available'" class="p-4 bg-info/10 border border-info/20 rounded-lg">
                <p class="text-info font-medium">{{ t('product.pleaseSelectColorSize') }}</p>
            </div>
        </div>
    </div>

    <!-- Authentication Required Modal -->
    <Dialog v-model:open="showAuthModal">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle class="flex items-center gap-2 text-xl">
                    <ShoppingCart class="h-5 w-5 text-primary" />
                    {{ t('productDetail.accountRequired') }}
                </DialogTitle>
                <DialogDescription class="text-base pt-2">
                    {{ t('productDetail.accountRequiredDesc') }}
                </DialogDescription>
            </DialogHeader>

            <div class="py-6 space-y-4">
                <!-- Benefits list -->
                <div class="bg-muted/50 rounded-lg p-4 space-y-2">
                    <p class="text-sm font-medium">{{ t('productDetail.withAccountYouCan') }}</p>
                    <ul class="text-sm text-muted-foreground space-y-1">
                        <li class="flex items-center gap-2">
                            <CheckCircle class="h-4 w-4 text-success" />
                            {{ t('productDetail.saveItemsToCart') }}
                        </li>
                        <li class="flex items-center gap-2">
                            <CheckCircle class="h-4 w-4 text-success" />
                            {{ t('productDetail.trackYourOrders') }}
                        </li>
                        <li class="flex items-center gap-2">
                            <CheckCircle class="h-4 w-4 text-success" />
                            {{ t('productDetail.fastCheckout') }}
                        </li>
                    </ul>
                </div>
            </div>

           <DialogFooter class="flex-col sm:flex-col gap-3">
    <!-- Register Button (Primary) -->
    <Button
        @click="redirectToRegister()"
        size="lg"
        class="w-full"
    >
        <UserPlus class="h-4 w-4 mr-2" />
        {{ t('productDetail.createAccount') }}
    </Button>

    <!-- Login Button (Secondary) -->
    <Button
        @click="redirectToLogin()"
        variant="outline"
        size="lg"
        class="w-full"
    >
        <LogIn class="h-4 w-4 mr-2" />
        {{ t('productDetail.logIn') }}
    </Button>

    <!-- Cancel Button -->
    <Button
        @click="showAuthModal = false"
        variant="ghost"
        size="sm"
        class="w-full"
    >
        {{ t('productDetail.continueBrowsing') }}
    </Button>
</DialogFooter>
        </DialogContent>
    </Dialog>

    <!-- Lightbox Modal -->
    <div
        v-if="isLightboxOpen && currentImage"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/80"
        @click="closeLightbox"
    >
        <div class="relative max-w-4xl max-h-full p-4">
            <img
                :src="currentImage"
                :alt="product.name"
                class="max-w-full max-h-full object-contain"
            />
            <button
                @click="closeLightbox"
                class="absolute top-4 right-4 text-white hover:text-gray-300 text-2xl bg-black/50 rounded-full w-10 h-10 flex items-center justify-center"
            >
                ×
            </button>
            <div v-if="hasMultipleImages" class="absolute inset-y-0 left-0 right-0 flex items-center justify-between px-4">
                <button
                    @click.stop="prevImage"
                    class="p-3 bg-black/50 text-white rounded-full hover:bg-black/70 transition-colors"
                >
                    <ChevronLeft class="h-6 w-6" />
                </button>
                <button
                    @click.stop="nextImage"
                    class="p-3 bg-black/50 text-white rounded-full hover:bg-black/70 transition-colors"
                >
                    <ChevronRight class="h-6 w-6" />
                </button>
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
