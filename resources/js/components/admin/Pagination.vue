<script setup lang="ts">
import { Button } from '@/components/ui/button';

interface PaginationLink {
    label: string;
    url?: string;
    active: boolean;
}

interface Props {
    links: PaginationLink[];
    className?: string;
}

interface Emits {
    (e: 'goToPage', url: string): void;
}

const { links, className = 'flex justify-center mt-8' } = defineProps<Props>();
const emit = defineEmits<Emits>();

const goToPage = (url?: string) => {
    if (url) {
        emit('goToPage', url);
    }
};
</script>

<template>
    <div v-if="links && links.length > 3" :class="className">
        <div class="flex space-x-2">
            <Button
                v-for="(link, index) in links"
                :key="`${link.label}-${index}`"
                :variant="link.active ? 'default' : 'outline'"
                :disabled="!link.url"
                @click="goToPage(link.url)"
                size="sm"
                v-html="link.label"
                class="min-w-10"
            />
        </div>
    </div>
</template>
