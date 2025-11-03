<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { User, Save, Crown, CheckCircle, XCircle } from 'lucide-vue-next';
import { computed } from 'vue';

interface UserData {
    id: number;
    name: string;
    email: string;
    is_admin: boolean;
    email_verified_at: string | null;
    created_at: string;
    last_active_at?: string;
    customer?: {
        first_name?: string;
        last_name?: string;
        phone?: string;
        address?: string;
    };
}

interface FormData {
    name: string;
    email: string;
    password: string;
    password_confirmation: string;
    is_admin: boolean;
    first_name: string;
    last_name: string;
    phone: string;
    address: string;
    processing: boolean;
    errors: Record<string, string>;
}

interface Props {
    user: UserData;
    form: FormData;
    isEditing: boolean;
    locale?: string;
}

interface Emits {
    (e: 'submit'): void;
}

const props = withDefaults(defineProps<Props>(), {
    locale: 'nl-NL'
});

const emit = defineEmits<Emits>();

const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString(props.locale, {
        day: '2-digit',
        month: 'long',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle class="flex items-center justify-between">
                <div class="flex items-center space-x-2">
                    <User class="h-5 w-5" />
                    <span>{{ isEditing ? 'Edit User Information' : 'User Information' }}</span>
                </div>
                <Button
                    v-if="isEditing"
                    @click="emit('submit')"
                    :disabled="form.processing"
                >
                    <Save class="h-4 w-4 mr-2" />
                    {{ form.processing ? 'Saving...' : 'Save Changes' }}
                </Button>
            </CardTitle>
        </CardHeader>
        <CardContent class="space-y-6">

            <!-- Edit Mode Form -->
            <form v-if="isEditing" @submit.prevent="emit('submit')" class="space-y-6">

                <!-- Account Information -->
                <div class="space-y-4">
                    <h3 class="text-lg font-medium">Account Information</h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <Label for="name">Display Name *</Label>
                            <Input
                                id="name"
                                v-model="form.name"
                                :class="{ 'border-destructive': form.errors.name }"
                            />
                            <p v-if="form.errors.name" class="text-sm text-destructive">
                                {{ form.errors.name }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <Label for="email">Email Address *</Label>
                            <Input
                                id="email"
                                v-model="form.email"
                                type="email"
                                :class="{ 'border-destructive': form.errors.email }"
                            />
                            <p v-if="form.errors.email" class="text-sm text-destructive">
                                {{ form.errors.email }}
                            </p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <Label for="password">New Password</Label>
                            <Input
                                id="password"
                                v-model="form.password"
                                type="password"
                                placeholder="Leave blank to keep current password"
                                :class="{ 'border-destructive': form.errors.password }"
                            />
                            <p v-if="form.errors.password" class="text-sm text-destructive">
                                {{ form.errors.password }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <Label for="password_confirmation">Confirm Password</Label>
                            <Input
                                id="password_confirmation"
                                v-model="form.password_confirmation"
                                type="password"
                                placeholder="Confirm new password"
                            />
                        </div>
                    </div>
                </div>

                <Separator />

                <!-- Personal Information -->
                <div class="space-y-4">
                    <h3 class="text-lg font-medium">Personal Information</h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <Label for="first_name">First Name *</Label>
                            <Input
                                id="first_name"
                                v-model="form.first_name"
                                :class="{ 'border-destructive': form.errors.first_name }"
                            />
                            <p v-if="form.errors.first_name" class="text-sm text-destructive">
                                {{ form.errors.first_name }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <Label for="last_name">Last Name *</Label>
                            <Input
                                id="last_name"
                                v-model="form.last_name"
                                :class="{ 'border-destructive': form.errors.last_name }"
                            />
                            <p v-if="form.errors.last_name" class="text-sm text-destructive">
                                {{ form.errors.last_name }}
                            </p>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <Label for="phone">Phone Number</Label>
                        <Input
                            id="phone"
                            v-model="form.phone"
                            type="tel"
                            :class="{ 'border-destructive': form.errors.phone }"
                        />
                        <p v-if="form.errors.phone" class="text-sm text-destructive">
                            {{ form.errors.phone }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <Label for="address">Address</Label>
                        <textarea
                            id="address"
                            v-model="form.address"
                            rows="3"
                            :class="[
                                'flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2',
                                { 'border-destructive': form.errors.address }
                            ]"
                        ></textarea>
                        <p v-if="form.errors.address" class="text-sm text-destructive">
                            {{ form.errors.address }}
                        </p>
                    </div>
                </div>

                <Separator />

                <!-- Role Management -->
                <div class="space-y-4">
                    <h3 class="text-lg font-medium">Role & Permissions</h3>

                    <div class="space-y-3">
                        <Label>User Role</Label>
                        <div class="space-y-2">
                            <label class="flex items-center space-x-2 cursor-pointer">
                                <input
                                    type="radio"
                                    :value="false"
                                    v-model="form.is_admin"
                                    class="rounded-full border-border"
                                />
                                <User class="h-4 w-4" />
                                <span>Regular User</span>
                            </label>
                            <label class="flex items-center space-x-2 cursor-pointer">
                                <input
                                    type="radio"
                                    :value="true"
                                    v-model="form.is_admin"
                                    class="rounded-full border-border"
                                />
                                <Crown class="h-4 w-4" />
                                <span>Administrator</span>
                            </label>
                        </div>
                        <p v-if="form.errors.is_admin" class="text-sm text-destructive">
                            {{ form.errors.is_admin }}
                        </p>
                    </div>
                </div>

            </form>

            <!-- Display Mode -->
            <div v-else class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="text-sm font-medium text-muted-foreground">Display Name</label>
                        <p class="text-sm font-medium">{{ user.name }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-muted-foreground">Email Address</label>
                        <p class="text-sm">{{ user.email }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-muted-foreground">First Name</label>
                        <p class="text-sm">{{ user.customer?.first_name || 'Not provided' }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-muted-foreground">Last Name</label>
                        <p class="text-sm">{{ user.customer?.last_name || 'Not provided' }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-muted-foreground">Phone Number</label>
                        <p class="text-sm">{{ user.customer?.phone || 'Not provided' }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-muted-foreground">Address</label>
                        <p class="text-sm">{{ user.customer?.address || 'Not provided' }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-muted-foreground">Account Created</label>
                        <p class="text-sm">{{ formatDate(user.created_at) }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-muted-foreground">Last Active</label>
                        <p class="text-sm">
                            {{ user.last_active_at ? formatDate(user.last_active_at) : 'Never logged in' }}
                        </p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-muted-foreground">Email Verified</label>
                        <div class="flex items-center space-x-2">
                            <component :is="user.email_verified_at ? CheckCircle : XCircle"
                                       :class="user.email_verified_at ? 'text-green-600' : 'text-red-600'"
                                       class="h-4 w-4" />
                            <span class="text-sm">
                                {{ user.email_verified_at ? 'Verified' : 'Not Verified' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

        </CardContent>
    </Card>
</template>
