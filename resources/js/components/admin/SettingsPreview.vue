<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

interface Props {
    siteName: string;
    heroTitle: string;
    heroSubtitle: string;
    donationMessage: string;
    logoPreview?: string | null;
    show: boolean;
}

const props = defineProps<Props>();
</script>

<template>
    <Card v-if="show" class="mt-6">
        <CardHeader>
            <CardTitle class="text-lg">Live Preview</CardTitle>
        </CardHeader>
        <CardContent class="space-y-4">
            <div class="border rounded-lg p-4 bg-background">
                <!-- Logo -->
                <div v-if="logoPreview" class="mb-3">
                    <img :src="logoPreview" alt="Logo preview" class="h-8 w-auto" />
                </div>

                <!-- Site Name -->
                <h3 class="font-bold text-lg">{{ siteName || 'Site Name' }}</h3>

                <!-- Hero Title -->
                <h4 class="text-xl font-semibold mt-2">{{ heroTitle || 'Hero Title' }}</h4>

                <!-- Hero Subtitle -->
                <p v-if="heroSubtitle" class="text-sm text-muted-foreground mt-1">
                    {{ heroSubtitle }}
                </p>

                <!-- Donation Message -->
                <div v-if="donationMessage" class="mt-3 p-3 bg-red-50 border border-red-200 rounded text-xs">
                    <div class="flex items-start space-x-2">
                        <span>💝</span>
                        <div>
                            <p class="font-medium text-red-800">Donation Message</p>
                            <p class="text-red-700 mt-1">{{ donationMessage }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
