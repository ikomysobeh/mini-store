<script setup lang="ts">
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Upload, Trash2, Star, Image, X } from 'lucide-vue-next';
import { ref, computed } from 'vue';

interface ProductImage {
    id: number;
    url: string;
    path: string;
    is_primary: boolean;
    sort_order: number;
}

const { form, categories, images } = defineProps<{
    form: {
        type: Object,
        required: true
    },
    categories: {
        type: Array,
        required: true
    },
    images?: ProductImage[] // For edit mode
}>();

const emit = defineEmits<{
    imagesChange: [images: File[]]
    deleteImage: [imageId: number]
    setPrimaryImage: [imageId: number]
}>();

// State for multiple images
const selectedFiles = ref<File[]>([]);
const fileInput = ref<HTMLInputElement | null>(null);
const dragOver = ref(false);

// FIXED: Handle file selection and add to form
const handleFileSelect = (event: Event) => {
    const target = event.target as HTMLInputElement;
    if (target.files) {
        const files = Array.from(target.files);
        selectedFiles.value = [...selectedFiles.value, ...files];

        // IMPORTANT: Add files to form data for submission
        form.images = selectedFiles.value;

        emit('imagesChange', selectedFiles.value);
    }
};

// Remove selected file before upload
const removeSelectedFile = (index: number) => {
    selectedFiles.value.splice(index, 1);

    // Update form data
    form.images = selectedFiles.value;

    emit('imagesChange', selectedFiles.value);
};

// Delete existing image
const deleteImage = (imageId: number) => {
    if (confirm('Are you sure you want to delete this image?')) {
        emit('deleteImage', imageId);
    }
};

// Set primary image
const setPrimaryImage = (imageId: number) => {
    emit('setPrimaryImage', imageId);
};

// Prevent form submission when clicking select images
const triggerFileInput = (event: Event) => {
    event.preventDefault();
    event.stopPropagation();
    fileInput.value?.click();
};

// FIXED: Create object URL safely
const createImageURL = (file: File): string => {
    try {
        return window.URL.createObjectURL(file);
    } catch (error) {
        console.error('Failed to create object URL:', error);
        return '';
    }
};

// Drag and drop handlers
const handleDrop = (event: DragEvent) => {
    event.preventDefault();
    dragOver.value = false;

    const files = event.dataTransfer?.files;
    if (files) {
        const imageFiles = Array.from(files).filter(file => file.type.startsWith('image/'));
        selectedFiles.value = [...selectedFiles.value, ...imageFiles];

        // Update form data
        form.images = selectedFiles.value;

        emit('imagesChange', selectedFiles.value);
    }
};

const handleDragOver = (event: DragEvent) => {
    event.preventDefault();
    dragOver.value = true;
};

const handleDragLeave = (event: DragEvent) => {
    event.preventDefault();
    dragOver.value = false;
};

// Computed for existing images
const existingImages = computed(() => images || []);
const hasImages = computed(() => existingImages.value.length > 0 || selectedFiles.value.length > 0);
</script>

