<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
</script>

<template>
    <div
        class="flex aspect-square size-8 items-center justify-center rounded-md bg-sidebar-primary text-sidebar-primary-foreground"
    >
        <AppLogoIcon class="size-5 fill-current text-white dark:text-black" />
    </div>
    <div class="ml-2 flex flex-1 items-center text-left">
        <span class="truncate text-sm font-semibold leading-tight sm:text-base md:text-lg"
            >Laravel Starter Kit</span
        >
    </div>
</template>
