<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import {
    Users, Eye, ArrowUpDown, CheckCircle, XCircle, Phone, MapPin,
    Crown, User, Trash2, UserPlus, FileSpreadsheet
} from 'lucide-vue-next';
import { computed } from 'vue';

interface User {
    id: number;
    name: string;
    email: string;
    is_admin: boolean;
    email_verified_at: string | null;
    avatar?: string;
    created_at: string;
    last_active_at?: string;
    customer?: {
        first_name?: string;
        last_name?: string;
        phone?: string;
        address?: string;
        orders_count?: number;
        total_spent?: number;
        created_at?: string;
    };
}

interface Props {
    users: User[];
    selectedUsers: number[];
    searchTerm?: string;
    selectedRole?: string;
    locale?: string;
}

interface Emits {
    (e: 'update:selectedUsers', value: number[]): void;
    (e: 'toggleSort', column: string): void;
    (e: 'updateUserRole', userId: number, isAdmin: boolean): void;
    (e: 'deleteUser', userId: number, userName: string): void;
    (e: 'clearFilters'): void;
    (e: 'exportUsers'): void;
}

const props = withDefaults(defineProps<Props>(), {
    locale: 'nl-NL'
});

const emit = defineEmits<Emits>();

// Helper functions
const formatDate = (date: string) => {
    if (!date) return 'Never';
    return new Date(date).toLocaleDateString(props.locale, {
        day: '2-digit',
        month: 'short',
        year: 'numeric'
    });
};

