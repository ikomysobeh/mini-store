<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import PageHeader from '@/components/admin/PageHeader.vue';
import UserStats from '@/components/admin/UserStats.vue';
import UserFilters from '@/components/admin/UserFilters.vue';
import BulkActions from '@/components/admin/BulkActions.vue';
import UsersTable from '@/components/admin/UsersTable.vue';
import Pagination from '@/components/admin/Pagination.vue';
import SuccessAlert from '@/components/admin/SuccessAlert.vue';
import { Users, Filter, FileSpreadsheet, RefreshCw, Trash2 } from 'lucide-vue-next';
import { ref, computed, watch } from 'vue';
import AdminLayout from '@/layouts/AdminLayout.vue';

const { users, filters } = defineProps({
    users: {
        type: Object,
        required: true
    },
    filters: {
        type: Object,
        default: () => ({})
    }
});

// State
const searchTerm = ref(filters.search || '');
const selectedRole = ref(filters.is_admin !== undefined ? filters.is_admin : '');
const selectedUsers = ref([]);
const showFilters = ref(false);

// Computed
const totalUsers = computed(() => users.total || users.data.length);

// Header actions
const headerActions = [
    {
        label: showFilters.value ? 'Hide' : 'Show' + ' Filters',
        icon: Filter,
        variant: 'outline',
        onClick: () => { showFilters.value = !showFilters.value; }
    },
    {
        label: 'Refresh',
        icon: RefreshCw,
        variant: 'outline',
        onClick: () => applyFilters()
    },
    {
        label: 'Add User',
        href: '/admin/users/create',
    }
];

// Bulk actions
const bulkActions = [
    {
        label: 'Delete Selected',
        icon: Trash2,
        variant: 'destructive',
        destructive: true,
        onClick: bulkDelete
    }
];

// Actions
function applyFilters() {
    const params = {};

    if (searchTerm.value) params.search = searchTerm.value;
    if (selectedRole.value !== '') params.is_admin = selectedRole.value;

    router.get('/admin/users', params, {
        preserveState: true,
        preserveScroll: true,
    });
}

function clearFilters() {
    searchTerm.value = '';
    selectedRole.value = '';
    router.get('/admin/users');
}

function toggleSort(column: string) {
    // Add sorting logic if needed
    console.log('Sort by:', column);
    applyFilters();
}

function updateUserRole(userId: number, isAdmin: boolean) {
    const user = users.data.find(u => u.id === userId);
    if (!user) return;

    router.patch(`/admin/users/${userId}`, {
        name: user.name,
        email: user.email,
        is_admin: isAdmin,
        first_name: user.customer?.first_name || '',
        last_name: user.customer?.last_name || '',
        phone: user.customer?.phone || '',
        address: user.customer?.address || ''
    }, {
        preserveScroll: true
    });
}

function deleteUser(userId: number, userName: string) {
    if (confirm(`Are you sure you want to delete user "${userName}"? This action cannot be undone.`)) {
        router.delete(`/admin/users/${userId}`, {
            preserveScroll: true
        });
    }
}

function bulkDelete() {
    if (selectedUsers.value.length === 0) return;

    if (confirm(`Are you sure you want to delete ${selectedUsers.value.length} users? This action cannot be undone.`)) {
        selectedUsers.value.forEach(userId => {
            router.delete(`/admin/users/${userId}`, {
                preserveScroll: true,
                onSuccess: () => {
                    selectedUsers.value = selectedUsers.value.filter(id => id !== userId);
                }
            });
        });
    }
}

function goToPage(url: string) {
    if (url) {
        router.visit(url, {
            preserveState: true,
            preserveScroll: false,
        });
    }
}

function exportUsers() {
    const params = new URLSearchParams();
    if (searchTerm.value) params.append('search', searchTerm.value);
    if (selectedRole.value !== '') params.append('is_admin', selectedRole.value);

    window.open(`/admin/users/export?${params.toString()}`);
}

function cancelBulkSelection() {
    selectedUsers.value = [];
}

// Watch for search changes with debounce
let searchTimeout;
watch(searchTerm, (newValue) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        applyFilters();
    }, 500);
});

// Watch for role filter changes
watch(selectedRole, () => {
    applyFilters();
});

// Update header actions when showFilters changes
watch(showFilters, (newValue) => {
    headerActions[0].label = newValue ? 'Hide Filters' : 'Show Filters';
});
</script>

<template>
    <AdminLayout
        title="Orders Management"
        :breadcrumbs="breadcrumbs"
    >
    <div class="min-h-screen bg-muted/20">
        <Head title="Users Management" />

        <!-- Page Header Component -->
        <PageHeader
            title="Users Management"
            description="Manage user accounts, profiles and permissions"
            :icon="Users"
            icon-color="text-green-600"
            :actions="headerActions"
        />

        <div class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

            <!-- User Stats Component -->
            <UserStats
                :users="users.data"
                :total-users="totalUsers"
            />

            <!-- Success Alert Component -->
            <SuccessAlert />

            <!-- User Filters Component -->
            <UserFilters
                v-model:search-term="searchTerm"
                v-model:selected-role="selectedRole"
                :show-filters="showFilters"
                @clear-filters="clearFilters"
                @apply-filters="applyFilters"
            />

            <!-- Bulk Actions Component -->
            <BulkActions
                :selected-count="selectedUsers.length"
                item-name="user"
                :actions="bulkActions"
                @cancel="cancelBulkSelection"
            />

            <!-- Users Table Component -->
            <UsersTable
                :users="users.data"
                v-model:selected-users="selectedUsers"
                :search-term="searchTerm"
                :selected-role="selectedRole"
                locale="en-US"
                @toggle-sort="toggleSort"
                @update-user-role="updateUserRole"
                @delete-user="deleteUser"
                @clear-filters="clearFilters"
                @export-users="exportUsers"
            />

            <!-- Pagination Component -->
            <Pagination
                :links="users.links"
                @go-to-page="goToPage"
            />

            <!-- Table Info -->
            <div class="flex justify-between items-center mt-4 text-sm text-muted-foreground">
                <p>
                    Showing {{ users.from || 1 }} to {{ users.to || users.data.length }}
                    of {{ users.total || users.data.length }} users
                </p>
                <p>
                    {{ selectedUsers.length }} selected
                </p>
            </div>

        </div>
    </div>
    </AdminLayout>
</template>
