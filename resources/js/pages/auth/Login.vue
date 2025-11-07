<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent } from '@/components/ui/card';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Head, useForm } from '@inertiajs/vue3';
import { LoaderCircle, Eye, EyeOff, Heart } from 'lucide-vue-next';
import { ref } from 'vue';

defineProps<{
    status?: string;
    canResetPassword: boolean;
    canRegister: boolean;
    logoUrl?: string;
    siteName?: string;
}>();

// Form handling with Inertia useForm
const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const showPassword = ref(false);

function togglePassword() {
    showPassword.value = !showPassword.value;
}

// Submit function
const submit = () => {
    form.post('/login', {
        onFinish: () => form.reset('password'),
        onSuccess: () => {
            console.log('Login successful');
        },
        onError: (errors) => {
            console.log('Login errors:', errors);
        },
    });
};
</script>

<template>
    <div class="min-h-screen flex items-center justify-center bg-background p-4">
        <Head title="Log in" />

        <div class="w-full max-w-sm">
            <!-- Logo and Brand Section -->
            <div class="flex flex-col items-center space-y-6 mb-8">
                <div class="flex items-center justify-center">
                    <div v-if="logoUrl" class="w-12 h-12 rounded-lg overflow-hidden">
                        <img :src="logoUrl" :alt="siteName || 'Logo'" class="w-full h-full object-cover" />
                    </div>
                    <div v-else class="w-12 h-12 bg-primary rounded-lg flex items-center justify-center">
                        <Heart class="h-6 w-6 text-primary-foreground" />
                    </div>
                </div>

                <div class="text-center space-y-2">
                    <h1 class="text-2xl font-semibold">Welcome back</h1>
                    <p class="text-sm text-muted-foreground">
                        Enter your email below to sign in to your account
                    </p>
                </div>
            </div>

            <!-- Login Card -->
            <Card>
                <CardContent class="p-6">
                    <!-- Status Message -->
                    <Alert v-if="status" class="mb-4">
                        <AlertDescription>{{ status }}</AlertDescription>
                    </Alert>

                    <form @submit.prevent="submit" class="space-y-4">
                        <!-- Email Field -->
                        <div class="space-y-2">
                            <Label for="email">Email</Label>
                            <Input
                                id="email"
                                v-model="form.email"
                                type="email"
                                placeholder="m@example.com"
                                required
                                autofocus
                                :disabled="form.processing"
                            />
                            <div v-if="form.errors.email" class="text-sm text-destructive">
                                {{ form.errors.email }}
                            </div>
                        </div>

                        <!-- Password Field -->
                        <div class="space-y-2">
                        
                            <div class="relative">
                                <Input
                                    id="password"
                                    v-model="form.password"
                                    :type="showPassword ? 'text' : 'password'"
                                    required
                                    :disabled="form.processing"
                                    class="pr-10"
                                />
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    @click="togglePassword"
                                    class="absolute right-0 top-0 h-full px-3"
                                    :disabled="form.processing"
                                >
                                    <Eye v-if="!showPassword" class="h-4 w-4" />
                                    <EyeOff v-else class="h-4 w-4" />
                                </Button>
                            </div>
                            <div v-if="form.errors.password" class="text-sm text-destructive">
                                {{ form.errors.password }}
                            </div>
                        </div>

                        <!-- Remember Me -->
                        <div class="flex items-center space-x-2">
                            <Checkbox
                                id="remember"
                                v-model:checked="form.remember"
                                :disabled="form.processing"
                            />
                            <Label for="remember" class="text-sm cursor-pointer">
                                Remember me
                            </Label>
                        </div>

                        <!-- Submit Button -->
                        <Button type="submit" class="w-full" :disabled="form.processing">
                            <LoaderCircle v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
                            Sign In
                        </Button>
                    </form>
                </CardContent>
            </Card>

            <!-- Register Link -->
            <div v-if="canRegister" class="mt-4 text-center text-sm">
                Don't have an account?
                <a href="/register" class="underline underline-offset-4 hover:text-primary">
                    Sign up
                </a>
            </div>
        </div>
    </div>
</template>
