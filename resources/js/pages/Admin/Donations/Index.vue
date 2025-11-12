<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { 
  Heart, Search, Filter, Download, Trash2, Eye, 
  DollarSign, TrendingUp, Calendar, Users 
} from 'lucide-vue-next';

const props = defineProps({
  donations: Object,
  filters: Object,
  statuses: Array,
  stats: Object,
});

// State
const selectedDonations = ref<number[]>([]);
const searchQuery = ref(props.filters?.search || '');
const statusFilter = ref(props.filters?.status || 'all');
const dateRangeFilter = ref(props.filters?.date_range || 'all');

// Computed
const allSelected = computed(() => {
  return props.donations.data.length > 0 && 
         selectedDonations.value.length === props.donations.data.length;
});

// Methods
const formatPrice = (amount: number) => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD'
  }).format(amount);
};

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString('en-US', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};

const getStatusColor = (status: string) => {
  const colors = {
    pending: 'bg-yellow-500/20 text-yellow-700',
    completed: 'bg-green-500/20 text-green-700',
    failed: 'bg-red-500/20 text-red-700',
  };
  return colors[status] || 'bg-gray-500/20 text-gray-700';
};

const toggleSelectAll = () => {
  if (allSelected.value) {
    selectedDonations.value = [];
  } else {
    selectedDonations.value = props.donations.data.map(d => d.id);
  }
};

const applyFilters = () => {
  router.get('/admin/donations', {
    search: searchQuery.value,
    status: statusFilter.value === 'all' ? '' : statusFilter.value,
    date_range: dateRangeFilter.value === 'all' ? '' : dateRangeFilter.value,
  }, {
    preserveState: true,
    preserveScroll: true,
  });
};

const clearFilters = () => {
  searchQuery.value = '';
  statusFilter.value = 'all';
  dateRangeFilter.value = 'all';
  router.get('/admin/donations');
};

const viewDonation = (id: number) => {
  router.get(`/admin/donations/${id}`);
};

const deleteDonation = (id: number) => {
  if (confirm('Are you sure you want to delete this donation?')) {
    router.delete(`/admin/donations/${id}`, {
      preserveScroll: true,
    });
  }
};

const bulkDelete = () => {
  if (selectedDonations.value.length === 0) return;
  
  if (confirm(`Delete ${selectedDonations.value.length} selected donations?`)) {
    router.post('/admin/donations/bulk-delete', {
      donation_ids: selectedDonations.value
    }, {
      preserveScroll: true,
      onSuccess: () => {
        selectedDonations.value = [];
      }
    });
  }
};


</script>

