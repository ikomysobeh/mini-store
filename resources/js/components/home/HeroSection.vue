<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { ShoppingCart, ArrowRight, Sparkles, MessageCircle, Facebook } from 'lucide-vue-next';
import { computed } from 'vue';
import { useLocale } from '@/composables/useLocale';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
const { localizedUrl } = useLocale();

interface Props {
    siteName: string;
    heroTitle?: string;
    heroSubtitle?: string;
    heroBackgroundUrl?: string | null;
    heroUseBackground?: boolean | string;
    heroBackgroundOverlay?: string;
}

const {
    siteName,
    heroTitle,
    heroSubtitle,
    heroBackgroundUrl,
    heroUseBackground = false,
    heroBackgroundOverlay = 'dark'
} = defineProps<Props>();

const defaultTitle = computed(() => heroTitle || t('home.discoverProducts'));
const defaultSubtitle = computed(() => heroSubtitle || t('home.premiumQuality'));

// Convert to boolean properly
const useBackground = computed(() => {
    if (typeof heroUseBackground === 'string') {
        return heroUseBackground === '1' || heroUseBackground === 'true';
    }
    return Boolean(heroUseBackground);
});

const heroStyles = computed(() => {
    if (useBackground.value && heroBackgroundUrl) {
        return {
            backgroundImage: `url(${heroBackgroundUrl})`,
            backgroundSize: 'cover',
            backgroundPosition: 'center',
            backgroundRepeat: 'no-repeat',
        };
    }
    return {};
});

const heroClasses = computed(() => {
    const baseClasses = 'relative overflow-hidden rounded-2xl';

    if (useBackground.value && heroBackgroundUrl) {
        return baseClasses;
    }
    return `${baseClasses} bg-gradient-to-br from-primary via-primary/90 to-primary/80`;
});

const overlayClasses = computed(() => {
    if (!useBackground.value || !heroBackgroundUrl) return '';

    switch (heroBackgroundOverlay) {
        case 'dark':
            return 'absolute inset-0 bg-black/50';
        case 'light':
            return 'absolute inset-0 bg-white/50';
        case 'none':
        default:
            return '';
    }
});

const textClasses = computed(() => {
    if (!useBackground.value || !heroBackgroundUrl) {
        return {
            title: 'text-primary-foreground',
            subtitle: 'text-primary-foreground/80',
            badge: 'text-primary-foreground',
            badgeBg: 'bg-primary-foreground/20',
            contact: 'text-primary-foreground/90'
        };
    }

    switch (heroBackgroundOverlay) {
        case 'light':
            return {
                title: 'text-black',
                subtitle: 'text-black/80',
                badge: 'text-black',
                badgeBg: 'bg-black/20',
                contact: 'text-black/90'
            };
        case 'dark':
        case 'none':
        default:
            return {
                title: 'text-white',
                subtitle: 'text-white/90',
                badge: 'text-white',
                badgeBg: 'bg-white/20',
                contact: 'text-white/90'
            };
    }
});

const buttonClasses = computed(() => {
    if (!useBackground.value || !heroBackgroundUrl) {
        return {
            primary: 'hover:scale-105 transition-all duration-300 shadow-lg',
            secondary: 'hover:scale-105 transition-all duration-300 shadow-lg'
        };
    }

    return {
        primary: 'bg-white text-black hover:bg-gray-100 hover:scale-105 transition-all duration-300 shadow-lg',
        secondary: 'border-white text-white hover:bg-white/10 hover:scale-105 transition-all duration-300 shadow-lg'
    };
});

// ✅ NEW: WhatsApp link generators
const getWhatsAppLink = (phoneNumber: string) => {
    // Remove any spaces, dashes, or special characters
    const cleanNumber = phoneNumber.replace(/\D/g, '');
    // For Syria, add country code +963
    const internationalNumber = cleanNumber.startsWith('963') ? cleanNumber : `963${cleanNumber}`;
    return `https://wa.me/${internationalNumber}`;
};
</script>

