<script setup lang="ts">
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Label } from '@/components/ui/label';

const { form, categories } = defineProps({
    form: { type: Object, required: true },
    categories: { type: Array, required: true }
});
</script>

<template>
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- Left Column -->
        <div class="space-y-6">

            <!-- Product Name -->
            <div>
                <Label for="name">Product Name *</Label>
                <Input
                    id="name"
                    v-model="form.name"
                    type="text"
                    required
                    class="mt-1"
                    placeholder="Enter product name"
                />
                <div v-if="form.errors?.name" class="text-red-500 text-sm mt-1">
                    {{ form.errors.name }}
                </div>
            </div>

            <!-- Description -->
            <div>
                <Label for="description">Description</Label>
                <Textarea
                    id="description"
                    v-model="form.description"
                    rows="4"
                    class="mt-1"
                    placeholder="Product description..."
                />
                <div v-if="form.errors?.description" class="text-red-500 text-sm mt-1">
                    {{ form.errors.description }}
                </div>
            </div>

            <!-- Price and Stock -->
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <Label for="price">Base Price ($) *</Label>
                    <Input
                        id="price"
                        v-model.number="form.price"
                        type="number"
                        step="0.01"
                        min="0"
                        required
                        class="mt-1"
                        placeholder="0.00"
                    />
                    <div v-if="form.errors?.price" class="text-red-500 text-sm mt-1">
                        {{ form.errors.price }}
                    </div>
                </div>
                <div>
                    <Label for="stock">Base Stock *</Label>
                    <Input
                        id="stock"
                        v-model.number="form.stock"
                        type="number"
                        min="0"
                        required
                        class="mt-1"
                        placeholder="0"
                    />
                    <small class="text-gray-500 block mt-1">Used when no variants are created</small>
                    <div v-if="form.errors?.stock" class="text-red-500 text-sm mt-1">
                        {{ form.errors.stock }}
                    </div>
                </div>
            </div>

            <!-- Category -->
            <div>
                <Label for="category_id">Category *</Label>
                <select
                    id="category_id"
                    v-model="form.category_id"
                    required
                    class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm mt-1"
                >
                    <option value="">Select a category</option>
                    <option v-for="category in categories" :key="category.id" :value="category.id">
                        {{ category.name }}
                    </option>
                </select>
                <div v-if="form.errors?.category_id" class="text-red-500 text-sm mt-1">
                    {{ form.errors.category_id }}
                </div>
            </div>

        </div>

        <!-- Right Column -->
        <div class="space-y-6">

            <!-- Product Image Upload -->
            <div>
                <Label for="image">Product Image</Label>
                <Input
                    id="image"
                    type="file"
                    accept="image/*"
                    @change="(e) => form.image = e.target.files[0]"
                    class="mt-1"
                />
                <div v-if="form.errors?.image" class="text-red-500 text-sm mt-1">
                    {{ form.errors.image }}
                </div>
            </div>

            <!-- Checkboxes -->
            <div class="space-y-4">
                <div class="flex items-center space-x-2">
                    <input
                        id="is_active"
                        v-model="form.is_active"
                        type="checkbox"
                        class="rounded border-gray-300"
                    />
                    <Label for="is_active">Active Product</Label>
                </div>
                <div class="flex items-center space-x-2">
                    <input
                        id="is_donatable"
                        v-model="form.is_donatable"
                        type="checkbox"
                        class="rounded border-gray-300"
                    />
                    <Label for="is_donatable">Available for Donations</Label>
                </div>
            </div>

        </div>
    </div>
</template>
