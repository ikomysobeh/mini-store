<!-- resources/js/components/common/Pagination.vue -->
<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { ChevronLeft, ChevronRight } from 'lucide-vue-next';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

interface Link {
    url: string | null;
    label: string;
    active: boolean;
}

const props = defineProps<{
    links: Link[];
}>();

const emit = defineEmits(['go-to-page']);

const goToPage = (url: string | null) => {
    if (url) {
        emit('go-to-page', url);
    }
};
</script>

<template>
    <div v-if="props.links.length > 3" class="flex items-center justify-between">

        <!-- Previous Button -->
        <Button
            :disabled="!props.links[0].url"
            variant="outline"
            size="sm"
            @click="goToPage(props.links[0].url)"
        >
            <ChevronLeft class="h-4 w-4 mr-2" />
            {{ t('common.previous') }}
        </Button>

        <!-- Page Numbers -->
        <div class="flex items-center space-x-1">
            <template v-for="(link, index) in props.links" :key="index">
                <Button
                    v-if="index !== 0 && index !== props.links.length - 1"
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
            :disabled="!props.links[props.links.length - 1].url"
            variant="outline"
            size="sm"
            @click="goToPage(props.links[props.links.length - 1].url)"
        >
            {{ t('common.next') }}
            <ChevronRight class="h-4 w-4 ml-2" />
        </Button>

    </div>
</template>
