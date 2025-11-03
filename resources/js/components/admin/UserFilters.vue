<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Search, Crown, User } from 'lucide-vue-next';

interface Props {
    searchTerm: string;
    selectedRole: string;
    showFilters: boolean;
}

interface Emits {
    (e: 'update:searchTerm', value: string): void;
    (e: 'update:selectedRole', value: string): void;
    (e: 'clearFilters'): void;
    (e: 'applyFilters'): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

const roleOptions = [
    { value: '', label: 'All Roles' },
    { value: '1', label: 'Administrators Only' },
    { value: '0', label: 'Regular Users Only' }
];

const quickFilterAdmins = () => {
    emit('update:selectedRole', '1');
    emit('applyFilters');
};

const quickFilterUsers = () => {
    emit('update:selectedRole', '0');
    emit('applyFilters');
};
</script>

<template>
    <Card v-if="showFilters" class="mb-6">
        <CardHeader>
            <CardTitle class="text-lg">Filters & Search</CardTitle>
        </CardHeader>
        <CardContent class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">

                <!-- Search -->
                <div class="space-y-2">
                    <label class="text-sm font-medium">Search Users</label>
                    <div class="relative">
                        <Search class="absolute left-3 top-3 h-4 w-4 text-muted-foreground" />
                        <Input
                            :value="searchTerm"
                            @input="emit('update:searchTerm', ($event.target as HTMLInputElement).value)"
                            placeholder="Name, email..."
                            class="pl-10"
                        />
                    </div>
                </div>

                <!-- Role Filter -->
                <div class="space-y-2">
                    <label class="text-sm font-medium">User Role</label>
                    <select
                        :value="selectedRole"
                        @change="emit('update:selectedRole', ($event.target as HTMLSelectElement).value)"
                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                    >
                        <option v-for="option in roleOptions" :key="option.value" :value="option.value">
                            {{ option.label }}
                        </option>
                    </select>
                </div>

                <!-- Quick Filters -->
                <div class="space-y-2">
                    <label class="text-sm font-medium">Quick Filters</label>
                    <div class="flex flex-wrap gap-2">
                        <Button variant="outline" size="sm" @click="quickFilterAdmins">
                            <Crown class="h-3 w-3 mr-1" />
                            Admins
                        </Button>
                        <Button variant="outline" size="sm" @click="quickFilterUsers">
                            <User class="h-3 w-3 mr-1" />
                            Users
                        </Button>
                    </div>
                </div>

                <!-- Actions -->
                <div class="space-y-2">
                    <label class="text-sm font-medium">&nbsp;</label>
                    <Button variant="outline" @click="emit('clearFilters')" class="w-full">
                        Clear All Filters
                    </Button>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
