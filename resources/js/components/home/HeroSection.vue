<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { ShoppingCart, ArrowRight, Sparkles } from 'lucide-vue-next';

interface Props {
    siteName: string;
    heroTitle?: string;
    heroSubtitle?: string;
}

const { siteName, heroTitle, heroSubtitle } = defineProps<Props>();

const defaultTitle = heroTitle || 'Discover Amazing Products';
const defaultSubtitle = heroSubtitle || 'Premium quality items with modern design and exceptional value';
</script>

<template>
    <section class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-primary via-primary/90 to-primary/80">
        <div class="absolute inset-0 bg-[url('/hero-pattern.svg')] opacity-10"></div>
        <div class="relative px-8 py-16 text-center">
            <div class="inline-flex items-center bg-primary-foreground/20 px-4 py-2 rounded-full mb-6">
                <Sparkles class="h-4 w-4 mr-2 text-primary-foreground" />
                <span class="text-sm text-primary-foreground/90">Welcome to {{ siteName }}</span>
            </div>

            <h1 class="text-4xl md:text-6xl font-bold text-primary-foreground mb-6 leading-tight">
                {{ defaultTitle }}
            </h1>
            <p class="text-xl text-primary-foreground/80 max-w-2xl mx-auto mb-8 leading-relaxed">
                {{ defaultSubtitle }}
            </p>

            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <Button
                    size="lg"
                    variant="secondary"
                    class="hover:scale-105 transition-all duration-300 shadow-lg"
                    as="a"
                    href="/products"
                >
                    <ShoppingCart class="h-5 w-5 mr-2" />
                    Shop Now
                </Button>
                <Button
                    size="lg"
                    variant="outline"
                    class="border-primary-foreground/20 text-primary-foreground hover:bg-primary-foreground/10"
                    as="a"
                    href="#featured"
                >
                    View Featured
                    <ArrowRight class="h-5 w-5 ml-2" />
                </Button>
            </div>
        </div>
    </section>
</template>
