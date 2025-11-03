<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import UserForm from '@/components/admin/UserForm.vue';
import { ArrowLeft, UserPlus } from 'lucide-vue-next';

// Form data
const form = useForm({
    // User fields
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    is_admin: false,

    // Customer fields
    first_name: '',
    last_name: '',
    phone: '',
    address: '',
});

// Header actions
const headerActions = [
    {
        label: 'Back to Users',
        href: '/admin/users',
        icon: ArrowLeft,
        variant: 'outline'
    }
];

// Form submission
const submitForm = () => {
    form.post('/admin/users', {
        preserveScroll: true,
    });
};
</script>

<template>
    <AdminLayout title="Create User">
        <Head title="Create User" />

        <!-- Page Header -->
        <PageHeader
            title="Create New User"
            description="Add a new user with customer profile"
            :icon="UserPlus"
            icon-color="text-green-600"
            :actions="headerActions"
        />

        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

            <!-- User Form Component -->
            <UserForm
                :form="form"
                :is-editing="false"
                @submit="submitForm"
            />

        </div>
    </AdminLayout>
</template>
