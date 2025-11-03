<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { ArrowLeft } from 'lucide-vue-next';

interface Props {
    href?: string;
    label?: string;
    className?: string;
}

const { href = '/products', label = 'Back to Products', className = 'mb-6' } = defineProps<Props>();

const handleBack = () => {
    if (href.startsWith('http') || href.startsWith('/')) {
        router.visit(href);
    } else {
        window.history.back();
    }
};
</script>

<template>
    <Button
        variant="outline"
        size="sm"
        @click="handleBack"
        :class="className"
    >
        <ArrowLeft class="h-4 w-4 mr-2" />
        {{ label }}
    </Button>
</template>
