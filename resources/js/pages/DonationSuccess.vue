<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import Navbar from '@/components/Navbar.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { CheckCircle, Heart, Home, Share2 } from 'lucide-vue-next';

const props = defineProps({
  donation: { type: Object, required: true },
  settings: { type: Object, required: true },
  categories: { type: Array, default: () => [] },
  auth: { type: Object, default: () => ({}) },
});

const siteName = props.settings?.site_name || 'Elegant Store';

const formatPrice = (amount: number) => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD'
  }).format(amount);
};

const shareMessage = () => {
  const text = `I just donated to ${siteName}! Join me in supporting this great cause.`;
  const url = window.location.origin + '/donate';
  
  if (navigator.share) {
    navigator.share({ title: siteName, text, url });
  } else {
    navigator.clipboard.writeText(url);
    alert('Donation link copied to clipboard!');
  }
};
</script>

<template>
  <div class="min-h-screen bg-background text-foreground">
    <Head :title="`Thank You - ${siteName}`" />

    <Navbar
      :categories="categories"
      :cart-items="[]"
      :user="auth.user"
      :site-name="siteName"
      :settings="settings"
    />

    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
      
      <Card class="text-center">
        <CardHeader>
          <div class="flex justify-center mb-4">
            <CheckCircle class="h-20 w-20 text-green-500" />
          </div>
          <CardTitle class="text-3xl">Thank You for Your Donation!</CardTitle>
        </CardHeader>
        
        <CardContent class="space-y-6">
          
          <!-- Success Message -->
          <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-6">
            <p class="text-lg text-green-800 dark:text-green-300 mb-2">
              Your donation of <strong>{{ formatPrice(donation.value) }}</strong> has been received!
            </p>
            <p class="text-sm text-green-700 dark:text-green-400">
              Donation ID: #{{ donation.id }}
            </p>
          </div>

          <!-- Donation Details -->
          <div class="text-left space-y-3 bg-muted/30 rounded-lg p-4">
            <div class="flex justify-between">
              <span class="text-muted-foreground">Donor Name:</span>
              <span class="font-medium">{{ donation.name }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-muted-foreground">Phone:</span>
              <span class="font-medium">{{ donation.phone }}</span>
            </div>
            <div v-if="donation.message" class="pt-3 border-t">
              <span class="text-muted-foreground block mb-2">Your Message:</span>
              <p class="text-sm italic">{{ donation.message }}</p>
            </div>
          </div>

          <!-- Impact Message -->
          <div class="bg-primary/5 border border-primary/20 rounded-lg p-6">
            <Heart class="h-8 w-8 mx-auto text-red-500 mb-3" />
            <p class="text-sm leading-relaxed">
              Your generous contribution will directly help those in need. We truly appreciate your support in making a positive difference in our community.
            </p>
          </div>

          <!-- Action Buttons -->
          <div class="flex flex-col sm:flex-row gap-3 pt-4">
            <Button 
              as="a" 
              href="/" 
              variant="default" 
              class="flex-1"
            >
              <Home class="h-4 w-4 mr-2" />
              Return to Home
            </Button>
            
            <Button 
              @click="shareMessage" 
              variant="outline" 
              class="flex-1"
            >
              <Share2 class="h-4 w-4 mr-2" />
              Share & Inspire Others
            </Button>
          </div>

          <!-- Additional Note -->
          <p class="text-xs text-muted-foreground pt-4">
            A confirmation has been sent to the admin. If you have any questions, please contact us.
          </p>

        </CardContent>
      </Card>

    </div>
  </div>
</template>
