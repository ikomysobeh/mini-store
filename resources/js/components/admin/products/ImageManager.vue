<script setup lang="ts">
import { ref } from 'vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Trash2, Star, Upload } from 'lucide-vue-next'

interface ProductImage {
    id: number
    url: string
    path: string
    is_primary: boolean
    sort_order: number
}

const props = defineProps<{
    images: ProductImage[]
    maxImages?: number
}>()

const emit = defineEmits<{
    imagesChange: [images: File[]]
    deleteImage: [imageId: number]
    setPrimary: [imageId: number]
}>()

const fileInput = ref<HTMLInputElement | null>(null)
const selectedFiles = ref<File[]>([])

const handleFileSelect = (event: Event) => {
    const target = event.target as HTMLInputElement
    if (target.files) {
        const files = Array.from(target.files)
        selectedFiles.value = [...selectedFiles.value, ...files]
        emit('imagesChange', selectedFiles.value)
    }
}

const removeSelectedFile = (index: number) => {
    selectedFiles.value.splice(index, 1)
    emit('imagesChange', selectedFiles.value)
}

const deleteImage = (imageId: number) => {
    if (confirm('Are you sure you want to delete this image?')) {
        emit('deleteImage', imageId)
    }
}

const setPrimaryImage = (imageId: number) => {
    emit('setPrimary', imageId)
}

const triggerFileInput = () => {
    fileInput.value?.click()
}
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle class="flex items-center space-x-2">
                <Upload class="h-5 w-5" />
                <span>Product Images</span>
            </CardTitle>
        </CardHeader>
        <CardContent class="space-y-4">
            <!-- Upload Section -->
            <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center">
                <input
                    ref="fileInput"
                    type="file"
                    multiple
                    accept="image/*"
                    @change="handleFileSelect"
                    class="hidden"
                />
                <Upload class="mx-auto h-12 w-12 text-gray-400 mb-4" />
                <p class="text-gray-600 mb-4">Click to select images or drag and drop</p>
                <Button @click="triggerFileInput" variant="outline">
                    Select Images
                </Button>
                <p class="text-xs text-gray-500 mt-2">
                    Supports: JPG, PNG, GIF (max 2MB each)
                </p>
            </div>

            <!-- Selected Files (to be uploaded) -->
            <div v-if="selectedFiles.length > 0" class="space-y-2">
                <h4 class="font-medium">Selected Files ({{ selectedFiles.length }})</h4>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div
                        v-for="(file, index) in selectedFiles"
                        :key="`file-${index}`"
                        class="relative group"
                    >
                        <img
                            :src="URL.createObjectURL(file)"
                            :alt="file.name"
                            class="w-full h-24 object-cover rounded border"
                        />
                        <button
                            @click="removeSelectedFile(index)"
                            class="absolute top-1 right-1 bg-red-500 text-white rounded-full p-1 opacity-0 group-hover:opacity-100 transition-opacity"
                        >
                            <Trash2 class="h-3 w-3" />
                        </button>
                        <p class="text-xs mt-1 truncate">{{ file.name }}</p>
                    </div>
                </div>
            </div>

            <!-- Existing Images -->
            <div v-if="images.length > 0" class="space-y-2">
                <h4 class="font-medium">Current Images ({{ images.length }})</h4>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div
                        v-for="image in images"
                        :key="image.id"
                        class="relative group"
                    >
                        <img
                            :src="image.url"
                            alt="Product image"
                            class="w-full h-24 object-cover rounded border"
                            :class="{ 'ring-2 ring-blue-500': image.is_primary }"
                        />

                        <!-- Primary badge -->
                        <div v-if="image.is_primary" class="absolute top-1 left-1">
                            <span class="bg-blue-500 text-white text-xs px-2 py-1 rounded">
                                Primary
                            </span>
                        </div>

                        <!-- Action buttons -->
                        <div class="absolute top-1 right-1 opacity-0 group-hover:opacity-100 transition-opacity space-x-1">
                            <button
                                v-if="!image.is_primary"
                                @click="setPrimaryImage(image.id)"
                                class="bg-blue-500 text-white rounded-full p-1"
                                title="Set as primary"
                            >
                                <Star class="h-3 w-3" />
                            </button>
                            <button
                                @click="deleteImage(image.id)"
                                class="bg-red-500 text-white rounded-full p-1"
                                title="Delete image"
                            >
                                <Trash2 class="h-3 w-3" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty state -->
            <div v-if="images.length === 0 && selectedFiles.length === 0" class="text-center py-4">
                <p class="text-gray-500">No images uploaded yet</p>
            </div>
        </CardContent>
    </Card>
</template>
