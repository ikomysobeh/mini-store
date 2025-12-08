<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { ShoppingCart, ArrowRight, Heart, Star, Gift } from 'lucide-vue-next';
import { useI18n } from 'vue-i18n';
import { useLocale } from '@/composables/useLocale';

const { t } = useI18n();
const { localizedUrl } = useLocale();

interface Props {
    className?: string;
    showDonationOption?: boolean;
}

const { className = '', showDonationOption = false } = defineProps<Props>();
</script>

<template>
    <div :class="`text-center py-20 ${className}`">
        <!-- Empty Cart Icon -->
        <div class="w-32 h-32 bg-gradient-to-br from-muted to-muted/60 rounded-full flex items-center justify-center mx-auto mb-8 shadow-inner">
            <ShoppingCart class="h-16 w-16 text-muted-foreground" />
        </div>

        <!-- Main Message -->
        <h2 class="text-3xl font-bold mb-3">{{ t('checkout.emptyCart') }}</h2>
        <p class="text-muted-foreground mb-8 max-w-md mx-auto">
            {{ t('checkout.emptyCartMessage') }}
        </p>

        <!-- Action Buttons -->
        <div class="flex flex-col sm:flex-row gap-4 justify-center mb-12">
            <Button as="a" :href="localizedUrl('/products')" size="lg" class="min-w-48">
                <ShoppingCart class="h-5 w-5 mr-2" />
                {{ t('home.shopNow') }}
            </Button>
            <Button variant="outline" as="a" :href="localizedUrl('/products?featured=true')" size="lg">
                <Star class="h-5 w-5 mr-2" />
                {{ t('home.featuredProducts') }}
            </Button>
            <Button
                v-if="showDonationOption"
                variant="outline"
                as="a"
                :href="localizedUrl('/products?type=donation')"
                size="lg"
                class="text-warning hover:bg-warning/10"
            >
                <Gift class="h-5 w-5 mr-2" />
                {{ t('checkout.orDonate') }}
            </Button>
        </div>

        <!-- Quick Info Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 max-w-3xl mx-auto">
            <div class="text-center p-6 bg-primary/10 rounded-xl">
                <div class="w-12 h-12 bg-primary/20 rounded-full flex items-center justify-center mx-auto mb-4">
                    <ShoppingCart class="h-6 w-6 text-primary" />
                </div>
                <h3 class="font-semibold mb-2">{{ t('checkout.browseProducts') }}</h3>
                <p class="text-sm text-muted-foreground">{{ t('checkout.browseProductsDesc') }}</p>
            </div>

            <div class="text-center p-6 bg-destructive/10 rounded-xl">
                <div class="w-12 h-12 bg-destructive/20 rounded-full flex items-center justify-center mx-auto mb-4">
                    <Heart class="h-6 w-6 text-destructive" />
                </div>
                <h3 class="font-semibold mb-2">{{ t('checkout.checkWishlist') }}</h3>
                <p class="text-sm text-muted-foreground">{{ t('checkout.checkWishlistDesc') }}</p>
            </div>

            <div class="text-center p-6 bg-secondary/10 rounded-xl">
                <div class="w-12 h-12 bg-secondary/20 rounded-full flex items-center justify-center mx-auto mb-4">
                    <Star class="h-6 w-6 text-secondary" />
                </div>
                <h3 class="font-semibold mb-2">{{ t('checkout.featuredItems') }}</h3>
                <p class="text-sm text-muted-foreground">{{ t('checkout.featuredItemsDesc') }}</p>
            </div>
        </div>

        <!-- Additional Help -->
        <div class="mt-12 p-6 bg-muted/30 rounded-xl max-w-md mx-auto">
            <h4 class="font-semibold mb-2">{{ t('checkout.needHelp') }}</h4>
            <p class="text-sm text-muted-foreground mb-4">
                {{ t('checkout.helpMessage') }}
            </p>
            <div class="flex flex-col sm:flex-row gap-2 justify-center">
                <Button variant="outline" size="sm" as="a" :href="localizedUrl('/contact')">
                    {{ t('checkout.contactSupport') }}
                </Button>
                <Button variant="outline" size="sm" as="a" :href="localizedUrl('/help')">
                    {{ t('checkout.viewFaq') }}
                </Button>
            </div>
        </div>
    </div>
</template>
