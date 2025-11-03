<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Separator } from '@/components/ui/separator';
import {
    FolderEdit, ArrowLeft, Upload, X, Save, Eye, Info, Trash2
} from 'lucide-vue-next';
import { ref, computed } from 'vue';
import AdminLayout from '@/layouts/AdminLayout.vue';

const { category } = defineProps({
    category: { type: Object, required: true }
});

// Form state - Pre-populate with existing data
const form = useForm({
    name: category.name || '',
    slug: category.slug || '',
    description: category.description || '',
    image: null,
    is_active: category.is_active !== undefined ? Boolean(category.is_active) : true,
    sort_order: category.sort_order || 0,
    remove_image: false,
    _method: 'PUT'
});

// State
const imagePreview = ref(category.image || null);
const originalImage = category.image;

// Computed properties
const isFormValid = computed(() => {
    return form.name && form.name.trim().length > 0;
});

const hasUnsavedChanges = computed(() => {
    return form.name !== category.name ||
        form.slug !== category.slug ||
        form.description !== (category.description || '') ||
        form.is_active !== Boolean(category.is_active) ||
        form.sort_order !== (category.sort_order || 0) ||
        form.image !== null ||
        form.remove_image === true;
});

// Helper functions
const generateSlug = (name) => {
    if (!name) return '';
    return name.toLowerCase().replace(/[^\w ]+/g, '').replace(/ +/g, '-');
};

// Auto-generate slug (only if empty)
const updateSlug = () => {
    if (form.name && !form.slug) {
        form.slug = generateSlug(form.name);
    }
};

// Image handling
const handleImageUpload = (event) => {
    const file = event.target.files?.[0];
    if (file) {
        if (file.size > 5 * 1024 * 1024) {
            alert('File size must be less than 5MB');
            return;
        }

        if (!file.type.startsWith('image/')) {
            alert('Please select an image file');
            return;
        }

        form.image = file;
        form.remove_image = false; // Reset remove flag if new image selected

        const reader = new FileReader();
        reader.onload = (e) => {
            imagePreview.value = e.target.result;
        };
        reader.readAsDataURL(file);
    }
    event.target.value = '';
};

const removeImage = () => {
    form.image = null;

    // If there's an original image, ask if they want to remove it
    if (originalImage && imagePreview.value === originalImage) {
        if (confirm('Are you sure you want to remove the current image?')) {
            imagePreview.value = null;
            form.remove_image = true;
        } else {
            imagePreview.value = originalImage; // Keep original
        }
    } else {
        // Just removing a new uploaded image
        imagePreview.value = originalImage; // Revert to original
        form.remove_image = false;
    }
};

const removeExistingImage = () => {
    if (confirm('Are you sure you want to remove the current image?')) {
        imagePreview.value = null;
        form.remove_image = true;
        form.image = null;
    }
};

// Form submission
const updateCategory = (draft = false) => {
    console.log('=== Form Submission Debug ===');
    console.log('Form is_active before submission:', form.is_active, typeof form.is_active);
    console.log('Draft mode:', draft);

    if (!isFormValid.value) {
        alert('Category name is required');
        return;
    }

    if (!form.slug && form.name) {
        form.slug = generateSlug(form.name);
    }

    // Handle draft mode
    const originalIsActive = form.is_active;
    if (draft) {
        form.is_active = false;
    }

    console.log('Form data before submission:', form.data());

    // Check if we need to use FormData (for file uploads)
    if (form.image) {
        const formData = new FormData();

        // Add all form fields explicitly
        formData.append('name', form.name);
        formData.append('slug', form.slug || '');
        formData.append('description', form.description || '');
        formData.append('sort_order', form.sort_order.toString());
        formData.append('is_active', form.is_active ? '1' : '0');
        formData.append('remove_image', form.remove_image ? '1' : '0');
        formData.append('image', form.image);
        formData.append('_method', 'PUT');

        console.log('Submitting FormData with is_active:', form.is_active ? '1' : '0');

        router.post(`/admin/categories/${category.id}`, formData, {
            forceFormData: true,
            onSuccess: () => {
                console.log('Update successful');
            },
            onError: (errors) => {
                console.error('Form submission errors:', errors);
                form.is_active = originalIsActive; // Restore original value
            },
            onFinish: () => {
                if (draft) {
                    form.is_active = originalIsActive; // Restore original value
                }
            }
        });
    } else {
        // Regular JSON submission
        const submitData = {
            name: form.name,
            slug: form.slug || '',
            description: form.description || '',
            sort_order: parseInt(form.sort_order.toString()) || 0,
            is_active: Boolean(form.is_active),
            remove_image: Boolean(form.remove_image)
        };

        console.log('Submitting JSON data:', submitData);

        router.put(`/admin/categories/${category.id}`, submitData, {
            onSuccess: () => {
                console.log('Update successful');
            },
            onError: (errors) => {
                console.error('Form submission errors:', errors);
                form.is_active = originalIsActive; // Restore original value
            },
            onFinish: () => {
                if (draft) {
                    form.is_active = originalIsActive; // Restore original value
                }
            }
        });
    }
};

