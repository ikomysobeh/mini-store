<script setup lang="ts">
import { Card, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

interface Props {
    description?: string;
    specifications?: Record<string, string>;
    features?: string[];
    className?: string;
}

const {
    description,
    specifications,
    features,
    className = 'mb-12'
} = defineProps<Props>();

const hasContent = description || specifications || features?.length;
</script>

<template>
    <Card v-if="hasContent" :class="className">
        <CardContent class="p-6">
            <div class="prose max-w-none">

                <!-- Description -->
                <div v-if="description">
                    <h3 class="text-xl font-semibold mb-4">{{ t('product.description') }}</h3>
                    <div class="text-muted-foreground leading-relaxed whitespace-pre-line">
                        {{ description }}
                    </div>
                </div>

                <!-- Features -->
                <div v-if="features?.length" :class="{ 'mt-8': description }">
                    <h4 class="font-semibold mb-3">{{ t('productDetail.keyFeatures') }}</h4>
                    <ul class="space-y-2">
                        <li v-for="(feature, index) in features" :key="index" class="flex items-start space-x-2">
                            <div class="w-1.5 h-1.5 bg-primary/80 rounded-full mt-2 flex-shrink-0"></div>
                            <span class="text-muted-foreground">{{ feature }}</span>
                        </li>
                    </ul>
                </div>

                <!-- Specifications -->
                <div v-if="specifications" :class="{ 'mt-8': description || features?.length }">
                    <h4 class="font-semibold mb-4">{{ t('product.details') }}</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div v-for="(value, key) in specifications" :key="key"
                             class="flex justify-between items-center py-2 px-3 bg-muted/30 rounded-lg">
                            <span class="text-muted-foreground font-medium">{{ key }}:</span>
                            <span class="font-semibold">{{ value }}</span>
                        </div>
                    </div>
                </div>

                <!-- No Content Message -->
            </div>
        </CardContent>
    </Card>

    <!-- Fallback if no content -->
    <Card v-else :class="className">
        <CardContent class="p-6 text-center">
            <p class="text-muted-foreground">{{ t('productDetail.noAdditionalInfo') }}</p>
        </CardContent>
    </Card>
</template>
