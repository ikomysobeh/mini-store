<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Upload, X } from 'lucide-vue-next';
import { ref, watch } from 'vue';

interface Props {
    title?: string;
    description?: string;
    imagePreview?: string | null;
    maxSize?: number; // in MB
    acceptedTypes?: string[];
}

interface Emits {
    (e: 'imageSelected', file: File): void;
    (e: 'imageRemoved'): void;
}

const props = withDefaults(defineProps<Props>(), {
    title: 'Image Upload',
    description: 'Click to upload an image',
    maxSize: 5,
    acceptedTypes: () => ['image/jpeg', 'image/png', 'image/gif', 'image/webp']
});

const emit = defineEmits<Emits>();

const fileInput = ref<HTMLInputElement | null>(null);

// Helper functions
const formatFileSize = (bytes: number) => {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
};

const getAcceptString = () => {
    return props.acceptedTypes.join(',');
};

// Event handlers
const handleFileSelect = (event: Event) => {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];

    if (!file) return;

    // Validate file size
    if (file.size > props.maxSize * 1024 * 1024) {
        alert(`File size must be less than ${props.maxSize}MB`);
        target.value = '';
        return;
    }

    // Validate file type
    if (!props.acceptedTypes.includes(file.type)) {
        alert(`Please select a valid image file (${props.acceptedTypes.map(type => type.split('/')[1]).join(', ')})`);
        target.value = '';
        return;
    }

    emit('imageSelected', file);
    target.value = '';
};

const removeImage = () => {
    emit('imageRemoved');
    if (fileInput.value) {
        fileInput.value.value = '';
    }
};

const triggerFileInput = () => {
    fileInput.value?.click();
};
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle>{{ title }}</CardTitle>
        </CardHeader>
        <CardContent class="space-y-4">

            <!-- Image Preview -->
            <div v-if="imagePreview" class="relative inline-block w-full">
                <img
                    :src="imagePreview"
                    :alt="`${title} preview`"
                    class="w-full h-48 object-cover rounded-lg border"
                />
                <Button
                    variant="destructive"
                    size="sm"
                    @click="removeImage"
                    class="absolute top-2 right-2 h-8 w-8 p-0"
                >
                    <X class="h-4 w-4" />
                </Button>

                <!-- Change Image Button -->
                <div class="mt-3">
                    <Button variant="outline" size="sm" @click="triggerFileInput">
                        <Upload class="h-4 w-4 mr-2" />
                        Change Image
                    </Button>
                </div>
            </div>

            <!-- Upload Area -->
            <div
                v-else
                @click="triggerFileInput"
                class="relative border-2 border-dashed border-muted-foreground/25 rounded-lg hover:border-muted-foreground/50 transition-colors cursor-pointer"
            >
                <div class="p-8 text-center">
                    <Upload class="h-8 w-8 text-muted-foreground mx-auto mb-3" />
                    <p class="text-sm font-medium text-muted-foreground mb-1">{{ description }}</p>
                    <p class="text-xs text-muted-foreground">
                        {{ acceptedTypes.map(type => type.split('/')[1].toUpperCase()).join(', ') }} up to {{ maxSize }}MB
                    </p>
                </div>
            </div>

            <!-- Hidden File Input -->
            <input
                ref="fileInput"
                type="file"
                :accept="getAcceptString()"
                @change="handleFileSelect"
                class="hidden"
            />

            <!-- Help Text -->
            <p v-if="description && !imagePreview" class="text-xs text-muted-foreground">
                {{ title.toLowerCase() }}s help customers quickly identify and browse your content.
            </p>

        </CardContent>
    </Card>
</template>
