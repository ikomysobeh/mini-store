<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import Navbar from '@/components/Navbar.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { XCircle, ArrowLeft, Heart } from 'lucide-vue-next';

const props = defineProps({
  settings: { type: Object, required: true },
  categories: { type: Array, default: () => [] },
  auth: { type: Object, default: () => ({}) },
});

const siteName = props.settings?.site_name || 'Elegant Store';
</script>

<template>
  <div class="min-h-screen bg-background text-foreground">
    <Head :title="`Payment Cancelled - ${siteName}`" />

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
            <XCircle class="h-20 w-20 text-amber-500" />
          </div>
          <CardTitle class="text-3xl">Payment Cancelled</CardTitle>
        </CardHeader>
        
        <CardContent class="space-y-6">
          
          <!-- Cancel Message -->
          <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg p-6">
            <p class="text-lg text-amber-800 dark:text-amber-300 mb-2">
              Your donation payment was cancelled.
            </p>
            <p class="text-sm text-amber-700 dark:text-amber-400">
              No charges have been made to your account.
            </p>
          </div>

          <!-- Encouragement Message -->
          <div class="bg-primary/5 border border-primary/20 rounded-lg p-6">
            <Heart class="h-8 w-8 mx-auto text-red-500 mb-3" />
            <p class="text-sm leading-relaxed">
              We understand that circumstances change. If you'd like to try again or donate a different amount, we're here whenever you're ready.
            </p>
          </div>

          <!-- Action Buttons -->
          <div class="flex flex-col sm:flex-row gap-3 pt-4">
            <Button 
              as="a" 
              href="/donate" 
              variant="default"
              class="flex-1 bg-red-600 hover:bg-red-700"
            >
              <Heart class="h-4 w-4 mr-2" />
              Try Again
            </Button>
            
            <Button 
              as="a" 
              href="/" 
              variant="outline" 
              class="flex-1"
            >
              <ArrowLeft class="h-4 w-4 mr-2" />
              Return to Home
            </Button>
          </div>

          <!-- Help Text -->
          <p class="text-xs text-muted-foreground pt-4">
            If you experienced any issues, please contact our support team.
          </p>

        </CardContent>
      </Card>

    </div>
  </div>
</template>
