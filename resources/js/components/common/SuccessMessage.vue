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
        <Card class="border-green-200 bg-green-50">
            <CardContent class="p-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <component :is="getIcon(icon)" class="h-4 w-4 text-green-600" />
                        <p class="text-sm text-green-700 font-medium">
                            {{ message || $page.props.flash?.success }}
                        </p>
                    </div>
                    <button
                        v-if="dismissible"
                        @click="dismiss"
                        class="text-green-600 hover:text-green-800 transition-colors"
                    >
                        <X class="h-4 w-4" />
                    </button>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
