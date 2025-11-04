<script setup lang="ts">
import { Card, CardContent } from '@/components/ui/card';
import { CheckCircle, ShoppingCart, X } from 'lucide-vue-next';
import { ref } from 'vue';

interface Props {
    message?: string;
    icon?: string;
    dismissible?: boolean;
    className?: string;
}

const {
    message,
    icon = 'check',
    dismissible = false,
    className = 'mb-6'
} = defineProps<Props>();

const isVisible = ref(true);

const dismiss = () => {
    isVisible.value = false;
};

const getIcon = (iconType: string) => {
    switch (iconType) {
        case 'cart':
            return ShoppingCart;
        case 'check':
        default:
            return CheckCircle;
    }
};
</script>

<template>
    <div v-if="isVisible && (message || $page.props.flash?.success)" :class="className">
        <Card class="border-primary/20 bg-primary/10">
            <CardContent class="p-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <component :is="getIcon(icon)" class="h-4 w-4 text-primary" />
                        <p class="text-sm text-primary font-medium">
                            {{ message || $page.props.flash?.success }}
                        </p>
                    </div>
                    <button
                        v-if="dismissible"
                        @click="dismiss"
                        class="text-primary hover:text-primary/80 transition-colors"
                    >
                        <X class="h-4 w-4" />
                    </button>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
