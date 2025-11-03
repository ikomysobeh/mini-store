<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Label } from '@/components/ui/label';
import { Upload, X, Image, Info } from 'lucide-vue-next';
import { ref } from 'vue';

interface Props {
    logoPreview?: string | null;
    hasNewLogo: boolean;
    error?: string;
    maxSize?: number; // in MB
}

interface Emits {
    (e: 'logoSelected', file: File): void;
    (e: 'logoRemoved'): void;
    (e: 'existingLogoRemoved'): void;
}

const props = withDefaults(defineProps<Props>(), {
    maxSize: 2
});

const emit = defineEmits<Emits>();

const fileInput = ref<HTMLInputElement | null>(null);

const handleLogoUpload = (event: Event) => {
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
    if (!['image/jpeg', 'image/png', 'image/jpg', 'image/gif'].includes(file.type)) {
        alert('Please select a valid image file (JPEG, PNG, JPG, GIF)');
        target.value = '';
        return;
    }

    emit('logoSelected', file);
    target.value = '';
};

const triggerUpload = () => {
    fileInput.value?.click();
};
</script>

<template>
    <div class="space-y-4">
        <Label>Site Logo</Label>

        <!-- Current Logo -->
        <div v-if="logoPreview" class="space-y-4">
            <div class="flex items-center space-x-4">
                <img :src="logoPreview" alt="Current logo" class="h-16 w-auto border rounded bg-white p-2" />
                <div class="space-y-2">
                    <p class="text-sm font-medium">Current Logo</p>
                    <div class="flex space-x-2">
                        <Button variant="outline" size="sm" @click="triggerUpload">
                            <Upload class="h-4 w-4 mr-2" />
                            Change Logo
                        </Button>
                        <Button
                            variant="destructive"
                            size="sm"
                            @click="hasNewLogo ? emit('logoRemoved') : emit('existingLogoRemoved')"
                        >
                            <X class="h-4 w-4 mr-2" />
                            Remove
                        </Button>
                    </div>
                </div>
            </div>

            <!-- New Logo Badge -->
            <Badge v-if="hasNewLogo" class="bg-blue-100 text-blue-800">
                <Info class="h-3 w-3 mr-1" />
                New logo will be saved when you save settings
            </Badge>
        </div>

        <!-- Upload Area -->
        <div v-else @click="triggerUpload" class="relative border-2 border-dashed border-muted-foreground/25 rounded-lg hover:border-muted-foreground/50 transition-colors cursor-pointer">
            <div class="p-8 text-center">
                <Image class="h-12 w-12 text-muted-foreground mx-auto mb-4" />
                <p class="text-sm font-medium text-muted-foreground mb-2">Click to upload your site logo</p>
                <p class="text-xs text-muted-foreground">PNG, JPG, GIF up to {{ maxSize }}MB</p>
            </div>
        </div>

        <!-- Hidden File Input -->
        <input
            ref="fileInput"
            type="file"
            accept="image/*"
            @change="handleLogoUpload"
            class="hidden"
        />

        <!-- Error Message -->
        <p v-if="error" class="text-sm text-destructive">
            {{ error }}
        </p>

        <!-- Help Text -->
        <p class="text-xs text-muted-foreground">
            Upload your site logo. Recommended size: 200x50px for best results.
        </p>
    </div>
</template>
