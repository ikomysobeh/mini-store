<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { 
  Heart, ArrowLeft, Trash2, User, Phone, 
  DollarSign, Calendar, CreditCard, MessageSquare, Copy, Check 
} from 'lucide-vue-next';
import { ref } from 'vue';

const props = defineProps({
  donation: Object,
});

// State for copy feedback
const copiedPaymentId = ref(false);
const copiedIntentId = ref(false);

const formatPrice = (amount: number) => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD'
  }).format(amount);
};

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString('en-US', {
    weekday: 'long',
    day: '2-digit',
    month: 'long',
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

// ✅ FIXED: Use direct URL instead of route()
const goBack = () => {
  router.get('/admin/donations');
};

// ✅ FIXED: Use direct URL instead of route()
const deleteDonation = () => {
  if (confirm('Are you sure you want to delete this donation?')) {
    router.delete(`/admin/donations/${props.donation.id}`, {
      onSuccess: () => goBack()
    });
  }
};

// ✅ NEW: Copy to clipboard functionality
const copyToClipboard = (text: string, type: 'payment' | 'intent') => {
  navigator.clipboard.writeText(text).then(() => {
    if (type === 'payment') {
      copiedPaymentId.value = true;
      setTimeout(() => copiedPaymentId.value = false, 2000);
    } else {
      copiedIntentId.value = true;
      setTimeout(() => copiedIntentId.value = false, 2000);
    }
  });
};

// ✅ NEW: Truncate long IDs for display
const truncateId = (id: string) => {
  if (!id || id === 'N/A') return 'N/A';
  if (id.length <= 20) return id;
  return `${id.substring(0, 10)}...${id.substring(id.length - 6)}`;
};
</script>

<template>
  <AdminLayout>
    <Head :title="`Donation #${donation.id}`" />

    <div class="space-y-6">
      
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div class="flex items-center space-x-4">
          <Button @click="goBack" variant="outline" size="icon">
            <ArrowLeft class="h-4 w-4" />
          </Button>
          <div>
            <h1 class="text-3xl font-bold flex items-center space-x-3">
              <Heart class="h-8 w-8 text-red-500" />
              <span>Donation #{{ donation.id }}</span>
            </h1>
            <p class="text-muted-foreground mt-1">
              Donation details and information
            </p>
          </div>
        </div>
        <Button 
          v-if="donation.status !== 'completed'"
          @click="deleteDonation" 
          variant="destructive"
        >
          <Trash2 class="h-4 w-4 mr-2" />
          Delete
        </Button>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Main Info -->
        <div class="lg:col-span-2 space-y-6">
          
          <!-- Donor Information -->
          <Card>
            <CardHeader>
              <CardTitle class="flex items-center space-x-2">
                <User class="h-5 w-5" />
                <span>Donor Information</span>
              </CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <p class="text-sm text-muted-foreground">Full Name</p>
                  <p class="font-medium text-lg">{{ donation.name }}</p>
                </div>
                <div>
                  <p class="text-sm text-muted-foreground">Phone Number</p>
                  <p class="font-medium text-lg flex items-center">
                    <Phone class="h-4 w-4 mr-2" />
                    {{ donation.phone }}
                  </p>
                </div>
              </div>
            </CardContent>
          </Card>

          <!-- Message -->
          <Card v-if="donation.message">
            <CardHeader>
              <CardTitle class="flex items-center space-x-2">
                <MessageSquare class="h-5 w-5" />
                <span>Donor Message</span>
              </CardTitle>
            </CardHeader>
            <CardContent>
              <div class="bg-muted/30 rounded-lg p-4">
                <p class="text-foreground italic leading-relaxed">
                  "{{ donation.message }}"
                </p>
              </div>
            </CardContent>
          </Card>

          <!-- Payment Information -->
          <Card>
            <CardHeader>
              <CardTitle class="flex items-center space-x-2">
                <CreditCard class="h-5 w-5" />
                <span>Payment Information</span>
              </CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
              <div class="grid grid-cols-1 gap-4">
                
                <!-- Payment Method -->
                <div class="flex items-center justify-between pb-3 border-b">
                  <p class="text-sm text-muted-foreground">Payment Method</p>
                  <Badge variant="outline" class="font-mono">
                    {{ donation.payment_method || 'Stripe' }}
                  </Badge>
                </div>

                <!-- Payment ID -->
                <div class="space-y-2">
                  <p class="text-sm text-muted-foreground">Stripe Session ID</p>
                  <div v-if="donation.payment_id" class="flex items-center justify-between bg-muted/30 rounded-lg p-3">
                    <code class="text-xs font-mono flex-1 truncate">
                      {{ donation.payment_id }}
                    </code>
                    <Button 
                      @click="copyToClipboard(donation.payment_id, 'payment')"
                      variant="ghost" 
                      size="sm"
                      class="ml-2 h-8 w-8 p-0"
                    >
                      <Check v-if="copiedPaymentId" class="h-4 w-4 text-green-600" />
                      <Copy v-else class="h-4 w-4" />
                    </Button>
                  </div>
                  <p v-else class="text-sm text-muted-foreground italic">Not available</p>
                </div>

                <!-- Payment Intent ID -->
                <div class="space-y-2">
                  <p class="text-sm text-muted-foreground">Stripe Payment Intent</p>
                  <div v-if="donation.payment_intent_id" class="flex items-center justify-between bg-muted/30 rounded-lg p-3">
                    <code class="text-xs font-mono flex-1 truncate">
                      {{ donation.payment_intent_id }}
                    </code>
                    <Button 
                      @click="copyToClipboard(donation.payment_intent_id, 'intent')"
                      variant="ghost" 
                      size="sm"
                      class="ml-2 h-8 w-8 p-0"
                    >
                      <Check v-if="copiedIntentId" class="h-4 w-4 text-green-600" />
                      <Copy v-else class="h-4 w-4" />
                    </Button>
                  </div>
                  <p v-else class="text-sm text-muted-foreground italic">Not available</p>
                </div>

                <!-- Paid At -->
                <div class="flex items-center justify-between pt-3 border-t">
                  <p class="text-sm text-muted-foreground">Payment Completed</p>
                  <p class="text-sm font-medium">
                    {{ donation.paid_at ? formatDate(donation.paid_at) : 'Not paid yet' }}
                  </p>
                </div>

              </div>
            </CardContent>
          </Card>

        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
          
          <!-- Amount Card -->
          <Card class="bg-primary/5 border-primary/20">
            <CardHeader>
              <CardTitle class="text-center flex items-center justify-center space-x-2">
                <DollarSign class="h-5 w-5" />
                <span>Donation Amount</span>
              </CardTitle>
            </CardHeader>
            <CardContent>
              <div class="text-center">
                <p class="text-4xl font-bold text-primary mb-2">
                  {{ formatPrice(donation.value) }}
                </p>
                <Badge :class="getStatusColor(donation.status)" class="text-sm">
                  {{ donation.status.toUpperCase() }}
                </Badge>
              </div>
            </CardContent>
          </Card>

          <!-- Dates Card -->
          <Card>
            <CardHeader>
              <CardTitle class="flex items-center space-x-2">
                <Calendar class="h-5 w-5" />
                <span>Timeline</span>
              </CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
              <div>
                <p class="text-xs text-muted-foreground mb-1">Created</p>
                <p class="text-sm font-medium">{{ formatDate(donation.created_at) }}</p>
              </div>
              <div v-if="donation.paid_at" class="pt-4 border-t">
                <p class="text-xs text-muted-foreground mb-1">Paid</p>
                <p class="text-sm font-medium text-green-600">{{ formatDate(donation.paid_at) }}</p>
              </div>
              <div v-else class="pt-4 border-t">
                <p class="text-xs text-muted-foreground mb-1">Status</p>
                <p class="text-sm font-medium text-yellow-600">Awaiting payment</p>
              </div>
            </CardContent>
          </Card>

          <!-- Quick Actions -->
          <Card>
            <CardHeader>
              <CardTitle class="text-sm">Quick Actions</CardTitle>
            </CardHeader>
            <CardContent class="space-y-2">
              <Button 
                @click="goBack" 
                variant="outline" 
                class="w-full"
              >
                <ArrowLeft class="h-4 w-4 mr-2" />
                Back to List
              </Button>
              <Button 
                v-if="donation.status !== 'completed'"
                @click="deleteDonation" 
                variant="destructive"
                class="w-full"
              >
                <Trash2 class="h-4 w-4 mr-2" />
                Delete Donation
              </Button>
            </CardContent>
          </Card>

        </div>

      </div>

    </div>
  </AdminLayout>
</template>
