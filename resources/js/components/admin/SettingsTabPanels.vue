<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import CharacterCountInput from '@/components/admin/CharacterCountInput.vue';
import LogoUpload from '@/components/admin/LogoUpload.vue';
import { Globe, Palette, MessageSquare, Heart, Info } from 'lucide-vue-next';

interface Props {
    activeTab: string;
    form: {
        site_name: string;
        hero_title: string;
        hero_subtitle: string;
        donation_message: string;
        errors: Record<string, string>;
    };
    logoPreview?: string | null;
    hasNewLogo: boolean;
}

interface Emits {
    (e: 'update:siteName', value: string): void;
    (e: 'update:heroTitle', value: string): void;
    (e: 'update:heroSubtitle', value: string): void;
    (e: 'update:donationMessage', value: string): void;
    (e: 'logoSelected', file: File): void;
    (e: 'logoRemoved'): void;
    (e: 'existingLogoRemoved'): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();
</script>

<template>
    <!-- General Settings -->
    <Card v-show="activeTab === 'general'">
        <CardHeader>
            <CardTitle class="flex items-center space-x-2">
                <Globe class="h-5 w-5" />
                <span>General Settings</span>
            </CardTitle>
        </CardHeader>
        <CardContent class="space-y-6">
            <CharacterCountInput
                :model-value="form.site_name"
                @update:model-value="emit('update:siteName', $event)"
                label="Site Name"
                placeholder="Enter your site name"
                :required="true"
                :max-length="255"
                :error="form.errors.site_name"
                help-text="This appears in the browser title and navigation"
            />
        </CardContent>
    </Card>

    <!-- Branding Settings -->
    <Card v-show="activeTab === 'branding'">
        <CardHeader>
            <CardTitle class="flex items-center space-x-2">
                <Palette class="h-5 w-5" />
                <span>Branding</span>
            </CardTitle>
        </CardHeader>
        <CardContent class="space-y-6">
            <LogoUpload
                :logo-preview="logoPreview"
                :has-new-logo="hasNewLogo"
                :error="form.errors.logo"
                :max-size="2"
                @logo-selected="emit('logoSelected', $event)"
                @logo-removed="emit('logoRemoved')"
                @existing-logo-removed="emit('existingLogoRemoved')"
            />
        </CardContent>
    </Card>

    <!-- Content Settings -->
    <Card v-show="activeTab === 'content'">
        <CardHeader>
            <CardTitle class="flex items-center space-x-2">
                <MessageSquare class="h-5 w-5" />
                <span>Homepage Content</span>
            </CardTitle>
        </CardHeader>
        <CardContent class="space-y-6">
            <CharacterCountInput
                :model-value="form.hero_title"
                @update:model-value="emit('update:heroTitle', $event)"
                label="Hero Title"
                placeholder="Welcome to our store"
                :required="true"
                :max-length="255"
                :error="form.errors.hero_title"
                help-text="Main headline displayed on your homepage"
                class="text-lg"
            />

            <CharacterCountInput
                :model-value="form.hero_subtitle"
                @update:model-value="emit('update:heroSubtitle', $event)"
                label="Hero Subtitle"
                placeholder="Discover amazing products and support great causes"
                type="textarea"
                :rows="3"
                :max-length="500"
                :error="form.errors.hero_subtitle"
                help-text="Secondary text displayed below the main title"
            />
        </CardContent>
    </Card>

    <!-- Donations Settings -->
    <Card v-show="activeTab === 'donations'">
        <CardHeader>
            <CardTitle class="flex items-center space-x-2">
                <Heart class="h-5 w-5" />
                <span>Donation Settings</span>
            </CardTitle>
        </CardHeader>
        <CardContent class="space-y-6">
            <CharacterCountInput
                :model-value="form.donation_message"
                @update:model-value="emit('update:donationMessage', $event)"
                label="Donation Message"
                placeholder="Support our cause by making a donation. Every contribution helps make a difference."
                type="textarea"
                :rows="4"
                :max-length="1000"
                :error="form.errors.donation_message"
            />

            <div class="p-3 bg-orange-50 border border-orange-200 rounded-lg">
                <div class="flex items-start space-x-2">
                    <Heart class="h-4 w-4 text-orange-600 mt-0.5" />
                    <div class="text-sm text-orange-700">
                        <p class="font-medium">Donation Information</p>
                        <p class="text-xs mt-1">This message will be displayed on donation pages and checkout to encourage support.</p>
                    </div>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
