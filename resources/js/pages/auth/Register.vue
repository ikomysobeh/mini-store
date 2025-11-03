<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent } from '@/components/ui/card';
import { Head, useForm } from '@inertiajs/vue3';
import { LoaderCircle, Eye, EyeOff, Heart } from 'lucide-vue-next';
import { ref } from 'vue';

// IMPORTANT: Do NOT import any admin components here!
// import NotificationBell from '@/components/admin/NotificationBell.vue'; // ❌ REMOVE THIS
// import AdminLayout from '@/layouts/AdminLayout.vue'; // ❌ REMOVE THIS

defineProps<{
    logoUrl?: string;
    siteName?: string;
}>();

// Form handling with Inertia useForm
const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const showPassword = ref(false);
const showPasswordConfirmation = ref(false);

// Submit function - posts to Fortify's /register route
const submit = () => {
    form.post('/register', {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <div class="flex min-h-screen flex-col items-center justify-center p-6 md:p-10">
        <Head title="Register" />

        <div class="w-full max-w-sm">
            <div class="flex flex-col space-y-2 text-center mb-8">
                <!-- Logo -->
                <div class="mx-auto mb-4">
                    <div v-if="logoUrl" class="w-10 h-10 rounded-md overflow-hidden">
                        <img :src="logoUrl" :alt="siteName || 'Logo'" class="w-full h-full object-cover" />
                    </div>
                    <div v-else class="w-10 h-10 bg-primary rounded-md flex items-center justify-center">
                        <Heart class="h-5 w-5 text-primary-foreground" />
                    </div>
                </div>

                <h1 class="text-2xl font-semibold tracking-tight">Create an account</h1>
                <p class="text-sm text-muted-foreground">
                    Enter your details below to create your account
                </p>
            </div>

            <!-- Form -->
            <div class="grid gap-6">
                <form @submit.prevent="submit" class="grid gap-4">
                    <!-- Name -->
                    <div class="grid gap-2">
                        <Label for="name">Full Name</Label>
                        <Input
                            id="name"
                            v-model="form.name"
                            type="text"
                            placeholder="John Doe"
                            autocomplete="name"
                            required
                            autofocus
                            :disabled="form.processing"
                        />
                        <p v-if="form.errors.name" class="text-sm text-destructive">
                            {{ form.errors.name }}
                        </p>
                    </div>

                    <!-- Email -->
                    <div class="grid gap-2">
                        <Label for="email">Email</Label>
                        <Input
                            id="email"
                            v-model="form.email"
                            type="email"
                            placeholder="name@example.com"
                            autocomplete="email"
                            required
                            :disabled="form.processing"
                        />
                        <p v-if="form.errors.email" class="text-sm text-destructive">
                            {{ form.errors.email }}
                        </p>
                    </div>

                    <!-- Password -->
                    <div class="grid gap-2">
                        <Label for="password">Password</Label>
                        <div class="relative">
                            <Input
                                id="password"
                                v-model="form.password"
                                :type="showPassword ? 'text' : 'password'"
                                placeholder="Create a password"
                                autocomplete="new-password"
                                required
                                :disabled="form.processing"
                                class="pr-10"
                            />
                            <Button
                                type="button"
                                variant="ghost"
                                size="sm"
                                @click="showPassword = !showPassword"
                                class="absolute right-0 top-0 h-full px-3 hover:bg-transparent"
                            >
                                <Eye v-if="!showPassword" class="h-4 w-4" />
                                <EyeOff v-else class="h-4 w-4" />
                            </Button>
                        </div>
                        <p v-if="form.errors.password" class="text-sm text-destructive">
                            {{ form.errors.password }}
                        </p>
                    </div>

                    <!-- Confirm Password -->
                    <div class="grid gap-2">
                        <Label for="password_confirmation">Confirm Password</Label>
                        <div class="relative">
                            <Input
                                id="password_confirmation"
                                v-model="form.password_confirmation"
                                :type="showPasswordConfirmation ? 'text' : 'password'"
                                placeholder="Confirm your password"
                                autocomplete="new-password"
                                required
                                :disabled="form.processing"
                                class="pr-10"
                            />
                            <Button
                                type="button"
                                variant="ghost"
                                size="sm"
                                @click="showPasswordConfirmation = !showPasswordConfirmation"
                                class="absolute right-0 top-0 h-full px-3 hover:bg-transparent"
                            >
                                <Eye v-if="!showPasswordConfirmation" class="h-4 w-4" />
                                <EyeOff v-else class="h-4 w-4" />
                            </Button>
                        </div>
                        <p v-if="form.errors.password_confirmation" class="text-sm text-destructive">
                            {{ form.errors.password_confirmation }}
                        </p>
                    </div>

                    <!-- Submit Button -->
                    <Button type="submit" class="w-full" :disabled="form.processing">
                        <LoaderCircle v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
                        Create account
                    </Button>
                </form>

                <!-- Login Link -->
                <div class="text-center text-sm text-muted-foreground">
                    Already have an account?
                    <a href="/login" class="underline underline-offset-4 hover:text-primary">
                        Sign in
                    </a>
                </div>
            </div>
        </div>
    </div>
</template>
