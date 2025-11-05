<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import BasicProductForm from '@/components/admin/products/BasicProductForm.vue';
import ColorManager from '@/components/admin/products/ColorManager.vue';
import SizeManager from '@/components/admin/products/SizeManager.vue';
import VariantMatrix from '@/components/admin/products/VariantMatrix.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Package, ArrowLeft, Eye } from 'lucide-vue-next';
import { ref, computed, watch } from 'vue';

const { product, categories, existingVariants } = defineProps({
    product: { type: Object, required: true },
    categories: { type: Array, required: true },
    existingVariants: { type: Array, default: () => [] },
});

// Form data - ENHANCED with multiple images support
const form = useForm({
    name: product.name,
    description: product.description,
    price: product.price,
    stock: product.stock,
    category_id: product.category_id,
    is_active: product.is_active,
    is_donatable: product.is_donatable,
    image: null,
    // NEW: Multiple images support
    images: [],
    delete_images: [],
    primary_image_id: null,

    // Variant data
    colors: [],
    sizes: [],
    variants: [],
});

// State for variants
const colors = ref(product.available_colors || []);
const sizes = ref(product.available_sizes || []);
const variants = ref(existingVariants.map(v => ({
    id: v.id,
    color_id: v.color_id,
    size_id: v.size_id,
    color_name: v.color?.name || 'Unknown',
    color_hex: v.color?.hex_code || '#000000',
    size_name: v.size?.name || 'Unknown',
    stock: v.stock,
    price_adjustment: v.price_adjustment,
    is_active: v.is_active,
    sku: v.sku,
})));

const hasVariants = computed(() => variants.value.length > 0);
const hasExistingVariants = computed(() => existingVariants.length > 0);

// Header actions
const headerActions = [
    {
        label: 'Back to Products',
        href: '/admin/products',
        icon: ArrowLeft,
        variant: 'outline'
    },
    {
        label: 'View Product',
        href: `/products/${product.slug}`,
        icon: Eye,
        variant: 'outline'
    }
];

// Watch for colors/sizes changes to regenerate variants
watch([colors, sizes], () => {
    if (colors.value.length > 0 && sizes.value.length > 0) {
        generateNewVariants();
    }
}, { deep: true });

// Generate new variants from colors and sizes
const generateNewVariants = () => {
    const newVariants = [];

    colors.value.forEach(color => {
        sizes.value.forEach(size => {
            // Check if variant already exists
            const exists = variants.value.find(v =>
                v.color_id === color.id && v.size_id === size.id
            );

            if (!exists) {
                newVariants.push({
                    color_id: color.id,
                    size_id: size.id,
                    color_name: color.name,
                    color_hex: color.hex_code || color.hex,
                    size_name: size.name,
                    stock: 0,
                    price_adjustment: 0,
                    is_active: true,
                });
            }
        });
    });

    // Add new variants to existing ones
    variants.value = [...variants.value, ...newVariants];

    // Remove variants for deleted colors/sizes
    variants.value = variants.value.filter(variant =>
        colors.value.some(c => c.id === variant.color_id) &&
        sizes.value.some(s => s.id === variant.size_id)
    );
};

// Handle color management
const handleColorsChange = (newColors) => {
    colors.value = newColors;
    form.colors = newColors;
};

// Handle size management
const handleSizesChange = (newSizes) => {
    sizes.value = newSizes;
    form.sizes = newSizes;
};

// Handle variant updates
const handleVariantUpdate = (updatedVariants) => {
    variants.value = updatedVariants;
    form.variants = updatedVariants;
};

// NEW: Handle multiple images
const handleImagesChange = (images: File[]) => {
    form.images = images;
    console.log('New images added to form:', images.length, images);
};

// NEW: Handle image deletion
const handleDeleteImage = (imageId: number) => {
    if (!form.delete_images) {
        form.delete_images = [];
    }
    form.delete_images.push(imageId);
    console.log('Image marked for deletion:', imageId);
};

// NEW: Handle primary image setting
const handleSetPrimary = (imageId: number) => {
    form.primary_image_id = imageId;
    console.log('Primary image set to:', imageId);
};

