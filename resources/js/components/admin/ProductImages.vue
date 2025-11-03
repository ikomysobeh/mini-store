<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Upload, X, Plus } from 'lucide-vue-next';
import { ref } from 'vue';

interface GalleryItem {
    file: File;
    preview: string;
}

interface Props {
    imagePreview?: string | null;
    galleryPreviews: GalleryItem[];
    maxImageSize?: number; // in MB
    maxGalleryImages?: number;
}

interface Emits {
    (e: 'imageSelected', file: File): void;
    (e: 'imageRemoved'): void;
    (e: 'galleryImagesAdded', files: File[]): void;
    (e: 'galleryImageRemoved', index: number): void;
}

const props = withDefaults(defineProps<Props>(), {
    maxImageSize: 10,
    maxGalleryImages: 10
});

const emit = defineEmits<Emits>();

const fileInput = ref<HTMLInputElement | null>(null);
const galleryInput = ref<HTMLInputElement | null>(null);

// Event handlers
const handleImageUpload = (event: Event) => {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];

    if (!file) return;

    // Validate file size
    if (file.size > props.maxImageSize * 1024 * 1024) {
        alert(`File size must be less than ${props.maxImageSize}MB`);
        target.value = '';
        return;
    }

    // Validate file type
    if (!file.type.startsWith('image/')) {
        alert('Please select an image file');
        target.value = '';
        return;
    }

    emit('imageSelected', file);
    target.value = '';
};

const handleGalleryUpload = (event: Event) => {
    const target = event.target as HTMLInputElement;
    const files = Array.from(target.files || []);

    if (files.length === 0) return;

    // Check gallery limit
    if (props.galleryPreviews.length + files.length > props.maxGalleryImages) {
        alert(`You can only upload up to ${props.maxGalleryImages} gallery images`);
        target.value = '';
        return;
    }

    // Validate each file
    const validFiles: File[] = [];
    for (const file of files) {
        if (file.size > props.maxImageSize * 1024 * 1024) {
            alert(`File "${file.name}" is too large. Maximum size is ${props.maxImageSize}MB`);
            continue;
        }

        if (!file.type.startsWith('image/')) {
            alert(`File "${file.name}" is not an image`);
            continue;
        }

        validFiles.push(file);
    }

    if (validFiles.length > 0) {
        emit('galleryImagesAdded', validFiles);
    }

    target.value = '';
};

const triggerMainImageUpload = () => {
    fileInput.value?.click();
};

const triggerGalleryUpload = () => {
    galleryInput.value?.click();
};
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle>Product Images</CardTitle>
        </CardHeader>
        <CardContent class="space-y-6">

            <!-- Main Image -->
            <div class="space-y-4">
                <Label>Featured Image</Label>

                <!-- Upload Area (when no image) -->
                <div
                    v-if="!imagePreview"
                    @click="triggerMainImageUpload"
                    class="relative border-2 border-dashed border-muted-foreground/25 rounded-lg hover:border-muted-foreground/50 transition-colors cursor-pointer"
                >
                    <div class="p-8 text-center">
                        <Upload class="h-8 w-8 text-muted-foreground mx-auto mb-2" />
                        <p class="text-sm font-medium text-muted-foreground mb-2">Click to upload or drag and drop</p>
                        <p class="text-xs text-muted-foreground">PNG, JPG, GIF up to {{ maxImageSize }}MB</p>
                    </div>
                </div>

                <!-- Image Preview (when image exists) -->
                <div v-else class="relative inline-block w-full">
                    <div class="relative">
                        <img :src="imagePreview" alt="Product preview" class="w-36 h-36 object-full rounded-lg border" />
                        <Button
                            variant="destructive"
                            size="sm"
                            @click="emit('imageRemoved')"
                            class="absolute top-2 right-2 h-8 w-8 p-0"
                        >
                            <X class="h-4 w-4" />
                        </Button>
                    </div>

                    <!-- Change Image Button -->
                    <div class="mt-3">
                        <Button variant="outline" size="sm" @click="triggerMainImageUpload">
                            <Upload class="h-4 w-4 mr-2" />
                            Change Image
                        </Button>
                    </div>
                </div>

                <!-- Hidden Main Image Input -->
                <input
                    ref="fileInput"
                    type="file"
                    accept="image/*"
                    @change="handleImageUpload"
                    class="hidden"
                />
            </div>


        </CardContent>
    </Card>
</template>