<template>
    <section
        :class="heroClasses"
        :style="heroStyles"
    >
        <!-- Pattern Overlay -->
        <div
            v-if="!useBackground || !heroBackgroundUrl"
            class="absolute inset-0 bg-[url('/hero-pattern.svg')] opacity-10"
        ></div>

        <!-- Background Image Overlay -->
        <div
            v-if="overlayClasses"
            :class="overlayClasses"
        ></div>

        <div class="relative px-8 py-16 text-center">
            <!-- Welcome Badge -->
            <div :class="['inline-flex items-center px-4 py-2 rounded-full mb-6', textClasses.badgeBg]">
                <Sparkles :class="['h-4 w-4 mr-2', textClasses.badge]" />
                <span :class="['text-sm', textClasses.badge, 'opacity-90']">
                    {{ t('home.welcomeTo') }} {{ siteName }}
                </span>
            </div>

            <!-- Hero Title -->
            <h1 :class="['text-4xl md:text-6xl font-bold mb-6 leading-tight', textClasses.title]">
                {{ defaultTitle }}
            </h1>

            <!-- Hero Subtitle -->
            <p :class="['text-xl max-w-2xl mx-auto mb-8 leading-relaxed', textClasses.subtitle]">
                {{ defaultSubtitle }}
            </p>

            <!-- CTA Buttons -->
            <div class="flex flex-col sm:flex-row gap-4 justify-center mb-8">
                <Button
                    size="lg"
                    :variant="useBackground && heroBackgroundUrl ? 'default' : 'secondary'"
                    :class="buttonClasses.primary"
                    as="a"
                    :href="localizedUrl('/products')"
                >
                    <ShoppingCart class="h-5 w-5 mr-2" />
                    {{ t('home.shopNow') }}
                </Button>

                <Button
                    size="lg"
                    variant="outline"
                    :class="buttonClasses.primary"
                    as="a"
                    href="#featured"
                >
                    {{ t('home.viewFeatured') }}
                    <ArrowRight class="h-5 w-5 ml-2" />
                </Button>
            </div>

            <!-- ✅ Custom Order Button -->
            <div class="mb-6">
                <a 
                    :href="getWhatsAppLink('963937671126')"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="inline-flex items-center gap-3 px-6 py-3 bg-green-500 hover:bg-green-600 text-white font-semibold rounded-full shadow-lg hover:shadow-xl transition-all duration-300 hover:scale-105"
                >
                    <MessageCircle class="h-5 w-5" />
                    <span>{{ t('home.customOrder') }}</span>
                </a>
            </div>

            <!-- ✅ UPDATED: WhatsApp Contact Information - Button Style -->
            <div :class="['max-w-3xl mx-auto space-y-4', textClasses.contact]">
                <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
                    <!-- WhatsApp for Initiative -->
                    <a 
                        :href="getWhatsAppLink('963944255208')"
                        target="_blank"
                        rel="noopener noreferrer"
                        :class="['inline-flex items-center gap-2 px-4 py-2.5 rounded-full border-2 border-green-400/50 bg-green-500/20 hover:bg-green-500/40 transition-all duration-300 hover:scale-105 cursor-pointer', textClasses.contact]"
                    >
                        <MessageCircle class="h-4 w-4 text-green-400" />
                        <span class="font-medium">3lmni al 9aid:</span>
                        <span class="font-semibold">963944255208</span>
                    </a>

                    <!-- WhatsApp for Technical Issues -->
                    <a 
                        :href="getWhatsAppLink('963937671126')"
                        target="_blank"
                        rel="noopener noreferrer"
                        :class="['inline-flex items-center gap-2 px-4 py-2.5 rounded-full border-2 border-green-400/50 bg-green-500/20 hover:bg-green-500/40 transition-all duration-300 hover:scale-105 cursor-pointer', textClasses.contact]"
                    >
                        <MessageCircle class="h-4 w-4 text-green-400" />
                        <span class="font-medium">{{ t('home.technicalSupport') }}:</span>
                        <span class="font-semibold">963937671126</span>
                    </a>
                </div>

                <!-- Facebook Link -->
                <div class="flex items-center justify-center">
                    <a 
                        href="https://www.facebook.com/share/1KAWdthyWQ/" 
                        target="_blank"
                        rel="noopener noreferrer"
                        :class="['inline-flex items-center gap-2 px-4 py-2.5 rounded-full border-2 border-blue-400/50 bg-blue-500/20 hover:bg-blue-500/40 transition-all duration-300 hover:scale-105 cursor-pointer', textClasses.contact]"
                    >
                        <Facebook class="h-4 w-4 text-blue-400" />
                        <span class="font-medium">{{ t('home.followFacebook') }}</span>
                    </a>
                </div>
            </div>
        </div>
    </section>
</template>