<template>
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- Left Column -->
        <div class="space-y-6">

            <!-- Product Name - English -->
            <div>
                <Label for="name_en">Product Name (English) *</Label>
                <Input
                    id="name_en"
                    v-model="form.name_en"
                    type="text"
                    class="mt-1"
                    placeholder="Enter product name in English"
                />
                <div v-if="form.errors?.name_en" class="text-red-500 text-sm mt-1">
                    {{ form.errors.name_en }}
                </div>
            </div>

            <!-- Product Name - Arabic -->
            <div>
                <Label for="name_ar">Product Name (Arabic) - اسم المنتج *</Label>
                <Input
                    id="name_ar"
                    v-model="form.name_ar"
                    type="text"
                    class="mt-1"
                    dir="rtl"
                    placeholder="أدخل اسم المنتج بالعربية"
                />
                <div v-if="form.errors?.name_ar" class="text-red-500 text-sm mt-1">
                    {{ form.errors.name_ar }}
                </div>
                <p class="text-xs text-gray-500 mt-1">At least one language (English or Arabic) is required</p>
            </div>

            <!-- Description - English -->
            <div>
                <Label for="description_en">Description (English)</Label>
                <Textarea
                    id="description_en"
                    v-model="form.description_en"
                    rows="4"
                    class="mt-1"
                    placeholder="Product description in English..."
                />
                <div v-if="form.errors?.description_en" class="text-red-500 text-sm mt-1">
                    {{ form.errors.description_en }}
                </div>
            </div>

            <!-- Description - Arabic -->
            <div>
                <Label for="description_ar">Description (Arabic) - الوصف</Label>
                <Textarea
                    id="description_ar"
                    v-model="form.description_ar"
                    rows="4"
                    class="mt-1"
                    dir="rtl"
                    placeholder="وصف المنتج بالعربية..."
                />
                <div v-if="form.errors?.description_ar" class="text-red-500 text-sm mt-1">
                    {{ form.errors.description_ar }}
                </div>
                <p class="text-xs text-gray-500 mt-1">At least one language (English or Arabic) is required</p>
            </div>

            <!-- Price and Stock -->
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <Label for="price">Base Price ($) *</Label>
                    <Input
                        id="price"
                        v-model.number="form.price"
                        type="number"
                        step="0.01"
                        min="0"
                        required
                        class="mt-1"
                        placeholder="0.00"
                    />
                    <div v-if="form.errors?.price" class="text-red-500 text-sm mt-1">
                        {{ form.errors.price }}
                    </div>
                </div>
                <div>
                    <Label for="stock">Base Stock *</Label>
                    <Input
                        id="stock"
                        v-model.number="form.stock"
                        type="number"
                        min="0"
                        required
                        class="mt-1"
                        placeholder="0"
                    />
                    <small class="text-gray-500 block mt-1">Used when no variants are created</small>
                    <div v-if="form.errors?.stock" class="text-red-500 text-sm mt-1">
                        {{ form.errors.stock }}
                    </div>
                </div>
            </div>

            <!-- Category -->
            <div>
                <Label for="category_id">Category *</Label>
                <select
                    id="category_id"
                    v-model="form.category_id"
                    required
                    class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm mt-1"
                >
                    <option value="">Select a category</option>
                    <option v-for="category in categories" :key="category.id" :value="category.id">
                        {{ category.name }}
                    </option>
                </select>
                <div v-if="form.errors?.category_id" class="text-red-500 text-sm mt-1">
                    {{ form.errors.category_id }}
                </div>
            </div>

            <!-- Checkboxes -->
            <div class="space-y-4">
                <div class="flex items-center space-x-2">
                    <input
                        id="is_active"
                        v-model="form.is_active"
                        type="checkbox"
                        class="rounded border-gray-300"
                    />
                    <Label for="is_active">Active Product</Label>
                </div>

                <div class="flex items-center space-x-2">
                    <input
                        id="is_donatable"
                        v-model="form.is_donatable"
                        type="checkbox"
                        class="rounded border-gray-300"
                    />
                    <Label for="is_donatable">Available for Donation</Label>
                </div>
            </div>

        </div>

        <!-- Right Column -->
        <div class="space-y-6">

            <!-- Multiple Images Upload Section -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center space-x-2">
                        <Image class="h-5 w-5" />
                        <span>Product Images</span>
                    </CardTitle>
                </CardHeader>
                <CardContent class="space-y-4">

                    <!-- Upload Area -->
                    <div
                        :class="[
                            'border-2 border-dashed rounded-lg p-6 text-center transition-colors',
                            dragOver ? 'border-blue-500 bg-blue-50' : 'border-gray-300'
                        ]"
                        @drop="handleDrop"
                        @dragover="handleDragOver"
                        @dragleave="handleDragLeave"
                    >
                        <!-- Hidden file input -->
                        <input
                            ref="fileInput"
                            type="file"
                            multiple
                            accept="image/*"
                            @change="handleFileSelect"
                            class="hidden"
                        />

                        <Upload class="mx-auto h-12 w-12 text-gray-400 mb-4" />
                        <p class="text-gray-600 mb-4">
                            Drag images here or click the button below
                        </p>

                        <!-- Select Images Button -->
                        <button
                            type="button"
                            @click="triggerFileInput"
                            class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        >
                            <Upload class="h-4 w-4 mr-2" />
                            Select Images
                        </button>

                        <p class="text-xs text-gray-500 mt-2">
                            Supports: JPG, PNG, GIF (max 2MB each)
                        </p>
                    </div>

                    <!-- Selected Files (to be uploaded) -->
                    <div v-if="selectedFiles.length > 0" class="space-y-2">
                        <h4 class="font-medium text-sm">Selected Images ({{ selectedFiles.length }})</h4>
                        <div class="grid grid-cols-3 gap-2">
                            <div
                                v-for="(file, index) in selectedFiles"
                                :key="`file-${index}`"
                                class="relative group"
                            >
                                <!-- FIXED: Use window.URL properly -->
                                <img
                                    v-if="createImageURL(file)"
                                    :src="createImageURL(file)"
                                    :alt="file.name"
                                    class="w-full h-20 object-cover rounded border"
                                />
                                <div v-else class="w-full h-20 bg-gray-200 rounded border flex items-center justify-center">
                                    <span class="text-xs text-gray-500">Loading...</span>
                                </div>

                                <button
                                    type="button"
                                    @click="removeSelectedFile(index)"
                                    class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full p-1 opacity-0 group-hover:opacity-100 transition-opacity"
                                >
                                    <X class="h-3 w-3" />
                                </button>
                                <p class="text-xs mt-1 truncate">{{ file.name }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Existing Images (in edit mode) -->
                    <div v-if="existingImages.length > 0" class="space-y-2">
                        <h4 class="font-medium text-sm">Current Images ({{ existingImages.length }})</h4>
                        <div class="grid grid-cols-3 gap-2">
                            <div
                                v-for="image in existingImages"
                                :key="image.id"
                                class="relative group"
                            >
                                <img
                                    :src="image.url"
                                    alt="Product image"
                                    class="w-full h-20 object-cover rounded border"
                                    :class="{ 'ring-2 ring-blue-500': image.is_primary }"
                                />

                                <!-- Primary badge -->
                                <div v-if="image.is_primary" class="absolute top-1 left-1">
                                    <span class="bg-blue-500 text-white text-xs px-1 py-0.5 rounded text-[10px]">
                                        Primary
                                    </span>
                                </div>

                                <!-- Action buttons -->
                                <div class="absolute -top-2 -right-2 opacity-0 group-hover:opacity-100 transition-opacity flex space-x-1">
                                    <button
                                        v-if="!image.is_primary"
                                        type="button"
                                        @click="setPrimaryImage(image.id)"
                                        class="bg-blue-500 text-white rounded-full p-1"
                                        title="Set as primary"
                                    >
                                        <Star class="h-3 w-3" />
                                    </button>
                                    <button
                                        type="button"
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
                    <div v-if="!hasImages" class="text-center py-8 text-gray-500">
                        <Image class="mx-auto h-12 w-12 text-gray-300 mb-2" />
                        <p class="text-sm">No images selected</p>
                        <p class="text-xs">Use the upload area above to add product images</p>
                    </div>

                    <!-- Image upload errors -->
                    <div v-if="form.errors?.images" class="text-red-500 text-sm">
                        {{ form.errors.images }}
                    </div>

                </CardContent>
            </Card>

            <!-- Single Image Upload (Simple Backup) -->
            <Card>
                <CardHeader>
                    <CardTitle class="text-sm">Single Image Upload</CardTitle>
                </CardHeader>
                <CardContent>
                    <Label for="image">Upload Single Image</Label>
                    <Input
                        id="image"
                        type="file"
                        accept="image/*"
                        @change="(e) => form.image = e.target.files[0]"
                        class="mt-1"
                    />
                    <p class="text-xs text-gray-500 mt-1">
                        Use this for a quick single image upload
                    </p>
                    <div v-if="form.errors?.image" class="text-red-500 text-sm mt-1">
                        {{ form.errors.image }}
                    </div>
                </CardContent>
            </Card>

        </div>
    </div>
</template>
