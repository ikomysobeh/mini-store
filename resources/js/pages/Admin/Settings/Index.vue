<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import SettingsNavigation from '@/components/admin/SettingsNavigation.vue';
import SettingsPreview from '@/components/admin/SettingsPreview.vue';
import SettingsTabPanels from '@/components/admin/SettingsTabPanels.vue';
import UnsavedChangesAlert from '@/components/admin/UnsavedChangesAlert.vue';
import { Settings, Save, Eye, Globe, Palette, MessageSquare, Heart, AlertCircle, CheckCircle } from 'lucide-vue-next';
import { ref, computed, watch } from 'vue';
import AdminLayout from '@/layouts/AdminLayout.vue';

const { settings } = defineProps({
    settings: { type: Object, required: true },
});

// Form setup with current settings (ENHANCED with hero background)
const form = useForm({
    site_name: settings.site_name || '',
    hero_title: settings.hero_title || '',
    hero_subtitle: settings.hero_subtitle || '',
    donation_message: settings.donation_message || '',
    logo: null,
    remove_logo: false,
    // NEW: Hero background fields
    hero_background_image: null,
    hero_use_background_image: settings.hero_use_background_image || false,
    hero_background_overlay: settings.hero_background_overlay || 'dark',
    remove_hero_background: false,
});

// State
const logoPreview = ref(settings.logo ? `/storage/${settings.logo}` : null);
// NEW: Hero background state
const heroBackgroundPreview = ref(settings.hero_background_url || null);
const activeTab = ref('general');
const showPreview = ref(false);

// Computed
const hasChanges = computed(() => {
    return form.isDirty;
});

const hasNewLogo = computed(() => {
    return !!form.logo;
});

// NEW: Hero background computed
const hasNewHeroBackground = computed(() => {
    return !!form.hero_background_image;
});

// Tabs configuration (UPDATED with hero tab)
const tabs = [
    { id: 'general', label: 'General', icon: Globe },
    { id: 'branding', label: 'Branding', icon: Palette },
    { id: 'hero', label: 'Hero Section', icon: Eye }, // NEW: Hero tab
    { id: 'content', label: 'Content', icon: MessageSquare },
    { id: 'donations', label: 'Donations', icon: Heart },
];

// Logo handling (existing)
const handleLogoSelected = (file: File) => {
    form.logo = file;
    form.remove_logo = false;

    const reader = new FileReader();
    reader.onload = (e) => {
        logoPreview.value = e.target?.result as string;
    };
    reader.readAsDataURL(file);
};

const handleLogoRemoved = () => {
    form.logo = null;
    logoPreview.value = settings.logo ? `/storage/${settings.logo}` : null;
    form.remove_logo = false;
};

const handleExistingLogoRemoved = () => {
    if (confirm('Are you sure you want to remove the current logo?')) {
        logoPreview.value = null;
        form.remove_logo = true;
        form.logo = null;
    }
};

// NEW: Hero background handling
const handleHeroBackgroundSelected = (file: File) => {
    form.hero_background_image = file;
    form.remove_hero_background = false;

    const reader = new FileReader();
    reader.onload = (e) => {
        heroBackgroundPreview.value = e.target?.result as string;
    };
    reader.readAsDataURL(file);
};

const handleHeroBackgroundRemoved = () => {
    form.hero_background_image = null;
    heroBackgroundPreview.value = settings.hero_background_url || null;
    form.remove_hero_background = false;
};

const handleExistingHeroBackgroundRemoved = () => {
    if (confirm('Are you sure you want to remove the current hero background?')) {
        heroBackgroundPreview.value = null;
        form.remove_hero_background = true;
        form.hero_background_image = null;
        form.hero_use_background_image = false; // Auto-disable
    }
};

// Form submission
const saveSettings = () => {
    // Always use FormData for consistent handling
    const formData = new FormData();

    // Add all text fields
    formData.append('site_name', form.site_name);
    formData.append('hero_title', form.hero_title);
    formData.append('hero_subtitle', form.hero_subtitle);
    formData.append('donation_message', form.donation_message);

    // NEW: Add hero background fields
    formData.append('hero_use_background_image', form.hero_use_background_image ? '1' : '0');
    formData.append('hero_background_overlay', form.hero_background_overlay);

    // Add files if present
    if (form.logo) {
        formData.append('logo', form.logo);
    }

    // NEW: Add hero background file if present
    if (form.hero_background_image) {
        formData.append('hero_background_image', form.hero_background_image);
    }

    // Add remove flags if set
    if (form.remove_logo) {
        formData.append('remove_logo', '1');
    }

    // NEW: Add hero background remove flag
    if (form.remove_hero_background) {
        formData.append('remove_hero_background', '1');
    }

    // Always add method spoofing for Laravel
    formData.append('_method', 'PATCH');

    // Always submit using POST with method spoofing and FormData
    form.post('/admin/settings', {
        data: formData,
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            // Success handled by Laravel redirect
        },
        onError: (errors) => {
            console.error('Settings update failed:', errors);
        }
    });
};

