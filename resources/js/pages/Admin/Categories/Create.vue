<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3';
import BasicInformationForm from '@/components/admin/BasicInformationForm.vue';
import ImageUpload from '@/components/admin/ImageUpload.vue';
import PublishingOptions from '@/components/admin/PublishingOptions.vue';
import OrganizationSettings from '@/components/admin/OrganizationSettings.vue';
import LivePreview from '@/components/admin/LivePreview.vue';
import PageHeader from '@/components/admin/PageHeader.vue'; // Your existing component!
import { FolderPlus, Save, Package, UserPlus } from 'lucide-vue-next';
import { ref, computed } from 'vue';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Button } from '@/components/ui/button';

// Breadcrumbs
const breadcrumbs = [
    { label: 'Categories', href: '/admin/categories' },
    { label: 'Create Category', isActive: true }
];

// Form state
const form = useForm({
    name: '',
    slug: '',
    description: '',
    image: null,
    is_active: true,
    sort_order: 0
});

// State
const imagePreview = ref<string | null>(null);

// Computed properties
const isFormValid = computed(() => {
    return form.name && form.name.trim().length > 0;
});

// Header configuration using your existing props structure
const headerActions = computed(() => [
    {
        label: 'Save as Draft',
        variant: 'outline',
        icon: FolderPlus,
        disabled: form.processing || !isFormValid.value,
        loading: form.processing,
        onClick: saveDraft
    },
    {
        label: 'Save & Publish',
        variant: 'default',
        icon: Save,
        disabled: form.processing || !isFormValid.value,
        loading: form.processing,
        onClick: saveAndPublish
    }
]);

const statusIndicators = computed(() => [
    {
        label: isFormValid.value ? 'Form is valid' : 'Category name is required',
        color: isFormValid.value ? 'green' as const : 'red' as const,
        active: true
    },
    {
        label: form.is_active ? 'Will be published' : 'Will be saved as draft',
        color: form.is_active ? 'blue' as const : 'gray' as const,
        active: true
    }
]);

// Event handlers
const handleNameUpdate = (value: string) => {
    form.name = value;
};

const handleSlugUpdate = (value: string) => {
    form.slug = value;
};

const handleDescriptionUpdate = (value: string) => {
    form.description = value;
};

const handleActiveUpdate = (value: boolean) => {
    form.is_active = value;
};

const handleSortOrderUpdate = (value: number) => {
    form.sort_order = value;
};

const handleImageSelected = (file: File) => {
    form.image = file;
    const reader = new FileReader();
    reader.onload = (e) => {
        imagePreview.value = e.target?.result as string;
    };
    reader.readAsDataURL(file);
};

const handleImageRemoved = () => {
    form.image = null;
    imagePreview.value = null;
};

// Form submission methods
const saveCategory = (draft = false) => {
    if (!isFormValid.value) {
        alert('Category name is required');
        return;
    }

    // Auto-generate slug if not provided
    if (!form.slug && form.name) {
        form.slug = form.name.toLowerCase().replace(/[^\w ]+/g, '').replace(/ +/g, '-');
    }

    const formData = { ...form.data() };
    if (draft) {
        formData.is_active = false;
    }

    if (form.image) {
        const formDataObj = new FormData();
        Object.keys(formData).forEach(key => {
            if (key === 'image' && form.image) {
                formDataObj.append('image', form.image);
            } else if (key === 'is_active') {
                formDataObj.append(key, (draft ? false : form[key]) ? '1' : '0');
            } else if (formData[key] !== null && formData[key] !== '') {
                formDataObj.append(key, formData[key].toString());
            }
        });

        router.post('/admin/categories', formDataObj, {
            forceFormData: true,
            onSuccess: () => {
                // Redirect handled by backend
            },
            onError: (errors) => {
                console.error('Form submission errors:', errors);
            }
        });
    } else {
        if (draft) {
            form.is_active = false;
        }

        form.post('/admin/categories', {
            onSuccess: () => {
                // Redirect handled by backend
            },
            onError: (errors) => {
                console.error('Form submission errors:', errors);
            }
        });
    }
};

const saveDraft = () => {
    saveCategory(true);
};

const saveAndPublish = () => {
    saveCategory(false);
};

const goBack = () => {
    router.get('/admin/categories');
};
</script>

<template>
    <AdminLayout
        title="Create Category"
        :breadcrumbs="breadcrumbs"
    >
        <Head title="Create Category" />

        <!-- Using Your Existing PageHeader Component -->
        <PageHeader
            title="Create New Category"
            description="Add a new category to organize your products"
            :icon="Package"
            icon-color="text-primary"
            :actions="headerActions"
        />
        <!-- Main Content -->
        <div class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 bg-background text-foreground">

                <!-- Main Form Column -->
                <div class="lg:col-span-2 space-y-8">

                    <!-- Basic Information -->
                    <BasicInformationForm
                        :name="form.name"
                        :slug="form.slug"
                        :description="form.description"
                        :errors="form.errors"
                        :auto-generate-slug="true"
                        :max-description-length="500"
                        @update:name="handleNameUpdate"
                        @update:slug="handleSlugUpdate"
                        @update:description="handleDescriptionUpdate"
                    />


                </div>

                <!-- Sidebar Column -->
                <div class="lg:col-span-1 space-y-6">

                    <!-- Publishing Options -->
                    <PublishingOptions
                        :is-active="form.is_active"
                        :show-draft-warning="true"
                        title="Publishing"
                        active-label="Active (Visible to customers)"
                        @update:is-active="handleActiveUpdate"
                    />

                    <!-- Organization Settings -->
                    <OrganizationSettings
                        :sort-order="form.sort_order"
                        title="Organization"
                        help-text="Lower numbers appear first in category listings"
                        @update:sort-order="handleSortOrderUpdate"
                    />

                    <!-- Live Preview -->
                    <LivePreview
                        :name="form.name"
                        :slug="form.slug"
                        :description="form.description"
                        :image-preview="imagePreview"
                        title="Preview"
                        base-url="/categories"
                    />

                </div>
            </div>
        </div>

        <!-- Mobile Sticky Footer -->
        <div class="lg:hidden fixed bottom-0 left-0 right-0 bg-background border-t p-4 shadow-lg z-50">
            <div class="flex space-x-3">
                <Button
                    @click="saveDraft"
                    variant="outline"
                    :disabled="form.processing || !isFormValid"
                    class="flex-1"
                >
                    <FolderPlus class="h-4 w-4 mr-2" />
                    {{ form.processing ? 'Saving...' : 'Save Draft' }}
                </Button>
                <Button
                    @click="saveAndPublish"
                    :disabled="form.processing || !isFormValid"
                    class="flex-1"
                >
                    <Save class="h-4 w-4 mr-2" />
                    {{ form.processing ? 'Publishing...' : 'Publish' }}
                </Button>
            </div>
        </div>
    </AdminLayout>
</template>
