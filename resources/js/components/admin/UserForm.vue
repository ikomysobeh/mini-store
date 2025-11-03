<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Separator } from '@/components/ui/separator';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { User, Shield, Phone, MapPin, Mail, Lock, AlertCircle } from 'lucide-vue-next';
import { computed } from 'vue';

interface Props {
    form: any;
    isEditing: boolean;
}

interface Emits {
    (e: 'submit'): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

// Form validation
const isFormValid = computed(() => {
    return props.form.name &&
        props.form.email &&
        props.form.first_name &&
        props.form.last_name &&
        (!props.isEditing ? props.form.password : true) &&
        (!props.isEditing ? props.form.password_confirmation : true);
});

const submitForm = () => {
    if (isFormValid.value) {
        emit('submit');
    }
};
</script>

<template>
    <form @submit.prevent="submitForm" class="space-y-6">

        <!-- Account Information -->
        <Card>
            <CardHeader>
                <CardTitle class="flex items-center space-x-2">
                    <User class="h-5 w-5" />
                    <span>Account Information</span>
                </CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">

                <!-- Username -->
                <div class="space-y-2">
                    <Label for="name">Username *</Label>
                    <Input
                        id="name"
                        v-model="form.name"
                        :class="{ 'border-red-500': form.errors.name }"
                        placeholder="Enter username"
                        required
                    />
                    <p v-if="form.errors.name" class="text-sm text-red-600">{{ form.errors.name }}</p>
                </div>

                <!-- Email -->
                <div class="space-y-2">
                    <Label for="email" class="flex items-center space-x-2">
                        <Mail class="h-4 w-4" />
                        <span>Email Address *</span>
                    </Label>
                    <Input
                        id="email"
                        v-model="form.email"
                        type="email"
                        :class="{ 'border-red-500': form.errors.email }"
                        placeholder="Enter email address"
                        required
                    />
                    <p v-if="form.errors.email" class="text-sm text-red-600">{{ form.errors.email }}</p>
                </div>

                <!-- Password -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <Label for="password" class="flex items-center space-x-2">
                            <Lock class="h-4 w-4" />
                            <span>Password {{ isEditing ? '(leave blank to keep current)' : '*' }}</span>
                        </Label>
                        <Input
                            id="password"
                            v-model="form.password"
                            type="password"
                            :class="{ 'border-red-500': form.errors.password }"
                            placeholder="Enter password"
                            :required="!isEditing"
                        />
                        <p v-if="form.errors.password" class="text-sm text-red-600">{{ form.errors.password }}</p>
                    </div>

                    <div class="space-y-2">
                        <Label for="password_confirmation">Confirm Password {{ isEditing ? '' : '*' }}</Label>
                        <Input
                            id="password_confirmation"
                            v-model="form.password_confirmation"
                            type="password"
                            placeholder="Confirm password"
                            :required="!isEditing && form.password"
                        />
                    </div>
                </div>

                <!-- Admin Role -->
                <div class="flex items-center space-x-3 p-4 bg-muted/50 rounded-lg">
                    <Checkbox
                        id="is_admin"
                        :checked="form.is_admin"
                        @update:checked="form.is_admin = $event"
                    />
                    <Label for="is_admin" class="flex items-center space-x-2 cursor-pointer">
                        <Shield class="h-4 w-4 text-orange-600" />
                        <span>Admin Access</span>
                    </Label>
                </div>

                <Alert v-if="form.is_admin">
                    <AlertCircle class="h-4 w-4" />
                    <AlertDescription>
                        Admin users have full access to the admin panel and can manage all system functions.
                    </AlertDescription>
                </Alert>

            </CardContent>
        </Card>

        <Separator />

        <!-- Customer Profile -->
        <Card>
            <CardHeader>
                <CardTitle class="flex items-center space-x-2">
                    <User class="h-5 w-5" />
                    <span>Customer Profile</span>
                </CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">

                <!-- Name Fields -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <Label for="first_name">First Name *</Label>
                        <Input
                            id="first_name"
                            v-model="form.first_name"
                            :class="{ 'border-red-500': form.errors.first_name }"
                            placeholder="Enter first name"
                            required
                        />
                        <p v-if="form.errors.first_name" class="text-sm text-red-600">{{ form.errors.first_name }}</p>
                    </div>

                    <div class="space-y-2">
                        <Label for="last_name">Last Name *</Label>
                        <Input
                            id="last_name"
                            v-model="form.last_name"
                            :class="{ 'border-red-500': form.errors.last_name }"
                            placeholder="Enter last name"
                            required
                        />
                        <p v-if="form.errors.last_name" class="text-sm text-red-600">{{ form.errors.last_name }}</p>
                    </div>
                </div>

                <!-- Contact Information -->
                <div class="space-y-2">
                    <Label for="phone" class="flex items-center space-x-2">
                        <Phone class="h-4 w-4" />
                        <span>Phone Number</span>
                    </Label>
                    <Input
                        id="phone"
                        v-model="form.phone"
                        type="tel"
                        :class="{ 'border-red-500': form.errors.phone }"
                        placeholder="Enter phone number"
                    />
                    <p v-if="form.errors.phone" class="text-sm text-red-600">{{ form.errors.phone }}</p>
                </div>

                <!-- Address -->
                <div class="space-y-2">
                    <Label for="address" class="flex items-center space-x-2">
                        <MapPin class="h-4 w-4" />
                        <span>Address</span>
                    </Label>
                    <textarea
                        id="address"
                        v-model="form.address"
                        :class="{ 'border-red-500': form.errors.address }"
                        placeholder="Enter full address in Suwayda (neighborhood, street, building details)"
                        rows="3"
                        class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                    ></textarea>
                    <p v-if="form.errors.address" class="text-sm text-red-600">{{ form.errors.address }}</p>
                </div>

            </CardContent>
        </Card>

        <!-- Form Actions -->
        <div class="flex justify-end space-x-4 pt-6">
            <Button
                type="button"
                variant="outline"
                :disabled="form.processing"
                @click="$inertia.visit('/admin/users')"
            >
                Cancel
            </Button>

            <Button
                type="submit"
                :disabled="form.processing || !isFormValid"
                class="min-w-[120px]"
            >
                <div v-if="form.processing" class="flex items-center">
                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    {{ isEditing ? 'Updating...' : 'Creating...' }}
                </div>
                <span v-else>
                    {{ isEditing ? 'Update User' : 'Create User' }}
                </span>
            </Button>
        </div>

    </form>
</template>
