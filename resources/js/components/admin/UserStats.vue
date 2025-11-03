<script setup lang="ts">
import { Card, CardContent } from '@/components/ui/card';
import { Users, Crown, User, CheckCircle } from 'lucide-vue-next';

interface User {
    is_admin: boolean;
    email_verified_at: string | null;
}

interface Props {
    users: User[];
    totalUsers: number;
}

const { users, totalUsers } = defineProps<Props>();

const adminUsers = users.filter(user => user.is_admin).length;
const regularUsers = users.filter(user => !user.is_admin).length;
const verifiedUsers = users.filter(user => user.email_verified_at).length;

const stats = [
    {
        label: 'Total Users',
        value: totalUsers,
        icon: Users,
        color: 'blue'
    },
    {
        label: 'Administrators',
        value: adminUsers,
        icon: Crown,
        color: 'purple'
    },
    {
        label: 'Regular Users',
        value: regularUsers,
        icon: User,
        color: 'green'
    },
    {
        label: 'Email Verified',
        value: verifiedUsers,
        icon: CheckCircle,
        color: 'emerald'
    }
];

const getColorClasses = (color: string) => {
    const colors = {
        blue: 'bg-blue-100 text-blue-600',
        purple: 'bg-purple-100 text-purple-600',
        green: 'bg-green-100 text-green-600',
        emerald: 'bg-emerald-100 text-emerald-600'
    };
    return colors[color] || colors.blue;
};
</script>

<template>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <Card v-for="stat in stats" :key="stat.label">
            <CardContent class="p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-muted-foreground">{{ stat.label }}</p>
                        <p class="text-2xl font-bold">{{ stat.value.toLocaleString() }}</p>
                    </div>
                    <div :class="`p-3 rounded-full ${getColorClasses(stat.color)}`">
                        <component :is="stat.icon" class="h-5 w-5" />
                    </div>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