const formatDateTime = (date: string) => {
    if (!date) return 'Never';
    return new Date(date).toLocaleDateString(props.locale, {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const getUserInitials = (user: User) => {
    if (user.name) {
        return user.name.split(' ')
            .map(n => n[0])
            .join('')
            .toUpperCase()
            .substring(0, 2);
    }
    return user.email?.charAt(0).toUpperCase() || 'U';
};

const getFullName = (user: User) => {
    if (user.customer?.first_name && user.customer?.last_name) {
        return `${user.customer.first_name} ${user.customer.last_name}`;
    }
    return user.name;
};

const getRoleBadgeColor = (isAdmin: boolean) => {
    return isAdmin
        ? 'bg-purple-100 text-purple-800 border-purple-200'
        : 'bg-blue-100 text-blue-800 border-blue-200';
};

const getRoleIcon = (isAdmin: boolean) => {
    return isAdmin ? Crown : User;
};

const getVerificationColor = (isVerified: boolean) => {
    return isVerified ? 'text-green-600' : 'text-red-600';
};

const getCustomerCompletionScore = (user: User) => {
    if (!user.customer) return 0;

    let score = 0;
    const fields = ['first_name', 'last_name', 'phone', 'address'];
    fields.forEach(field => {
        if (user.customer?.[field]) score += 25;
    });
    return score;
};

// Computed
const isAllSelected = computed(() => {
    return props.users.length > 0 && props.selectedUsers.length === props.users.length;
});

const isIndeterminate = computed(() => {
    return props.selectedUsers.length > 0 && props.selectedUsers.length < props.users.length;
});

const hasFilters = computed(() => {
    return props.searchTerm || props.selectedRole !== '';
});

// Event handlers
const toggleSelectAll = () => {
    if (isAllSelected.value) {
        emit('update:selectedUsers', []);
    } else {
        emit('update:selectedUsers', props.users.map(user => user.id));
    }
};

const toggleUser = (userId: number) => {
    const currentSelected = [...props.selectedUsers];
    const index = currentSelected.indexOf(userId);

    if (index > -1) {
        currentSelected.splice(index, 1);
    } else {
        currentSelected.push(userId);
    }

    emit('update:selectedUsers', currentSelected);
};
</script>

<template>
    <Card class="shadow-sm">
        <CardContent class="p-0">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <!-- Table Header -->
                    <thead class="border-b bg-muted/30">
                    <tr>
                        <th class="p-4 text-left">
                            <input
                                type="checkbox"
                                :checked="isAllSelected"
                                :indeterminate="isIndeterminate"
                                @change="toggleSelectAll"
                                class="rounded border-border"
                            />
                        </th>
                        <th class="p-4 text-left">
                            <button
                                @click="emit('toggleSort', 'name')"
                                class="flex items-center space-x-1 hover:text-primary font-medium group"
                            >
                                <span>User</span>
                                <ArrowUpDown class="h-4 w-4 opacity-0 group-hover:opacity-100 transition-opacity" />
                            </button>
                        </th>
                        <th class="p-4 text-left font-medium">Profile Info</th>
                        <th class="p-4 text-left font-medium">Role & Status</th>
                        <th class="p-4 text-left font-medium">Customer Data</th>
                        <th class="p-4 text-left">
                            <button
                                @click="emit('toggleSort', 'created_at')"
                                class="flex items-center space-x-1 hover:text-primary font-medium group"
                            >
                                <span>Joined</span>
                                <ArrowUpDown class="h-4 w-4 opacity-0 group-hover:opacity-100 transition-opacity" />
                            </button>
                        </th>
                        <th class="p-4 text-right font-medium">Actions</th>
                    </tr>
                    </thead>

                    <!-- Table Body -->
                    <tbody>
                    <tr
                        v-for="user in users"
                        :key="user.id"
                        class="border-b hover:bg-muted/30 transition-colors"
                    >
                        <!-- Checkbox -->
                        <td class="p-4">
                            <input
                                type="checkbox"
                                :checked="selectedUsers.includes(user.id)"
                                @change="toggleUser(user.id)"
                                class="rounded border-border"
                            />
                        </td>

                        <!-- User Info -->
                        <td class="p-4">
                            <div class="flex items-center space-x-3">
                                <Avatar class="h-10 w-10">
                                    <AvatarImage
                                        v-if="user.avatar"
                                        :src="user.avatar"
                                        :alt="user.name"
                                    />
                                    <AvatarFallback class="bg-primary/10 text-primary">
                                        {{ getUserInitials(user) }}
                                    </AvatarFallback>
                                </Avatar>
                                <div>
                                    <p class="font-medium">{{ user.name }}</p>
                                    <div class="flex items-center space-x-2">
                                        <p class="text-sm text-muted-foreground">{{ user.email }}</p>
                                        <component
                                            :is="user.email_verified_at ? CheckCircle : XCircle"
                                            :class="getVerificationColor(!!user.email_verified_at)"
                                            class="h-3 w-3"
                                            :title="user.email_verified_at ? 'Email Verified' : 'Email Not Verified'"
                                        />
                                    </div>
                                </div>
                            </div>
                        </td>

                        <!-- Profile Info -->
                        <td class="p-4">
                            <div class="text-sm">
                                <p class="font-medium">
                                    {{ getFullName(user) }}
                                    <Badge v-if="user.name !== getFullName(user)" variant="outline" class="ml-1 text-xs">
                                        Full Profile
                                    </Badge>
                                </p>
                                <div class="text-muted-foreground space-y-0.5">
                                    <div v-if="user.customer?.phone" class="flex items-center space-x-1">
                                        <Phone class="h-3 w-3" />
                                        <span>{{ user.customer.phone }}</span>
                                    </div>
                                    <div v-if="user.customer?.address" class="flex items-center space-x-1">
                                        <MapPin class="h-3 w-3" />
                                        <span class="truncate max-w-32">{{ user.customer.address }}</span>
                                    </div>
                                </div>
                                <!-- Profile Completion -->
                                <div v-if="user.customer" class="mt-1">
                                    <div class="flex items-center space-x-2">
                                        <div class="w-16 bg-muted rounded-full h-1">
                                            <div
                                                class="bg-primary h-1 rounded-full transition-all"
                                                :style="{ width: getCustomerCompletionScore(user) + '%' }"
                                            ></div>
                                        </div>
                                        <span class="text-xs text-muted-foreground">
                                                {{ getCustomerCompletionScore(user) }}%
                                            </span>
                                    </div>
                                </div>
                            </div>
                        </td>

                        <!-- Role & Status -->
                        <td class="p-4">
                            <div class="space-y-2">
                                <Badge
                                    :class="getRoleBadgeColor(user.is_admin)"
                                    class="text-xs px-2 py-1 border flex items-center w-fit"
                                >
                                    <component
                                        :is="getRoleIcon(user.is_admin)"
                                        class="h-3 w-3 mr-1"
                                    />
                                    {{ user.is_admin ? 'Admin' : 'User' }}
                                </Badge>
                                <div class="text-xs text-muted-foreground">
                                    ID: #{{ user.id }}
                                </div>
                            </div>
                        </td>

                        <!-- Customer Data -->
                        <td class="p-4">
                            <div v-if="user.customer" class="text-sm">
                                <p class="font-medium">{{ user.customer.orders_count || 0 }} orders</p>
                                <p class="text-muted-foreground">
                                    Customer since {{ formatDate(user.customer.created_at || user.created_at) }}
                                </p>
                                <div v-if="user.customer.total_spent" class="text-xs text-green-600 font-medium">
                                    ${{ (user.customer.total_spent || 0).toLocaleString() }} spent
                                </div>
                            </div>
                            <div v-else class="text-sm">
                                <Badge variant="outline" class="text-xs">
                                    <UserPlus class="h-3 w-3 mr-1" />
                                    No Customer Profile
                                </Badge>
                            </div>
                        </td>

                        <!-- Joined Date -->
                        <td class="p-4">
                            <div class="text-sm">{{ formatDate(user.created_at) }}</div>
                            <div class="text-xs text-muted-foreground">
                                {{ formatDateTime(user.created_at) }}
                            </div>
                        </td>


                        <!-- Actions -->
                        <td class="p-4 text-right">
                            <div class="flex items-center justify-end space-x-2">
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    as="a"
                                    :href="`/admin/users/${user.id}`"
                                    title="View & Edit User"
                                    class="h-8 w-8 p-0"
                                >
                                    <Eye class="h-4 w-4" />
                                </Button>

                                <!-- Role Toggle -->

                                <Button
                                    variant="ghost"
                                    size="sm"
                                    @click="emit('deleteUser', user.id, user.name)"
                                    class="h-8 w-8 p-0 text-red-600 hover:text-red-700 hover:bg-red-50"
                                    title="Delete User"
                                >
                                    <Trash2 class="h-4 w-4" />
                                </Button>
                            </div>
                        </td>
                    </tr>
                    </tbody>
                </table>

                <!-- Empty State -->
                <div v-if="users.length === 0" class="text-center py-12">
                    <div class="w-20 h-20 bg-muted rounded-full flex items-center justify-center mx-auto mb-4">
                        <Users class="h-10 w-10 text-muted-foreground" />
                    </div>
                    <h3 class="text-lg font-semibold mb-2">No users found</h3>
                    <p class="text-muted-foreground mb-6">
                        {{ hasFilters ? 'Try adjusting your search filters' : 'No users have registered yet' }}
                    </p>
                    <div class="flex justify-center space-x-3">
                        <Button variant="outline" @click="emit('clearFilters')" v-if="hasFilters">
                            Clear Filters
                        </Button>
                        <Button @click="emit('exportUsers')">
                            <FileSpreadsheet class="h-4 w-4 mr-2" />
                            Export Data
                        </Button>
                    </div>
                </div>
            </div>
        </CardContent>
    </Card>
</template>

<style scoped>
/* Custom scrollbar for table */
.overflow-x-auto::-webkit-scrollbar {
    height: 8px;
}

.overflow-x-auto::-webkit-scrollbar-track {
    background: transparent;
}

.overflow-x-auto::-webkit-scrollbar-thumb {
    background: rgba(0, 0, 0, 0.2);
    border-radius: 4px;
}

.overflow-x-auto::-webkit-scrollbar-thumb:hover {
    background: rgba(0, 0, 0, 0.3);
}

/* Truncate long text */
.truncate {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

/* Profile completion bar */
.bg-primary {
    transition: width 0.3s ease-in-out;
}
</style>
