<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Package, Edit, ArrowLeft, Eye } from 'lucide-vue-next';

const { product } = defineProps({
    product: { type: Object, required: true }
});

const headerActions = [
    {
        label: 'Edit Product',
        icon: Edit,
        onClick: () => router.visit(`/admin/products/${product.id}/edit`)
    }
];

function formatCurrency(value) {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD'
    }).format(value);
}

function formatDate(date) {
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}
</script>

<template>
    <AdminLayout :title="`Product: ${product.name}`">
        <Head :title="`Product: ${product.name}`" />

        <!-- Back Button -->
        <div class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <Button variant="outline" @click="router.visit('/admin/products')">
                <ArrowLeft class="h-4 w-4 mr-2" />
                Back to Products
            </Button>
        </div>

        <!-- Page Header -->
        <PageHeader
            :title="product.name"
            :description="`Product ID: ${product.id}`"
            :icon="Package"
            icon-color="text-blue-600"
            :actions="headerActions"
        />

        <div class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

                <!-- Product Image -->
                <div class="lg:col-span-1">
                    <Card>
                        <CardHeader>
                            <CardTitle>Product Image</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="aspect-square bg-gray-100 rounded-lg overflow-hidden">
                                <img
                                    v-if="product.image_url"
                                    :src="product.image_url"
                                    :alt="product.name"
                                    class="w-full h-full object-cover"
                                />
                                <div v-else class="w-full h-full flex items-center justify-center">
                                    <Package class="h-16 w-16 text-gray-400" />
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Product Details -->
                <div class="lg:col-span-2 space-y-6">

                    <!-- Basic Information -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Basic Information</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="text-sm font-medium text-gray-600">Name</label>
                                    <p class="text-lg font-semibold">{{ product.name }}</p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-600">Slug</label>
                                    <p class="text-gray-900">{{ product.slug }}</p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-600">Category</label>
                                    <p class="text-gray-900">{{ product.category?.name || 'N/A' }}</p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-600">Price</label>
                                    <p class="text-lg font-bold text-green-600">{{ formatCurrency(product.price) }}</p>
                                </div>
                            </div>

                            <div v-if="product.description">
                                <label class="text-sm font-medium text-gray-600">Description</label>
                                <p class="text-gray-900 mt-1">{{ product.description }}</p>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Stock & Status -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Stock & Status</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label class="text-sm font-medium text-gray-600">Stock Quantity</label>
                                    <p class="text-2xl font-bold" :class="{
                                        'text-red-600': product.stock === 0,
                                        'text-yellow-600': product.stock <= 10 && product.stock > 0,
                                        'text-green-600': product.stock > 10
                                    }">
                                        {{ product.stock }}
                                    </p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-600">Status</label>
                                    <div class="mt-1">
                                        <Badge :class="product.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'">
                                            {{ product.is_active ? 'Active' : 'Inactive' }}
                                        </Badge>
                                    </div>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-600">Donatable</label>
                                    <div class="mt-1">
                                        <Badge :class="product.is_donatable ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800'">
                                            {{ product.is_donatable ? 'Yes' : 'No' }}
                                        </Badge>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Timestamps -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Timestamps</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="text-sm font-medium text-gray-600">Created At</label>
                                    <p class="text-gray-900">{{ formatDate(product.created_at) }}</p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-600">Last Updated</label>
                                    <p class="text-gray-900">{{ formatDate(product.updated_at) }}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                </div>
            </div>
        </div>
    </AdminLayout>
</template>
