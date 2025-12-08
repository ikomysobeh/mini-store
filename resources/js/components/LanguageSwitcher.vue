<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';

const { locale } = useI18n();
const isOpen = ref(false);

const currentLocale = computed(() => locale.value);
const localeLabel = computed(() => {
    return locale.value === 'ar' ? 'عربي' : 'English';
});

const localeFlag = computed(() => {
    return locale.value === 'ar' ? '🇸🇦' : '🇬🇧';
});

const switchLanguage = (newLocale: string) => {
    if (newLocale === currentLocale.value) {
        isOpen.value = false;
        return;
    }

    // Build new URL with proper locale prefix
    const currentPath = window.location.pathname;
    let newPath: string;

    // Remove any existing locale prefix
    const pathWithoutLocale = currentPath.replace(/^\/(en|ar)/, '') || '/';

    if (newLocale === 'ar') {
        // Add Arabic prefix
        newPath = '/ar' + pathWithoutLocale;
    } else {
        // Add English prefix (explicit)
        newPath = '/en' + pathWithoutLocale;
    }

    // Use full page reload to ensure server-side locale is set correctly
    window.location.href = newPath;
};
</script>

<template>
    <div class="relative inline-block text-left">
        <!-- Language Switcher Button -->
        <button
            type="button"
            @click="isOpen = !isOpen"
            class="inline-flex items-center gap-2 rounded-lg bg-background px-3 py-2 text-sm font-medium text-foreground transition-colors hover:bg-accent hover:text-accent-foreground focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2"
            :aria-label="`Switch language - Current: ${localeLabel}`"
        >
            <span class="text-base">{{ localeFlag }}</span>
            <span class="hidden sm:inline">{{ localeLabel }}</span>
            <svg
                class="h-4 w-4 transition-transform"
                :class="{ 'rotate-180': isOpen }"
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M19 9l-7 7-7-7"
                />
            </svg>
        </button>

        <!-- Dropdown Menu -->
        <transition
            enter-active-class="transition ease-out duration-100"
            enter-from-class="transform opacity-0 scale-95"
            enter-to-class="transform opacity-100 scale-100"
            leave-active-class="transition ease-in duration-75"
            leave-from-class="transform opacity-100 scale-100"
            leave-to-class="transform opacity-0 scale-95"
        >
            <div
                v-show="isOpen"
                class="absolute right-0 z-50 mt-2 w-48 origin-top-right rounded-lg bg-background shadow-lg ring-1 ring-border focus:outline-none"
                :class="currentLocale === 'ar' ? 'left-0 right-auto' : 'right-0'"
            >
                <div class="py-1" role="menu" aria-orientation="vertical">
                    <!-- English Option -->
                    <button
                        @click="switchLanguage('en')"
                        class="flex w-full items-center gap-3 px-4 py-2 text-sm transition-colors hover:bg-accent hover:text-accent-foreground"
                        :class="{
                            'bg-accent text-accent-foreground': currentLocale === 'en',
                            'text-foreground': currentLocale !== 'en',
                        }"
                        role="menuitem"
                    >
                        <span class="text-base">🇬🇧</span>
                        <span class="flex-1 text-left">English</span>
                        <svg
                            v-if="currentLocale === 'en'"
                            class="h-4 w-4 text-primary"
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M5 13l4 4L19 7"
                            />
                        </svg>
                    </button>

                    <!-- Arabic Option -->
                    <button
                        @click="switchLanguage('ar')"
                        class="flex w-full items-center gap-3 px-4 py-2 text-sm transition-colors hover:bg-accent hover:text-accent-foreground"
                        :class="{
                            'bg-accent text-accent-foreground': currentLocale === 'ar',
                            'text-foreground': currentLocale !== 'ar',
                        }"
                        role="menuitem"
                    >
                        <span class="text-base">🇸🇦</span>
                        <span class="flex-1" :class="currentLocale === 'ar' ? 'text-right' : 'text-left'">عربي</span>
                        <svg
                            v-if="currentLocale === 'ar'"
                            class="h-4 w-4 text-primary"
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M5 13l4 4L19 7"
                            />
                        </svg>
                    </button>
                </div>
            </div>
        </transition>

        <!-- Backdrop to close dropdown -->
        <div
            v-if="isOpen"
            @click="isOpen = false"
            class="fixed inset-0 z-40"
            aria-hidden="true"
        ></div>
    </div>
</template>
