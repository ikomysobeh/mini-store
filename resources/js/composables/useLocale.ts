import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

/**
 * Composable for handling locale-aware URLs
 */
export function useLocale() {
    const page = usePage();
    
    // Get current locale from Inertia shared data
    const locale = computed(() => (page.props.locale as string) || 'en');
    
    /**
     * Generate a localized URL by prepending the current locale
     * @param path - The path without locale prefix (e.g., '/products/my-product')
     * @returns The localized URL (e.g., '/en/products/my-product')
     */
    const localizedUrl = (path: string): string => {
        // Remove leading slash if present for consistent handling
        const cleanPath = path.startsWith('/') ? path.slice(1) : path;
        return `/${locale.value}/${cleanPath}`;
    };
    
    /**
     * Generate a localized product URL
     * @param slug - The product slug
     * @returns The localized product URL
     */
    const productUrl = (slug: string): string => {
        return localizedUrl(`/products/${slug}`);
    };
    
    /**
     * Generate a localized cart add URL
     * @param productId - The product ID
     * @returns The localized cart add URL
     */
    const cartAddUrl = (productId: number | string): string => {
        return localizedUrl(`/cart/add/${productId}`);
    };
    
    return {
        locale,
        localizedUrl,
        productUrl,
        cartAddUrl,
    };
}
