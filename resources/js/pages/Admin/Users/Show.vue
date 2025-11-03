<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import UserProfileHeader from '@/components/admin/UserProfileHeader.vue';
import UserInformationForm from '@/components/admin/UserInformationForm.vue';
import UserOrders from '@/components/admin/UserOrders.vue';
import UserStatsSidebar from '@/components/admin/UserStatsSidebar.vue';
import SuccessAlert from '@/components/admin/SuccessAlert.vue';
import { ref } from 'vue';
import AdminLayout from '@/layouts/AdminLayout.vue';

const { user } = defineProps({
    user: { type: Object, required: true }
});

// State
const isEditing = ref(false);

// FIXED: Define breadcrumbs
const breadcrumbs = [
    { label: 'Users', href: '/admin/users' },
    { label: user.name, isActive: true }
];

// Form for comprehensive user updates
const form = useForm({
    name: user.name || '',
    email: user.email || '',
    password: '',
    password_confirmation: '',
    is_admin: user.is_admin || false,
    first_name: user.customer?.first_name || '',
    last_name: user.customer?.last_name || '',
    phone: user.customer?.phone || '',
    address: user.customer?.address || ''
});

// Actions
const toggleEdit = () => {
    if (isEditing.value) {
        // Cancel editing - reset form
        form.reset();
        form.clearErrors();
    }
    isEditing.value = !isEditing.value;
};

const updateUser = () => {
    form.patch(`/admin/users/${user.id}`, {
        onSuccess: () => {
            isEditing.value = false;
        },
        preserveScroll: true
    });
};

const deleteUser = () => {
    if (confirm(`Are you sure you want to delete user "${user.name}"? This action cannot be undone.`)) {
        router.delete(`/admin/users/${user.id}`, {
            onSuccess: () => {
                router.visit('/admin/users');
            }
        });
    }
};

const sendEmail = () => {
    window.location.href = `mailto:${user.email}`;
};
</script>

<template>
    <AdminLayout
        :title="`User: ${user.name}`"
        :breadcrumbs="breadcrumbs"
    >
        <!-- User Profile Header Component -->
        <UserProfileHeader
            :user="user"
            :is-editing="isEditing"
            @toggle-edit="toggleEdit"
            @send-email="sendEmail"
            @delete-user="deleteUser"
        />

        <div class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

            <!-- Success Alert Component -->
            <SuccessAlert />

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

                <!-- Main Content -->
                <div class="lg:col-span-2 space-y-8">

                    <!-- User Information Form Component -->
                    <UserInformationForm
                        :user="user"
                        :form="form"
                        :is-editing="isEditing"
                        locale="en-US"
                        @submit="updateUser"
                    />

                    <!-- User Orders Component -->
                    <UserOrders
                        :orders="user.customer?.orders || []"
                        :has-customer="!!user.customer"
                        currency="USD"
                        locale="en-US"
                    />

                </div>

                <!-- Sidebar -->
                <div class="lg:col-span-1">
                    <UserStatsSidebar
                        :user="user"
                        currency="USD"
                        locale="en-US"
                    />
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