// Submit form - ENHANCED with image support
const submitForm = () => {
    console.log('Form data before submit:', {
        name: form.name,
        price: form.price,
        stock: form.stock,
        category_id: form.category_id,
        is_active: form.is_active,
        is_donatable: form.is_donatable,
        // NEW: Image logging
        has_single_image: !!form.image,
        has_multiple_images: form.images && form.images.length > 0,
        images_count: form.images?.length || 0,
        delete_images_count: form.delete_images?.length || 0,
        primary_image_id: form.primary_image_id
    });

    // FIXED: Use POST with _method for file uploads
    form.transform((data) => ({
        ...data,
        _method: 'PATCH',
        is_active: data.is_active ? 1 : 0,
        is_donatable: data.is_donatable ? 1 : 0,
    })).post(`/admin/products/${product.id}`, {
        forceFormData: true,  // CRITICAL for file uploads!
        preserveScroll: true,
        onSuccess: () => {
            console.log('Update successful');
        },
        onError: (errors) => {
            console.log('Update errors:', errors);
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    });
};

// Delete product
const deleteProduct = () => {
    if (confirm('Are you sure you want to delete this product? This action cannot be undone.')) {
        form.delete(`/admin/products/${product.id}`);
    }
};
</script>

<template>
    <AdminLayout title="Edit Product">
        <Head :title="`Edit ${product.name}`" />

        <PageHeader
            :title="`Edit Product: ${product.name}`"
            description="Update product information and manage variants"
            :icon="Package"
            icon-color="text-primary"
            :actions="headerActions"
        />

        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

            <form @submit.prevent="submitForm" class="space-y-8">

                <!-- Basic Product Information -->
                <Card>
                    <CardHeader>
                        <div class="flex items-center justify-between">
                            <div>
                                <CardTitle>Basic Product Information</CardTitle>
                                <div class="flex items-center gap-2 mt-2">
                                    <Badge :variant="product.is_active ? 'secondary' : 'destructive'">
                                        {{ product.is_active ? 'Active' : 'Inactive' }}
                                    </Badge>
                                    <Badge v-if="product.is_donatable" variant="primary">
                                        Donatable
                                    </Badge>
                                    <!-- NEW: Show current images count -->
                                    <Badge v-if="product.images && product.images.length > 0" variant="outline">
                                        {{ product.images.length }} Images
                                    </Badge>
                                </div>
                            </div>

                            <!-- Current Product Image -->
                            <div v-if="product.image_url || product.image" class="shrink-0">
                                <img
                                    :src="product.image_url || product.image"
                                    :alt="product.name"
                                    class="w-20 h-20 object-cover rounded-lg border"
                                />
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <!-- ENHANCED: Pass existing images and handlers -->
                        <BasicProductForm
                            :form="form"
                            :categories="categories"
                            :images="product.images || []"
                            @images-change="handleImagesChange"
                            @delete-image="handleDeleteImage"
                            @set-primary-image="handleSetPrimary"
                        />
                    </CardContent>
                </Card>

                <!-- Existing Variants Info -->
                <Card v-if="hasExistingVariants">
                    <CardHeader>
                        <CardTitle>Current Variants</CardTitle>
                        <p class="text-sm text-muted-foreground">
                            This product has {{ existingVariants.length }} existing variants.
                            You can modify them below or add new color/size combinations.
                        </p>
                    </CardHeader>
                </Card>

                <!-- Colors & Sizes Management -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                    <!-- Colors -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Colors</CardTitle>
                            <p class="text-sm text-muted-foreground">
                                {{ colors.length > 0 ? `${colors.length} colors selected` : 'No colors selected' }}
                            </p>
                        </CardHeader>
                        <CardContent>
                            <ColorManager
                                :colors="colors"
                                @update:colors="handleColorsChange"
                            />
                        </CardContent>
                    </Card>

                    <!-- Sizes -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Sizes</CardTitle>
                            <p class="text-sm text-muted-foreground">
                                {{ sizes.length > 0 ? `${sizes.length} sizes selected` : 'No sizes selected' }}
                            </p>
                        </CardHeader>
                        <CardContent>
                            <SizeManager
                                :sizes="sizes"
                                @update:sizes="handleSizesChange"
                            />
                        </CardContent>
                    </Card>

                </div>

                <!-- Variants Matrix -->
                <Card v-if="hasVariants || hasExistingVariants">
                    <CardHeader>
                        <CardTitle>Product Variants Management</CardTitle>
                        <p class="text-sm text-muted-foreground">
                            Configure stock, pricing, and availability for each variant
                        </p>
                    </CardHeader>
                    <CardContent>
                        <VariantMatrix
                            :variants="variants"
                            :base-price="form.price"
                            @update:variants="handleVariantUpdate"
                        />
                    </CardContent>
                </Card>

                <!-- Form Actions -->
                <div class="flex justify-between items-center pt-6">
                    <Button
                        type="button"
                        variant="destructive"
                        @click="deleteProduct"
                        :disabled="form.processing"
                    >
                        Delete Product
                    </Button>

                    <div class="flex space-x-4">
                        <Button
                            type="button"
                            variant="outline"
                            :disabled="form.processing"
                            @click="$inertia.visit('/admin/products')"
                        >
                            Cancel
                        </Button>

                        <Button
                            type="submit"
                            :disabled="form.processing"
                            class="min-w-[120px]"
                        >
                            <div v-if="form.processing" class="flex items-center">
                                <svg class="animate-spin -ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 714 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Updating...
                            </div>
                            <span v-else>Update Product</span>
                        </Button>
                    </div>
                </div>

            </form>

        </div>
    </AdminLayout>
</template>
