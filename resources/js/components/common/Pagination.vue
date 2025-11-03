<!-- resources/js/components/common/Pagination.vue -->
<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { ChevronLeft, ChevronRight } from 'lucide-vue-next';

const { links } = defineProps({
    links: { type: Array, required: true }
});

const emit = defineEmits(['go-to-page']);

const goToPage = (url) => {
    if (url) {
        emit('go-to-page', url);
    }
};
</script>

<template>
    <div v-if="links.length > 3" class="flex items-center justify-between">

        <!-- Previous Button -->
        <Button
            :disabled="!links[0].url"
            variant="outline"
            size="sm"
            @click="goToPage(links[0].url)"
        >
            <ChevronLeft class="h-4 w-4 mr-2" />
            Previous
        </Button>

        <!-- Page Numbers -->
        <div class="flex items-center space-x-1">
            <template v-for="(link, index) in links" :key="index">
                <Button
                    v-if="index !== 0 && index !== links.length - 1"
                    :variant="link.active ? 'default' : 'outline'"
                    size="sm"
                    :disabled="!link.url"
                    @click="goToPage(link.url)"
                    v-html="link.label"
                />
            </template>
        </div>

        <!-- Next Button -->
        <Button
            :disabled="!links[links.length - 1].url"
            variant="outline"
            size="sm"
            @click="goToPage(links[links.length - 1].url)"
        >
            Next
            <ChevronRight class="h-4 w-4 ml-2" />
        </Button>

    </div>
</template>
