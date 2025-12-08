<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import {
    ShoppingCart, Heart, Star, Sparkles, Package,
    Truck, Shield, RotateCcw, Award
} from 'lucide-vue-next';
import { computed } from 'vue';
import { useLocale } from '@/composables/useLocale';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
const { localizedUrl } = useLocale();

interface CartItem {
    id: number;
    name: string;
    price: number;
    quantity: number;
    image?: string;
    product?: {
        name: string;
        image?: string;
        category?: {
            name: string;
        };
    };
    category?: {
        name: string;
    };
}

interface Category {
    id: number;
    name: string;
    slug: string;
    products_count?: number;
}

interface Props {
    cartItems: CartItem[];
    categories?: Category[];
    isProductPage?: boolean;
    currentProduct?: any;
}

const { cartItems, categories = [], isProductPage = false, currentProduct } = defineProps<Props>();

// ✅ UPDATED: Removed shipping calculations
const cartTotal = computed(() =>
    cartItems.reduce((sum, item) => sum + (parseFloat(item.price.toString()) * parseInt(item.quantity.toString())), 0)
);

// Helper functions
const formatPrice = (price: number) => {
    return parseFloat(price.toString()).toFixed(2);
};
</script>

<template>
    <div class="sticky top-24 space-y-6">

        <!-- Cart Summary (Default) -->
        <Card v-if="!isProductPage" class="shadow-lg">
            <CardHeader class="pb-3">
                <CardTitle class="flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <ShoppingCart class="h-5 w-5" />
                        <span>{{ t('sidebar.shoppingCart') }}</span>
                    </div>
                    <Badge v-if="cartItems.length > 0" variant="secondary">
                        {{ cartItems.length }}
                    </Badge>
                </CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
                <!-- Empty Cart -->
                <div v-if="cartItems.length === 0" class="text-center py-8">
                    <div class="w-16 h-16 bg-muted rounded-full flex items-center justify-center mx-auto mb-3">
                        <ShoppingCart class="h-8 w-8 text-muted-foreground" />
                    </div>
                    <p class="text-sm text-muted-foreground mb-3">{{ t('sidebar.cartEmpty') }}</p>
                    <Button size="sm" variant="outline" as="a" :href="localizedUrl('/products')">
                        {{ t('sidebar.startShopping') }}
                    </Button>
                </div>

                <!-- Cart Items -->
                <div v-else class="space-y-3">
                    <div v-for="item in cartItems.slice(0, 3)" :key="item.id"
                         class="flex items-center space-x-3 p-3 bg-muted/30 rounded-lg transition-colors">
                        <div class="relative">
                            <img :src="item.product?.image || item.image || '/placeholder-product.jpg'"
                                 :alt="item.product?.name || item.name"
                                 class="w-12 h-12 object-cover rounded-md border" />
                            <Badge class="absolute -top-1 -right-1 h-5 w-5 p-0 flex items-center justify-center text-xs bg-primary text-primary-foreground">
                                {{ item.quantity || 1 }}
                            </Badge>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium truncate">{{ item.product?.name || item.name }}</p>
                            <p class="text-xs text-muted-foreground">{{ item.product?.category?.name || item.category?.name || 'Product' }}</p>
                        </div>
                        <span class="text-sm font-semibold">${{ formatPrice((item.price || 0) * (item.quantity || 1)) }}</span>
                    </div>

                    <div v-if="cartItems.length > 3" class="text-center text-sm text-muted-foreground">
                        {{ t('sidebar.moreItems', { count: cartItems.length - 3 }) }}
                    </div>

                    <Separator class="my-4" />

                    <!-- ✅ UPDATED: Simplified Cart Totals (No Shipping) -->
                    <div class="space-y-3">
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-muted-foreground">{{ t('sidebar.subtotal') }}:</span>
                            <span class="font-medium">${{ formatPrice(cartTotal) }}</span>
                        </div>

                        <Separator />
                        
                        <div class="flex justify-between items-center font-semibold text-lg">
                            <span>{{ t('sidebar.total') }}:</span>
                            <span class="text-xl text-primary">${{ formatPrice(cartTotal) }}</span>
                        </div>
                    </div>

                    <!-- Cart Actions -->
                    <div class="space-y-2 pt-2">
                        <Button class="w-full" variant="default" as="a" :href="localizedUrl('/checkout')">
                            <ShoppingCart class="h-4 w-4 mr-2" />
                            {{ t('sidebar.checkout') }}
                        </Button>
                        <Button class="w-full" variant="outline" as="a" :href="localizedUrl('/cart')">
                            {{ t('sidebar.viewCart') }} ({{ cartItems.length }})
                        </Button>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Product Details Sidebar (When viewing a product) -->
        <Card v-if="isProductPage && currentProduct" class="shadow-lg">
            <CardHeader class="pb-3">
                <CardTitle class="flex items-center space-x-2">
                    <Star class="h-5 w-5" />
                    <span>{{ t('sidebar.productDetails') }}</span>
                </CardTitle>
            </CardHeader>
            <CardContent class="space-y-6">
                <div class="space-y-3">
                    <h3 class="font-semibold text-lg line-clamp-2">{{ currentProduct.name }}</h3>
                    <div class="flex items-center space-x-2">
                        <Badge variant="secondary" class="capitalize">
                            {{ currentProduct.category?.name || 'Uncategorized' }}
                        </Badge>
                        <div class="flex items-center space-x-1" v-if="currentProduct.rating">
                            <Star class="h-4 w-4 fill-warning text-warning" />
                            <span class="text-sm">{{ currentProduct.rating }} ({{ currentProduct.reviews_count || 0 }} {{ t('sidebar.reviews') }})</span>
                        </div>
                    </div>
                </div>

                <Separator />

                <div class="space-y-4">
                    <div>
                        <p class="text-sm mb-1">{{ t('sidebar.price') }}</p>
                        <p class="text-3xl font-bold">${{ formatPrice(currentProduct.price) }}</p>
                    </div>

                    <div>
                        <p class="text-sm mb-2">{{ t('sidebar.availability') }}</p>
                        <div class="flex items-center space-x-2">
                            <Badge :variant="(currentProduct.stock || 0) > 0 ? 'default' : 'destructive'">
                                {{ (currentProduct.stock || 0) > 0 ? `${currentProduct.stock} ${t('sidebar.inStock')}` : t('sidebar.outOfStock') }}
                            </Badge>
                            <span v-if="(currentProduct.stock || 0) > 0 && (currentProduct.stock || 0) < 10" class="text-xs text-destructive">
                                {{ t('sidebar.onlyLeft', { count: currentProduct.stock }) }}
                            </span>
                        </div>
                    </div>

                    <div v-if="currentProduct.description" class="space-y-2">
                        <p class="text-sm font-medium">{{ t('product.description') }}</p>
                        <p class="text-sm text-muted-foreground line-clamp-3">{{ currentProduct.description }}</p>
                    </div>
                </div>

                <Separator />

                <div class="space-y-3">
                    <Button
                        class="w-full"
                        :disabled="(currentProduct.stock || 0) === 0"
                        variant="default"
                    >
                        <ShoppingCart class="h-4 w-4 mr-2" />
                        {{ t('sidebar.addToCart') }}
                    </Button>
                    <Button class="w-full" variant="outline">
                        <Heart class="h-4 w-4 mr-2" />
                        {{ t('sidebar.addToWishlist') }}
                    </Button>
                </div>
            </CardContent>
        </Card>

        <!-- Categories Quick Access -->
        <Card v-if="categories.length > 0">
            <CardHeader>
                <CardTitle class="text-base">{{ t('sidebar.shopByCategory') }}</CardTitle>
            </CardHeader>
            <CardContent class="space-y-2">
                <Button
                    v-for="category in categories.slice(0, 6)"
                    :key="category.id"
                    variant="ghost"
                    size="sm"
                    class="w-full justify-start"
                    as="a"
                    :href="localizedUrl(`/products?category=${category.slug}`)"
                >
                    {{ category.name }}
                    <Badge v-if="category.products_count" variant="outline" class="ml-auto">
                        {{ category.products_count }}
                    </Badge>
                </Button>
                <Button v-if="categories.length > 6" variant="outline" size="sm" class="w-full" as="a" :href="localizedUrl('/products')">
                    {{ t('sidebar.viewAllCategories') }}
                </Button>
            </CardContent>
        </Card>
    </div>
</template>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.line-clamp-3 {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
