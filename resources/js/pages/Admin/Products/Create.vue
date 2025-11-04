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
import { Package, ArrowLeft } from 'lucide-vue-next';
import { ref, computed, watch } from 'vue';

const { categories } = defineProps({
    categories: { type: Array, required: true },
});

// Form data
const form = useForm({
    name: '',
    description: '',
    price: 0,
    stock: 0,
    category_id: '',
    is_active: true,
    is_donatable: false,
    image: null,

    // Variant data
    colors: [],
    sizes: [],
    variants: [],
});

// State for variants
const colors = ref([]);
const sizes = ref([]);
const variants = ref([]);
const hasVariants = computed(() => colors.value.length > 0 && sizes.value.length > 0);

// Header actions
const headerActions = [
    {
        label: 'Back to Products',
        href: '/admin/products',
        icon: ArrowLeft,
        variant: 'outline'
    }
];

// Watch for colors/sizes changes to regenerate variants
watch([colors, sizes], () => {
    if (hasVariants.value) {
        generateVariants();
    }
}, { deep: true });

// Generate all color+size combinations
const generateVariants = () => {
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
                    color_hex: color.hex,
                    size_name: size.name,
                    stock: 0,
                    price_adjustment: 0,
                    is_active: true,
                });
            }
        });
    });

    // Keep existing variants and add new ones
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

// Submit form - ENHANCED WITH BETTER ERROR HANDLING
const submitForm = () => {
    // Clear previous errors
    form.clearErrors();

    // If no variants, use base stock
    if (!hasVariants.value) {
        form.variants = [];
    }

    // Enhanced logging
    console.log('Creating product with data:', {
        name: form.name,
        price: form.price,
        stock: form.stock,
        category_id: form.category_id,
        has_variants: hasVariants.value,
        variants_count: form.variants.length
    });

    form.post('/admin/products', {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            console.log('Product created successfully');
        },
        onError: (errors) => {
            console.log('Product creation errors:', errors);
            // Scroll to top to show errors
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    });
};
</script>

<template>
    <AdminLayout title="Create Product">
        <Head title="Create Product" />

        <PageHeader
            title="Create New Product"
            description="Add a new product with colors and sizes"
            :icon="Package"
            icon-color="text-primary"
            :actions="headerActions"
        />

        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">



            <form @submit.prevent="submitForm" class="space-y-8">

                <!-- Basic Product Information -->
                <Card>
                    <CardHeader>
                        <CardTitle>Basic Product Information</CardTitle>
                        <!-- ADD: Error indicator for this section -->
                        <div v-if="form.errors.name || form.errors.price || form.errors.stock || form.errors.category_id || form.errors.description || form.errors.image"
                             class="text-red-600 text-sm flex items-center mt-1">
                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                            Please check the fields below for errors
                        </div>
                    </CardHeader>
                    <CardContent>
                        <BasicProductForm
                            :form="form"
                            :categories="categories"
                        />
                    </CardContent>
                </Card>

                <!-- Colors & Sizes Management -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                    <!-- Colors -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Colors</CardTitle>
                            <!-- ADD: Show color count -->
                            <p class="text-sm text-muted-foreground">
                                {{ colors.length > 0 ? `${colors.length} colors selected` : 'No colors selected - optional' }}
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
                            <!-- ADD: Show size count -->
                            <p class="text-sm text-muted-foreground">
                                {{ sizes.length > 0 ? `${sizes.length} sizes selected` : 'No sizes selected - optional' }}
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

                <!-- Variants Matrix (only show if has colors and sizes) -->
                <Card v-if="hasVariants">
                    <CardHeader>
                        <CardTitle>Product Variants</CardTitle>
                        <p class="text-sm text-muted-foreground">
                            Configure stock and pricing for each color and size combination ({{ variants.length }} variants)
                        </p>
                        <!-- ADD: Variant error indicator -->
                        <div v-if="form.errors.variants" class="text-red-600 text-sm flex items-center mt-1">
                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                            {{ form.errors.variants }}
                        </div>
                    </CardHeader>
                    <CardContent>
                        <VariantMatrix
                            :variants="variants"
                            :base-price="form.price"
                            @update:variants="handleVariantUpdate"
                        />
                    </CardContent>
                </Card>

                <!-- ADD: No Variants Info Card -->
                <Card v-else-if="colors.length === 0 && sizes.length === 0">
                    <CardContent class="text-center py-8">
                        <div class="text-gray-500">
                            <Package class="mx-auto h-12 w-12 mb-4 text-muted-foreground" />
                            <h3 class="text-lg font-medium mb-2">Single Product</h3>
                            <p class="text-sm">This product will be created without variants. The base price and stock will be used.</p>
                        </div>
                    </CardContent>
                </Card>

                <!-- Form Actions -->
                <div class="flex justify-end space-x-4 pt-6">
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
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Creating...
                        </div>
                        <span v-else>Create Product</span>
                    </Button>
                </div>

            </form>

        </div>
    </AdminLayout>
</template>
