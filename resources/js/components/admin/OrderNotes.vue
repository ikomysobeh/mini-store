<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { FileText, MessageSquare } from 'lucide-vue-next';

interface Props {
    notes?: string;
    title?: string;
}

const { notes, title = 'Order Notes' } = defineProps<Props>();
</script>

<template>
    <Card v-if="notes">
        <CardHeader>
            <CardTitle class="flex items-center space-x-2">
                <FileText class="h-5 w-5" />
                <span>{{ title }}</span>
            </CardTitle>
        </CardHeader>
        <CardContent>
            <div class="bg-muted/30 p-4 rounded-lg border-l-4 border-l-blue-500">
                <div class="flex items-start space-x-2">
                    <MessageSquare class="h-4 w-4 text-muted-foreground mt-0.5 flex-shrink-0" />
                    <p class="text-sm leading-relaxed">{{ notes }}</p>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