const resetForm = () => {
    if (confirm('Are you sure you want to reset all changes?')) {
        form.reset();
        logoPreview.value = settings.logo ? `/storage/${settings.logo}` : null;
        heroBackgroundPreview.value = settings.hero_background_url || null;
    }
};
</script>

<template>
    <AdminLayout title="Orders Management" :breadcrumbs="breadcrumbs">
        <div class="min-h-screen bg-muted/20">
            <Head title="Site Settings" />

            <!-- Header -->
            <div class="border-b bg-background">
                <div class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex items-center justify-between h-16">
                        <div class="flex items-center space-x-4">
                            <div class="p-2 bg-purple-100 rounded-lg">
                                <Settings class="h-6 w-6 text-purple-600" />
                            </div>
                            <div>
                                <h1 class="text-2xl font-bold text-foreground flex items-center space-x-2">
                                    <span>Site Settings</span>
                                    <Badge v-if="hasChanges" variant="outline" class="bg-orange-50 text-orange-700 border-orange-200">
                                        <AlertCircle class="h-3 w-3 mr-1" />
                                        Unsaved Changes
                                    </Badge>
                                </h1>
                                <p class="text-sm text-muted-foreground">Configure your site appearance and content</p>
                            </div>
                        </div>

                        <div class="flex items-center space-x-3">
                            <Button variant="outline" @click="showPreview = !showPreview">
                                <Eye class="h-4 w-4 mr-2" />
                                {{ showPreview ? 'Hide' : 'Show' }} Preview
                            </Button>
                            <Button variant="outline" @click="resetForm" :disabled="!hasChanges || form.processing">
                                Reset
                            </Button>
                            <Button @click="saveSettings" :disabled="form.processing || !hasChanges">
                                <Save class="h-4 w-4 mr-2" />
                                {{ form.processing ? 'Saving...' : 'Save Settings' }}
                            </Button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <!-- Success Message -->
                <div v-if="$page.props.flash?.success" class="mb-6">
                    <Card class="border-green-200 bg-green-50">
                        <CardContent class="p-4">
                            <div class="flex items-center space-x-2">
                                <CheckCircle class="h-4 w-4 text-green-600" />
                                <p class="text-sm text-green-700 font-medium">{{ $page.props.flash.success }}</p>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
                    <!-- Sidebar Navigation -->
                    <div class="lg:col-span-1">
                        <SettingsNavigation
                            :tabs="tabs"
                            :active-tab="activeTab"
                            @update:active-tab="activeTab = $event"
                        />
                        <!-- Settings Preview -->
                        <SettingsPreview
                            :show="showPreview"
                            :site-name="form.site_name"
                            :hero-title="form.hero_title"
                            :hero-subtitle="form.hero_subtitle"
                            :donation-message="form.donation_message"
                            :logo-preview="logoPreview"
                            :hero-background-preview="heroBackgroundPreview"
                            :hero-use-background="form.hero_use_background_image"
                            :hero-overlay="form.hero_background_overlay"
                        />
                    </div>

                    <!-- Main Content -->
                    <div class="lg:col-span-3 space-y-8">
                        <SettingsTabPanels
                            :active-tab="activeTab"
                            :form="form"
                            :logo-preview="logoPreview"
                            :has-new-logo="hasNewLogo"
                            :hero-background-preview="heroBackgroundPreview"
                            :has-new-hero-background="hasNewHeroBackground"
                            @update:site-name="form.site_name = $event"
                            @update:hero-title="form.hero_title = $event"
                            @update:hero-subtitle="form.hero_subtitle = $event"
                            @update:donation-message="form.donation_message = $event"
                            @logo-selected="handleLogoSelected"
                            @logo-removed="handleLogoRemoved"
                            @existing-logo-removed="handleExistingLogoRemoved"
                            @hero-background-selected="handleHeroBackgroundSelected"
                            @hero-background-removed="handleHeroBackgroundRemoved"
                            @existing-hero-background-removed="handleExistingHeroBackgroundRemoved"
                            @update:hero-use-background="form.hero_use_background_image = $event"
                            @update:hero-overlay="form.hero_background_overlay = $event"
                        />

                        <!-- Unsaved Changes Alert -->
                        <UnsavedChangesAlert
                            :show="hasChanges"
                            :is-processing="form.processing"
                            @save="saveSettings"
                            @reset="resetForm"
                        />
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
