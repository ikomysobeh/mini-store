<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
// REMOVED: import { Switch } from '@/components/ui/switch';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Upload, X, Image, Eye, Monitor, Smartphone, Palette, ToggleLeft, ToggleRight } from 'lucide-vue-next';
import { ref } from 'vue';

const props = defineProps({
    activeTab: String,
    form: Object,
    logoPreview: String,
    hasNewLogo: Boolean,
    // Hero background props
    heroBackgroundPreview: String,
    hasNewHeroBackground: Boolean,
});

const emit = defineEmits([
    'update:site-name',
    'update:hero-title',
    'update:hero-subtitle',
    'update:donation-message',
    'logo-selected',
    'logo-removed',
    'existing-logo-removed',
    // Hero background events
    'hero-background-selected',
    'hero-background-removed',
    'existing-hero-background-removed',
    'update:hero-use-background',
    'update:hero-overlay',
]);

const logoInputRef = ref(null);
const heroBackgroundInputRef = ref(null);

// Logo handlers (existing)
const triggerLogoUpload = () => {
    logoInputRef.value?.click();
};

const handleLogoChange = (event) => {
    const file = event.target.files[0];
    if (file) {
        emit('logo-selected', file);
    }
};

// Hero background handlers
const triggerHeroBackgroundUpload = () => {
    heroBackgroundInputRef.value?.click();
};

const handleHeroBackgroundChange = (event) => {
    const file = event.target.files[0];
    if (file) {
        emit('hero-background-selected', file);
    }
};

// NEW: Toggle background image
const toggleHeroBackground = () => {
    emit('update:hero-use-background', !props.form.hero_use_background_image);
};
</script>