<template>
  <AdminLayout>
    <Head title="Manage Donations" />

    <div class="space-y-6">
      
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold flex items-center space-x-3">
            <Heart class="h-8 w-8 text-red-500" />
            <span>Donations</span>
          </h1>
          <p class="text-muted-foreground mt-1">
            Manage and track all donation contributions
          </p>
        </div>
        <div class="flex space-x-3">
        
          <Button 
            v-if="selectedDonations.length > 0" 
            @click="bulkDelete" 
            variant="destructive"
          >
            <Trash2 class="h-4 w-4 mr-2" />
            Delete ({{ selectedDonations.length }})
          </Button>
        </div>
      </div>

      <!-- Stats Cards -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Total Donations</CardTitle>
            <Users class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ stats.total }}</div>
            <p class="text-xs text-muted-foreground mt-1">
              All time contributions
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Total Revenue</CardTitle>
            <DollarSign class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ formatPrice(stats.total_revenue) }}</div>
            <p class="text-xs text-green-600 mt-1">
              From {{ stats.completed }} completed
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">This Month</CardTitle>
            <TrendingUp class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ formatPrice(stats.this_month_revenue) }}</div>
            <p class="text-xs text-muted-foreground mt-1">
              {{ stats.this_month }} donations
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Pending</CardTitle>
            <Calendar class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold text-yellow-600">{{ stats.pending }}</div>
            <p class="text-xs text-muted-foreground mt-1">
              Awaiting payment
            </p>
          </CardContent>
        </Card>
      </div>

      <!-- Filters -->
      <Card>
        <CardHeader>
          <CardTitle class="flex items-center space-x-2">
            <Filter class="h-5 w-5" />
            <span>Filters</span>
          </CardTitle>
        </CardHeader>
        <CardContent>
          <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            
            <!-- Search -->
            <div class="md:col-span-2">
              <div class="relative">
                <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                <Input
                  v-model="searchQuery"
                  placeholder="Search by name, phone, or ID..."
                  class="pl-10"
                  @keyup.enter="applyFilters"
                />
              </div>
            </div>

            <!-- Status Filter -->
            <Select v-model="statusFilter">
              <SelectTrigger>
                <SelectValue placeholder="All Statuses" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">All Statuses</SelectItem>
                <SelectItem
                  v-for="status in statuses"
                  :key="status.value"
                  :value="status.value"
                >
                  {{ status.label }}
                </SelectItem>
              </SelectContent>
            </Select>

            <!-- Date Range Filter -->
            <Select v-model="dateRangeFilter">
              <SelectTrigger>
                <SelectValue placeholder="All Time" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">All Time</SelectItem>
                <SelectItem value="today">Today</SelectItem>
                <SelectItem value="week">This Week</SelectItem>
                <SelectItem value="month">This Month</SelectItem>
                <SelectItem value="year">This Year</SelectItem>
              </SelectContent>
            </Select>

          </div>

          <div class="flex space-x-2 mt-4">
            <Button @click="applyFilters">Apply Filters</Button>
            <Button @click="clearFilters" variant="outline">Clear</Button>
          </div>
        </CardContent>
      </Card>

      <!-- Donations Table -->
      <Card>
        <CardContent class="p-0">
          <div class="overflow-x-auto">
            <table class="w-full">
              <thead class="bg-muted/50">
                <tr>
                  <th class="p-4 text-left">
                    <input
                      type="checkbox"
                      :checked="allSelected"
                      @change="toggleSelectAll"
                      class="rounded"
                    />
                  </th>
                  <th class="p-4 text-left font-medium">ID</th>
                  <th class="p-4 text-left font-medium">Donor</th>
                  <th class="p-4 text-left font-medium">Phone</th>
                  <th class="p-4 text-left font-medium">Amount</th>
                  <th class="p-4 text-left font-medium">Status</th>
                  <th class="p-4 text-left font-medium">Date</th>
                  <th class="p-4 text-left font-medium">Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="donation in donations.data"
                  :key="donation.id"
                  class="border-t hover:bg-muted/30 transition-colors"
                >
                  <td class="p-4">
                    <input
                      type="checkbox"
                      :value="donation.id"
                      v-model="selectedDonations"
                      class="rounded"
                    />
                  </td>
                  <td class="p-4 font-mono text-sm">#{{ donation.id }}</td>
                  <td class="p-4 font-medium">{{ donation.name }}</td>
                  <td class="p-4 text-sm text-muted-foreground">{{ donation.phone }}</td>
                  <td class="p-4 font-bold text-lg">{{ formatPrice(donation.value) }}</td>
                  <td class="p-4">
                    <Badge :class="getStatusColor(donation.status)">
                      {{ donation.status }}
                    </Badge>
                  </td>
                  <td class="p-4 text-sm text-muted-foreground">
                    {{ formatDate(donation.created_at) }}
                  </td>
                  <td class="p-4">
                    <div class="flex space-x-2">
                      <Button
                        @click="viewDonation(donation.id)"
                        variant="outline"
                        size="sm"
                      >
                        <Eye class="h-4 w-4" />
                      </Button>
                      <Button
                        v-if="donation.status !== 'completed'"
                        @click="deleteDonation(donation.id)"
                        variant="destructive"
                        size="sm"
                      >
                        <Trash2 class="h-4 w-4" />
                      </Button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Empty State -->
          <div v-if="donations.data.length === 0" class="text-center py-12">
            <Heart class="h-12 w-12 mx-auto text-muted-foreground mb-4" />
            <h3 class="text-lg font-medium mb-2">No donations found</h3>
            <p class="text-muted-foreground">
              {{ filters.search || filters.status ? 'Try adjusting your filters' : 'Donations will appear here when received' }}
            </p>
          </div>

          <!-- Pagination -->
          <div v-if="donations.data.length > 0" class="border-t p-4">
            <div class="flex items-center justify-between">
              <div class="text-sm text-muted-foreground">
                Showing {{ donations.from }} to {{ donations.to }} of {{ donations.total }} donations
              </div>
              <div class="flex space-x-2">
                <Button
                  v-for="link in donations.links"
                  :key="link.label"
                  @click="router.get(link.url)"
                  :variant="link.active ? 'default' : 'outline'"
                  :disabled="!link.url"
                  size="sm"
                  v-html="link.label"
                />
              </div>
            </div>
          </div>

        </CardContent>
      </Card>

    </div>
  </AdminLayout>
</template>