const deleteCategory = () => {
    const hasProducts = category.products_count > 0;
    let confirmMessage = `Are you sure you want to delete "${category.name}"?`;

    if (hasProducts) {
        confirmMessage += `\n\nThis category has ${category.products_count} product(s). They will become uncategorized.`;
    }

    confirmMessage += '\n\nThis action cannot be undone.';

    if (confirm(confirmMessage)) {
        router.delete(`/admin/categories/${category.id}`, {
            onSuccess: () => {
                router.visit('/admin/categories');
            }
        });
    }
};

// Debug function
const logFormData = () => {
    console.log('Current form state:', {
        name: form.name,
        slug: form.slug,
        description: form.description,
        is_active: form.is_active,
        sort_order: form.sort_order,
        has_image: !!form.image,
        remove_image: form.remove_image
    });
};
</script>

<template>
    <AdminLayout
        title="Orders Management"
        :breadcrumbs="breadcrumbs"
    >
    <div class="min-h-screen bg-muted/20">
        <Head :title="`Edit ${category.name}`" />

        <!-- Header -->
        <div class="border-b bg-background">
            <div class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-16">
                    <div class="flex items-center space-x-4">
                        <Button variant="ghost" as="a" href="/admin/categories" class="p-2">
                            <ArrowLeft class="h-5 w-5" />
                        </Button>
                        <div class="flex items-center space-x-3">
                            <div class="p-2 bg-blue-100 rounded-lg">
                                <FolderEdit class="h-6 w-6 text-blue-600" />
                            </div>
                            <div>
                                <h1 class="text-2xl font-bold text-foreground">Edit Category</h1>
                                <p class="text-sm text-muted-foreground">
                                    Update category information
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center space-x-3">
                        <Button variant="outline" as="a" :href="`/products?category=${category.slug}`" target="_blank">
                            <Eye class="h-4 w-4 mr-2" />
                            View Category
                        </Button>
                        <Button
                            variant="outline"
                            @click="updateCategory(true)"
                            :disabled="form.processing || !hasUnsavedChanges"
                        >
                            Save as Draft
                        </Button>
                        <Button
                            @click="updateCategory(false)"
                            :disabled="form.processing || !isFormValid || !hasUnsavedChanges"
                        >
                            <Save class="h-4 w-4 mr-2" />
                            {{ form.processing ? 'Updating...' : 'Update Category' }}
                        </Button>
                        <Button variant="destructive" @click="deleteCategory">
                            <Trash2 class="h-4 w-4 mr-2" />
                            Delete
                        </Button>
                    </div>
                </div>
            </div>
        </div>

        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

            <!-- Status Alert -->
            <Card v-if="!category.is_active" class="mb-6 border-orange-200 bg-orange-50">
                <CardContent class="p-4">
                    <div class="flex items-center space-x-2">
                        <Info class="h-4 w-4 text-orange-600" />
                        <p class="text-sm text-orange-700 font-medium">
                            This category is currently inactive and not visible to customers.
                        </p>
                    </div>
                </CardContent>
            </Card>

            <!-- Unsaved Changes Alert -->
            <Card v-if="hasUnsavedChanges" class="mb-6 border-blue-200 bg-blue-50">
                <CardContent class="p-4">
                    <div class="flex items-center space-x-2">
                        <Info class="h-4 w-4 text-blue-600" />
                        <p class="text-sm text-blue-700 font-medium">
                            You have unsaved changes. Don't forget to update the category.
                        </p>
                    </div>
                </CardContent>
            </Card>



            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

                <!-- Main Form -->
                <div class="lg:col-span-2 space-y-8">

                    <!-- Basic Information -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Basic Information</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-6">

                            <!-- Category Name -->
                            <div class="space-y-2">
                                <Label for="name">
                                    Category Name <span class="text-destructive">*</span>
                                </Label>
                                <Input
                                    id="name"
                                    v-model="form.name"
                                    @input="updateSlug"
                                    placeholder="Electronics, Clothing, Books..."
                                    class="text-lg"
                                    :class="{ 'border-destructive': form.errors.name }"
                                />
                                <p v-if="form.errors.name" class="text-sm text-destructive">
                                    {{ form.errors.name }}
                                </p>
                            </div>

                            <!-- Slug -->
                            <div class="space-y-2">
                                <Label for="slug">Category URL Slug</Label>
                                <div class="flex">
                                    <span class="inline-flex items-center px-3 rounded-l-md border border-r-0 border-input bg-muted text-sm text-muted-foreground">
                                        /categories/
                                    </span>
                                    <Input
                                        id="slug"
                                        v-model="form.slug"
                                        :placeholder="generateSlug(form.name) || 'category-slug'"
                                        class="rounded-l-none"
                                    />
                                </div>
                                <p class="text-xs text-muted-foreground">
                                    Preview: /categories/{{ form.slug || generateSlug(form.name) || 'category-slug' }}
                                </p>
                                <p v-if="form.errors.slug" class="text-sm text-destructive">
                                    {{ form.errors.slug }}
                                </p>
                            </div>

                            <!-- Description -->
                            <div class="space-y-2">
                                <Label for="description">Description</Label>
                                <textarea
                                    id="description"
                                    v-model="form.description"
                                    placeholder="Describe this category and what products it contains..."
                                    rows="4"
                                    maxlength="500"
                                    class="flex min-h-[100px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                                ></textarea>
                                <p class="text-xs text-muted-foreground">
                                    {{ form.description.length }}/500 characters
                                </p>
                            </div>

                        </CardContent>
                    </Card>


                </div>

                <!-- Sidebar -->
                <div class="lg:col-span-1 space-y-6">

                    <!-- Publishing Options -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Publishing</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">

                            <!-- Debug checkbox state -->

                            <div class="flex items-center space-x-2">
                                <input
                                    type="checkbox"
                                    :checked="form.is_active"
                                    @change="(event) => {
                    form.is_active = event.target.checked;
                    console.log('Checkbox changed to:', event.target.checked, 'Form is_active now:', form.is_active);
                }"
                                    id="is_active"
                                    class="rounded border-border"
                                />
                                <Label
                                    for="is_active"
                                    class="cursor-pointer"
                                >
                                    Active (Visible to customers)
                                </Label>
                            </div>

                            <!-- Alternative: Test with a button toggle -->

                            <div v-if="!form.is_active" class="p-3 bg-orange-50 border border-orange-200 rounded-lg">
                                <div class="flex items-start space-x-2">
                                    <Info class="h-4 w-4 text-orange-600 mt-0.5" />
                                    <div class="text-sm text-orange-700">
                                        <p class="font-medium">Draft Mode</p>
                                        <p class="text-xs">This category will not be visible to customers</p>
                                    </div>
                                </div>
                            </div>

                        </CardContent>
                    </Card>

                    <!-- Organization -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Organization</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">

                            <div class="space-y-2">
                                <Label for="sort_order">Sort Order</Label>
                                <Input
                                    id="sort_order"
                                    v-model="form.sort_order"
                                    type="number"
                                    min="0"
                                    placeholder="0"
                                />
                                <p class="text-xs text-muted-foreground">
                                    Lower numbers appear first in category listings
                                </p>
                            </div>

                        </CardContent>
                    </Card>

                    <!-- Category Stats -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="text-lg">Category Stats</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-3">
                            <div class="space-y-3 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-muted-foreground">Products:</span>
                                    <span class="font-medium">{{ category.products_count || 0 }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-muted-foreground">Created:</span>
                                    <span>{{ new Date(category.created_at).toLocaleDateString() }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-muted-foreground">Updated:</span>
                                    <span>{{ new Date(category.updated_at).toLocaleDateString() }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-muted-foreground">Status:</span>
                                    <span :class="category.is_active ? 'text-green-600' : 'text-red-600'">
                                        {{ category.is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </div>
                            </div>

                            <Separator />

                            <div class="space-y-2">
                                <Button
                                    variant="outline"
                                    size="sm"
                                    class="w-full"
                                    as="a"
                                    :href="`/products?category=${category.slug}`"
                                    target="_blank"
                                >
                                    <Eye class="h-4 w-4 mr-2" />
                                    View Category Page
                                </Button>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </div>
    </AdminLayout>
</template>
