<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
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
    <div class="bg-white border-b border-gray-200 px-4 sm:px-6 lg:px-8 py-3">
        <nav class="flex" aria-label="Breadcrumb">
            <ol class="flex items-center space-x-2">

                <!-- Home -->
                <li>
                    <Link href="/admin/dashboard" class="text-gray-400 hover:text-gray-500">
                        <Home class="h-4 w-4" />
                        <span class="sr-only">Home</span>
                    </Link>
                </li>

                <!-- Breadcrumb items -->
                <li v-for="(item, index) in items" :key="index" class="flex items-center">
                    <ChevronRight class="h-4 w-4 text-gray-400 mx-2" />

                    <Link
                        v-if="item.href && !item.isActive"
                        :href="item.href"
                        class="text-sm font-medium text-gray-500 hover:text-gray-700"
                    >
                        {{ item.label }}
                    </Link>

                    <span
                        v-else
                        class="text-sm font-medium text-gray-900"
                        :class="{ 'text-blue-600': item.isActive }"
                    >
                        {{ item.label }}
                    </span>
                </li>
            </ol>
        </nav>
    </div>
</template>