<template>
    <!-- General Tab -->
    <Card v-if="activeTab === 'general'">
        <CardHeader>
            <CardTitle>General Settings</CardTitle>
        </CardHeader>
        <CardContent class="space-y-6">
            <div class="space-y-2">
                <Label for="site_name">Site Name</Label>
                <Input
                    id="site_name"
                    :model-value="form.site_name"
                    @update:model-value="emit('update:site-name', $event)"
                    placeholder="Enter your site name"
                />
                <p class="text-xs text-muted-foreground">This appears in the browser title and throughout your site</p>
            </div>

            <div class="space-y-2">
                <Label for="hero_title">Hero Title</Label>
                <Input
                    id="hero_title"
                    :model-value="form.hero_title"
                    @update:model-value="emit('update:hero-title', $event)"
                    placeholder="Main headline for your homepage"
                />
            </div>

            <div class="space-y-2">
                <Label for="hero_subtitle">Hero Subtitle</Label>
                <Textarea
                    id="hero_subtitle"
                    :model-value="form.hero_subtitle"
                    @update:model-value="emit('update:hero-subtitle', $event)"
                    placeholder="Supporting text under the main headline"
                    rows="3"
                />
            </div>
        </CardContent>
    </Card>

    <!-- Branding Tab -->
    <Card v-if="activeTab === 'branding'">
        <CardHeader>
            <CardTitle>Branding</CardTitle>
        </CardHeader>
        <CardContent class="space-y-6">
            <!-- Logo Upload Section -->
            <div class="space-y-4">
                <Label>Site Logo</Label>

                <!-- Current Logo Display -->
                <div v-if="logoPreview" class="space-y-4">
                    <div class="flex items-center space-x-4 p-4 border rounded-lg bg-muted/20">
                        <img :src="logoPreview" alt="Logo preview" class="h-16 w-16 object-contain bg-white rounded border" />
                        <div class="flex-1">
                            <p class="font-medium">{{ hasNewLogo ? 'New logo selected' : 'Current logo' }}</p>
                            <p class="text-sm text-muted-foreground">Logo will appear in the navigation bar</p>
                        </div>
                        <Button variant="outline" size="sm" @click="hasNewLogo ? emit('logo-removed') : emit('existing-logo-removed')">
                            <X class="h-4 w-4" />
                        </Button>
                    </div>
                </div>

                <!-- Logo Upload Area -->
                <div @click="triggerLogoUpload" class="border-2 border-dashed border-muted-foreground/25 rounded-lg p-8 text-center hover:border-muted-foreground/40 cursor-pointer transition-colors">
                    <Upload class="mx-auto h-12 w-12 text-muted-foreground mb-4" />
                    <p class="text-sm font-medium">Click to upload logo</p>
                    <p class="text-xs text-muted-foreground">PNG, JPG, GIF up to 2MB</p>
                </div>

                <input
                    ref="logoInputRef"
                    type="file"
                    accept="image/*"
                    @change="handleLogoChange"
                    class="hidden"
                />
            </div>
        </CardContent>
    </Card>

    <!-- Hero Section Tab -->
    <Card v-if="activeTab === 'hero'">
        <CardHeader>
            <CardTitle class="flex items-center space-x-2">
                <Eye class="h-5 w-5" />
                <span>Hero Section Settings</span>
            </CardTitle>
        </CardHeader>
        <CardContent class="space-y-6">

            <!-- Background Image Section -->
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <Label class="text-base">Background Image</Label>
                    <!-- FIXED: Custom Toggle Button instead of Switch -->
                    <Button
                        variant="outline"
                        size="sm"
                        @click="toggleHeroBackground"
                        :class="[
                            'flex items-center space-x-2 transition-all',
                            form.hero_use_background_image
                                ? 'bg-primary text-primary-foreground hover:bg-primary/90'
                                : 'hover:bg-muted'
                        ]"
                    >
                        <component
                            :is="form.hero_use_background_image ? ToggleRight : ToggleLeft"
                            class="h-4 w-4"
                        />
                        <span class="text-sm">
                            {{ form.hero_use_background_image ? 'Enabled' : 'Disabled' }}
                        </span>
                    </Button>
                </div>

                <!-- Background Image Preview -->
                <div v-if="heroBackgroundPreview" class="space-y-4">
                    <div class="relative rounded-lg overflow-hidden border">
                        <img
                            :src="heroBackgroundPreview"
                            alt="Hero background preview"
                            class="w-full h-48 object-cover"
                        />
                        <!-- Overlay Preview -->
                        <div
                            :class="[
                                'absolute inset-0 flex items-center justify-center',
                                form.hero_background_overlay === 'dark' ? 'bg-black/40' :
                                form.hero_background_overlay === 'light' ? 'bg-white/40' : ''
                            ]"
                        >
                            <div class="text-center">
                                <h3 class="text-2xl font-bold text-white mb-2">{{ form.hero_title || 'Hero Title' }}</h3>
                                <p class="text-white/90">{{ form.hero_subtitle || 'Hero subtitle preview' }}</p>
                            </div>
                        </div>

                        <!-- Remove Button -->
                        <Button
                            variant="destructive"
                            size="sm"
                            class="absolute top-2 right-2"
                            @click="hasNewHeroBackground ? emit('hero-background-removed') : emit('existing-hero-background-removed')"
                        >
                            <X class="h-4 w-4" />
                        </Button>
                    </div>

                    <div class="flex items-center space-x-2">
                        <Badge variant="outline">
                            {{ hasNewHeroBackground ? 'New background selected' : 'Current background' }}
                        </Badge>
                        <Badge v-if="!form.hero_use_background_image" variant="secondary">
                            Disabled
                        </Badge>
                    </div>
                </div>

                <!-- Background Upload Area -->
                <div
                    @click="triggerHeroBackgroundUpload"
                    class="border-2 border-dashed border-muted-foreground/25 rounded-lg p-8 text-center hover:border-muted-foreground/40 cursor-pointer transition-colors"
                >
                    <Image class="mx-auto h-12 w-12 text-muted-foreground mb-4" />
                    <p class="text-sm font-medium">{{ heroBackgroundPreview ? 'Replace background image' : 'Upload background image' }}</p>
                    <p class="text-xs text-muted-foreground">PNG, JPG, GIF, WebP up to 5MB • Recommended: 1920x1080px</p>
                </div>

                <input
                    ref="heroBackgroundInputRef"
                    type="file"
                    accept="image/*"
                    @change="handleHeroBackgroundChange"
                    class="hidden"
                />
            </div>

            <!-- Overlay Settings -->
            <div class="space-y-4">
                <Label>Text Overlay</Label>
                <div class="grid grid-cols-3 gap-3">
                    <button
                        v-for="overlay in [
                            { value: 'dark', label: 'Dark', class: 'bg-black/40 text-white' },
                            { value: 'light', label: 'Light', class: 'bg-white/40 text-black' },
                            { value: 'none', label: 'None', class: 'bg-transparent text-black' }
                        ]"
                        :key="overlay.value"
                        @click="emit('update:hero-overlay', overlay.value)"
                        :class="[
                            'p-4 rounded-lg border-2 text-center transition-all',
                            form.hero_background_overlay === overlay.value
                                ? 'border-primary bg-primary/10'
                                : 'border-muted-foreground/20 hover:border-muted-foreground/40'
                        ]"
                    >
                        <div :class="['w-full h-16 rounded mb-2 flex items-center justify-center text-sm font-medium', overlay.class]">
                            {{ overlay.label }}
                        </div>
                        <p class="text-xs text-muted-foreground">{{ overlay.label }} Overlay</p>
                    </button>
                </div>
                <p class="text-xs text-muted-foreground">Choose an overlay to ensure text remains readable over your background image</p>
            </div>

            <!-- Device Preview -->
            <div class="space-y-4">
                <Label>Preview on Devices</Label>
                <div class="flex space-x-2">
                    <Button variant="outline" size="sm">
                        <Monitor class="h-4 w-4 mr-2" />
                        Desktop
                    </Button>
                    <Button variant="outline" size="sm">
                        <Smartphone class="h-4 w-4 mr-2" />
                        Mobile
                    </Button>
                </div>
            </div>
        </CardContent>
    </Card>

    <!-- Content Tab -->
    <Card v-if="activeTab === 'content'">
        <CardHeader>
            <CardTitle>Content Settings</CardTitle>
        </CardHeader>
        <CardContent class="space-y-6">
            <div class="space-y-2">
                <Label for="donation_message">Donation Message</Label>
                <Textarea
                    id="donation_message"
                    :model-value="form.donation_message"
                    @update:model-value="emit('update:donation-message', $event)"
                    placeholder="Message shown on donation pages"
                    rows="4"
                />
                <p class="text-xs text-muted-foreground">This message appears when users make donations</p>
            </div>
        </CardContent>
    </Card>

    <!-- Donations Tab -->
    <Card v-if="activeTab === 'donations'">
        <CardHeader>
            <CardTitle>Donation Settings</CardTitle>
        </CardHeader>
        <CardContent class="space-y-6">
            <div class="text-center p-8 text-muted-foreground">
                <Palette class="mx-auto h-12 w-12 mb-4" />
                <p>Donation settings coming soon...</p>
            </div>
        </CardContent>
    </Card>
</template>
