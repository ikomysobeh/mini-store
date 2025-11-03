<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { AlertCircle, Save } from 'lucide-vue-next';

interface Props {
    show: boolean;
    isProcessing: boolean;
}

interface Emits {
    (e: 'save'): void;
    (e: 'reset'): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();
</script>

<template>
    <Card v-if="show" class="sticky bottom-4 border-2 border-orange-200 bg-orange-50">
        <CardContent class="p-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-2">
                    <AlertCircle class="h-4 w-4 text-orange-600" />
                    <p class="text-sm font-medium text-orange-800">You have unsaved changes</p>
                </div>
                <div class="flex items-center space-x-3">
                    <Button variant="outline" @click="emit('reset')" :disabled="isProcessing">
                        Reset
                    </Button>
                    <Button @click="emit('save')" :disabled="isProcessing">
                        <Save class="h-4 w-4 mr-2" />
                        {{ isProcessing ? 'Saving...' : 'Save Changes' }}
                    </Button>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
