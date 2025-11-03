<script setup lang="ts">
import { ChevronRight, Home } from 'lucide-vue-next';

interface BreadcrumbItem {
    label: string;
    href?: string;
    isActive?: boolean;
}

interface Props {
    items: BreadcrumbItem[];
}

const props = defineProps<Props>();
</script>

<template>
    <nav class="flex items-center space-x-2 text-sm mb-6" aria-label="Breadcrumb">
        <ol class="flex items-center space-x-2">

            <li v-for="(item, index) in items" :key="index" class="flex items-center space-x-2">

                <!-- Separator -->
                <ChevronRight v-if="index > 0" class="h-4 w-4 text-muted-foreground" />

                <!-- Breadcrumb Item -->
                <div class="flex items-center space-x-1">
                    <Home v-if="index === 0" class="h-4 w-4" />

                    <a
                        v-if="item.href && !item.isActive"
                        :href="item.href"
                        class="text-muted-foreground hover:text-foreground transition-colors"
                    >
                        {{ item.label }}
                    </a>

                    <span
                        v-else
                        class="font-medium text-foreground"
                        :class="{ 'text-primary': item.isActive }"
                    >
                        {{ item.label }}
                    </span>
                </div>
            </li>
        </ol>
    </nav>
</template>
