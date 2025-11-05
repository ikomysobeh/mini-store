<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { ShoppingCart, ArrowRight, Sparkles } from 'lucide-vue-next';
import { computed } from 'vue';

interface Props {
    siteName: string;
    heroTitle?: string;
    heroSubtitle?: string;
    heroBackgroundUrl?: string | null;
    heroUseBackground?: boolean | string; // ALLOW STRING TOO
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

const defaultTitle = heroTitle || 'Discover Amazing Products';
const defaultSubtitle = heroSubtitle || 'Premium quality items with modern design and exceptional value';

// FIXED: Convert to boolean properly
const useBackground = computed(() => {
    if (typeof heroUseBackground === 'string') {
        return heroUseBackground === '1' || heroUseBackground === 'true';
    }
    return Boolean(heroUseBackground);
});

// UPDATED: Use computed boolean
const heroStyles = computed(() => {
    if (useBackground.value && heroBackgroundUrl) {
        return {
            backgroundImage: `url(${heroBackgroundUrl})`,
            backgroundSize: 'cover',           // Covers entire area
            backgroundPosition: 'center', // Centers the image
            backgroundRepeat: 'no-repeat',     // No repetition

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
            badgeBg: 'bg-primary-foreground/20'
        };
    }

    switch (heroBackgroundOverlay) {
        case 'light':
            return {
                title: 'text-black',
                subtitle: 'text-black/80',
                badge: 'text-black',
                badgeBg: 'bg-black/20'
            };
        case 'dark':
        case 'none':
        default:
            return {
                title: 'text-white',
                subtitle: 'text-white/90',
                badge: 'text-white',
                badgeBg: 'bg-white/20'
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
                    Welcome to {{ siteName }}
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
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <Button
                    size="lg"
                    :variant="useBackground && heroBackgroundUrl ? 'default' : 'secondary'"
                    :class="buttonClasses.primary"
                    as="a"
                    href="/products"
                >
                    <ShoppingCart class="h-5 w-5 mr-2" />
                    Shop Now
                </Button>

                <Button
                    size="lg"
                    variant="outline"
                    :class="buttonClasses.primary"
                    as="a"
                    href="#featured"
                >
                    View Featured
                    <ArrowRight class="h-5 w-5 ml-2" />
                </Button>
            </div>
        </div>

        <!-- Background Image Indicator -->

    </section>
</template>
