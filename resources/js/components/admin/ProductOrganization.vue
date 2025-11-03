<script setup lang="ts">
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Plus, X } from 'lucide-vue-next';
import { ref } from 'vue';

interface Category {
    id: number;
    name: string;
}

interface Props {
    categoryId: string;
    tags: string[];
    categories: Category[];
}

interface Emits {
    (e: 'update:categoryId', value: string): void;
    (e: 'update:tags', value: string[]): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

const tagInput = ref('');

const addTag = () => {
    const newTag = tagInput.value.trim();
    if (newTag && !props.tags.includes(newTag)) {
        const updatedTags = [...props.tags, newTag];
        emit('update:tags', updatedTags);
        tagInput.value = '';
    }
};

const removeTag = (index: number) => {
    const updatedTags = [...props.tags];
    updatedTags.splice(index, 1);
    emit('update:tags', updatedTags);
};

const handleKeyPress = (event: KeyboardEvent) => {
    if (event.key === 'Enter') {
        event.preventDefault();
        addTag();
    }
};
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle>Organization</CardTitle>
        </CardHeader>
        <CardContent class="space-y-4">

            <!-- Category Selection -->
            <div class="space-y-2">
                <Label for="category_id">Category</Label>
                <select
                    id="category_id"
                    :value="categoryId"
                    @change="emit('update:categoryId', ($event.target as HTMLSelectElement).value)"
                    class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                >
                    <option value="">Select Category</option>
                    <option v-for="category in categories" :key="category.id" :value="category.id">
                        {{ category.name }}
                    </option>
                </select>
            </div>

            <!-- Tags -->

        </CardContent>
    </Card>
</template>
