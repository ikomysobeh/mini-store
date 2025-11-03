<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { ArrowLeft, Mail, Edit, X, Trash2, User, Crown } from 'lucide-vue-next';

interface User {
    id: number;
    name: string;
    email: string;
    is_admin: boolean;
    avatar?: string;
}

interface Props {
    user: User;
    isEditing: boolean;
}

interface Emits {
    (e: 'toggleEdit'): void;
    (e: 'sendEmail'): void;
    (e: 'deleteUser'): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

const getUserInitials = (userData: User) => {
    if (userData.name) {
        return userData.name.split(' ')
            .map(n => n[0])
            .join('')
            .toUpperCase()
            .substring(0, 2);
    }
    return userData.email?.charAt(0).toUpperCase() || 'U';
};

const getRoleBadgeColor = (isAdmin: boolean) => {
    return isAdmin
        ? 'bg-purple-100 text-purple-800 border-purple-200'
        : 'bg-blue-100 text-blue-800 border-blue-200';
};

const getRoleIcon = (isAdmin: boolean) => {
    return isAdmin ? Crown : User;
};
</script>

<template>
    <div class="border-b bg-background">
        <div class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">

                <!-- Left Side -->
                <div class="flex items-center space-x-4">
                    <Button variant="ghost" as="a" href="/admin/users" class="p-2">
                        <ArrowLeft class="h-5 w-5" />
                    </Button>
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
                            <h1 class="text-2xl font-bold text-foreground flex items-center space-x-2">
                                <span>{{ user.name }}</span>
                                <Badge
                                    :class="getRoleBadgeColor(user.is_admin)"
                                    class="text-xs px-2 py-1 border"
                                >
                                    <component :is="getRoleIcon(user.is_admin)" class="h-3 w-3 mr-1" />
                                    {{ user.is_admin ? 'Administrator' : 'User' }}
                                </Badge>
                            </h1>
                            <p class="text-sm text-muted-foreground">
                                {{ user.email }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Right Side - Actions -->
                <div class="flex items-center space-x-3">
                    <Button variant="outline" size="sm" @click="emit('sendEmail')">
                        <Mail class="h-4 w-4 mr-2" />
                        Send Email
                    </Button>
                    <Button variant="outline" size="sm" @click="emit('toggleEdit')">
                        <Edit v-if="!isEditing" class="h-4 w-4 mr-2" />
                        <X v-else class="h-4 w-4 mr-2" />
                        {{ isEditing ? 'Cancel' : 'Edit User' }}
                    </Button>
                    <Button variant="destructive" size="sm" @click="emit('deleteUser')">
                        <Trash2 class="h-4 w-4 mr-2" />
                        Delete User
                    </Button>
                </div>
            </div>
        </div>
    </div>
</template>
