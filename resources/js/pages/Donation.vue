<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import Navbar from '@/components/Navbar.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Heart, Phone, User, MessageSquare, DollarSign, Shield } from 'lucide-vue-next';

// Props from backend
const props = defineProps({
  settings: { type: Object, required: true },
  categories: { type: Array, default: () => [] },
  auth: { type: Object, default: () => ({}) },
});

// Get donation settings with defaults
const donationTitle = props.settings?.donation_page_title || 'Support Our Cause';
const donationSubtitle = props.settings?.donation_page_subtitle || 'Your contribution makes a difference';
const donationMessage = props.settings?.donation_page_message || 'Help us continue our important work...';
const minAmount = Number(props.settings?.donation_min_amount) || 5;
const donationEnabled = props.settings?.donation_enable !== '0';
const siteName = props.settings?.site_name || 'Elegant Store';

// Form state
const form = ref({
  name: '',
  phone: '',
  value: minAmount,
  message: '',
});

const isProcessing = ref(false);

// Validation
const isValid = computed(() => {
  return form.value.name.trim() !== '' && 
         form.value.phone.trim() !== '' && 
         form.value.value >= minAmount;
});

// Submit donation
const submitDonation = () => {
  if (!isValid.value || isProcessing.value) return;
  
  isProcessing.value = true;
  router.post('/donate', form.value, {
    onFinish: () => {
      isProcessing.value = false;
    },
    onError: (errors) => {
      console.error('Donation errors:', errors);
      isProcessing.value = false;
    }
  });
};

// Format currency
const formatPrice = (amount: number) => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD'
  }).format(amount);
};
</script>

<template>
  <div class="min-h-screen bg-background text-foreground">
    <Head :title="`${donationTitle} - ${siteName}`" />

    <!-- Navbar -->
    <Navbar
      :categories="categories"
      :cart-items="[]"
      :user="auth.user"
      :site-name="siteName"
      :settings="settings"
    />

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">

      <!-- Disabled State -->
      <div v-if="!donationEnabled" class="text-center py-16">
        <Card>
          <CardContent class="p-12">
            <Heart class="h-16 w-16 mx-auto text-muted-foreground mb-4" />
            <h2 class="text-2xl font-bold mb-4">Donations Currently Unavailable</h2>
            <p class="text-muted-foreground">
              The donation feature is temporarily disabled. Please check back later.
            </p>
          </CardContent>
        </Card>
      </div>

      <!-- Donation Form -->
      <div v-else>
        
        <!-- Header Section -->
        <div class="text-center mb-12">
          <Heart class="h-12 w-12 mx-auto text-primary mb-4" />
          <h1 class="text-4xl font-bold mb-4">{{ donationTitle }}</h1>
          <p class="text-xl text-muted-foreground mb-8">{{ donationSubtitle }}</p>
          
          <!-- Admin's Message -->
          <Card class="bg-primary/5 border-primary/20">
            <CardContent class="p-6">
              <p class="text-left whitespace-pre-wrap leading-relaxed">
                {{ donationMessage }}
              </p>
            </CardContent>
          </Card>
        </div>

        <!-- Donation Form -->
        <Card>
          <CardHeader>
            <CardTitle class="flex items-center space-x-2">
              <Heart class="h-6 w-6 text-red-500" />
              <span>Make Your Donation</span>
            </CardTitle>
            <CardDescription>
              Your support helps us make a real difference
            </CardDescription>
          </CardHeader>
          
          <CardContent class="space-y-6">
            
            <!-- Name -->
            <div class="space-y-2">
              <Label for="name" class="flex items-center space-x-2">
                <User class="h-4 w-4" />
                <span>Full Name</span>
              </Label>
              <Input 
                id="name" 
                v-model="form.name" 
                placeholder="Enter your full name"
                required 
              />
            </div>

            <!-- Phone -->
            <div class="space-y-2">
              <Label for="phone" class="flex items-center space-x-2">
                <Phone class="h-4 w-4" />
                <span>Phone Number</span>
              </Label>
              <Input 
                id="phone" 
                v-model="form.phone" 
                type="tel"
                placeholder="+963944255208"
                required 
              />
              <p class="text-xs text-muted-foreground">
                Include country code (e.g., +963)
              </p>
            </div>

            <!-- Donation Amount -->
            <div class="space-y-2">
              <Label for="value" class="flex items-center space-x-2">
                <DollarSign class="h-4 w-4" />
                <span>Donation Amount (USD)</span>
              </Label>
              <Input 
                id="value" 
                v-model.number="form.value" 
                type="number" 
                :min="minAmount"
                step="1"
                required 
              />
              <p class="text-xs text-muted-foreground">
                Minimum amount: {{ formatPrice(minAmount) }}
              </p>
            </div>

            <!-- Optional Message -->
            <div class="space-y-2">
              <Label for="message" class="flex items-center space-x-2">
                <MessageSquare class="h-4 w-4" />
                <span>Message (Optional)</span>
              </Label>
              <Textarea 
                id="message" 
                v-model="form.message" 
                rows="4"
                placeholder="Leave a message, dedication, or special note..."
                class="resize-none"
              />
            </div>

            <!-- Amount Preview -->
            <div class="bg-muted/30 rounded-lg p-4">
              <div class="flex items-center justify-between">
                <span class="text-sm font-medium">Donation Amount:</span>
                <span class="text-2xl font-bold text-primary">
                  {{ formatPrice(form.value) }}
                </span>
              </div>
            </div>

            <!-- Submit Button -->
            <Button 
              @click="submitDonation" 
              :disabled="!isValid || isProcessing"
              class="w-full h-14 text-lg font-bold bg-red-600 hover:bg-red-700 text-white"
              size="lg"
            >
              <div v-if="isProcessing" class="flex items-center">
                <svg class="animate-spin -ml-1 mr-3 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Processing...
              </div>
              <div v-else class="flex items-center justify-center">
                <Heart class="h-6 w-6 mr-2" />
                Donate {{ formatPrice(form.value) }} via Stripe
              </div>
            </Button>

            <!-- Missing fields helper -->
            <div v-if="!isValid && !isProcessing" class="rounded-md border border-amber-300 bg-amber-50 px-3 py-3 text-sm text-amber-900">
              <p class="font-medium">Please complete the following:</p>
              <ul class="mt-2 list-disc list-inside space-y-1">
                <li v-if="!form.name">Full Name is required</li>
                <li v-if="!form.phone">Phone Number is required</li>
                <li v-if="form.value < minAmount">
                  Minimum donation is {{ formatPrice(minAmount) }}
                </li>
              </ul>
            </div>

          </CardContent>
        </Card>

        <!-- Trust Indicators -->
        <div class="text-center mt-8 space-y-2">
          <div class="flex items-center justify-center space-x-2 text-sm text-muted-foreground">
            <Shield class="h-4 w-4" />
            <p>Secure payment powered by Stripe</p>
          </div>
          <p class="text-xs text-muted-foreground">
            Your donation is processed securely and safely. We never store your payment information.
          </p>
        </div>

      </div>

    </div>
  </div>
</template>
