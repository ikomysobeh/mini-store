<?php

namespace Tests\Feature;

use App\Models\Donation;
use App\Models\AdminNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class StripeWebhookDonationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test webhook processes donation payment successfully
     */
    public function test_webhook_completes_donation_payment()
    {
        // Create a pending donation
        $donation = Donation::create([
            'name' => 'John Doe',
            'phone' => '+1234567890',
            'value' => 50.00,
            'message' => 'Test donation',
            'status' => Donation::STATUS_PENDING,
            'payment_id' => null,
        ]);

        // Mock Stripe session object
        $mockSession = (object)[
            'id' => 'cs_test_123456',
            'payment_intent' => 'pi_test_123456',
            'metadata' => (object)[
                'type' => 'donation',
                'donation_id' => $donation->id,
                'donor_name' => 'John Doe',
                'donor_phone' => '+1234567890',
            ],
        ];

        // Call the webhook handler directly (you'll need to make it public for testing)
        $webhookController = new \App\Http\Controllers\web\StripeWebhookController();
        
        // Use reflection to call private method
        $reflection = new \ReflectionClass($webhookController);
        $method = $reflection->getMethod('handleCheckoutSessionCompleted');
        $method->setAccessible(true);
        $method->invoke($webhookController, $mockSession);

        // Assert donation was updated
        $donation->refresh();
        $this->assertEquals(Donation::STATUS_COMPLETED, $donation->status);
        $this->assertNotNull($donation->paid_at);
        $this->assertEquals('cs_test_123456', $donation->payment_id);
        $this->assertEquals('pi_test_123456', $donation->payment_intent_id);

        // Assert admin notification was created
        $this->assertDatabaseHas('admin_notifications', [
            'type' => 'new_donation',
        ]);
    }

    /**
     * Test webhook ignores already completed donation
     */
    public function test_webhook_ignores_already_completed_donation()
    {
        // Create a completed donation
        $donation = Donation::create([
            'name' => 'Jane Doe',
            'phone' => '+1234567890',
            'value' => 75.00,
            'status' => Donation::STATUS_COMPLETED,
            'paid_at' => now(),
            'payment_id' => 'cs_test_existing',
        ]);

        $originalPaidAt = $donation->paid_at;

        // Mock Stripe session object
        $mockSession = (object)[
            'id' => 'cs_test_existing',
            'payment_intent' => 'pi_test_existing',
            'metadata' => (object)[
                'type' => 'donation',
                'donation_id' => $donation->id,
            ],
        ];

        // Call webhook handler
        $webhookController = new \App\Http\Controllers\web\StripeWebhookController();
        $reflection = new \ReflectionClass($webhookController);
        $method = $reflection->getMethod('handleCheckoutSessionCompleted');
        $method->setAccessible(true);
        $method->invoke($webhookController, $mockSession);

        // Assert donation was NOT updated (paid_at should be the same)
        $donation->refresh();
        $this->assertEquals($originalPaidAt->timestamp, $donation->paid_at->timestamp);
    }

    /**
     * Test webhook routes orders separately from donations
     */
    public function test_webhook_routes_orders_and_donations_separately()
    {
        // Create a pending donation
        $donation = Donation::create([
            'name' => 'Test Donor',
            'phone' => '+1234567890',
            'value' => 100.00,
            'status' => Donation::STATUS_PENDING,
        ]);

        // Mock Stripe session with ORDER type
        $mockOrderSession = (object)[
            'id' => 'cs_order_123',
            'metadata' => (object)[
                'type' => 'order', // ← This is an order, not donation
                'order_id' => 999,
            ],
        ];

        // Call webhook handler
        $webhookController = new \App\Http\Controllers\web\StripeWebhookController();
        $reflection = new \ReflectionClass($webhookController);
        $method = $reflection->getMethod('handleCheckoutSessionCompleted');
        $method->setAccessible(true);
        
        // Should not affect donation (should try to find order instead)
        Log::shouldReceive('warning')->once(); // Will warn that order not found
        $method->invoke($webhookController, $mockOrderSession);

        // Assert donation was NOT updated
        $donation->refresh();
        $this->assertEquals(Donation::STATUS_PENDING, $donation->status);
        $this->assertNull($donation->paid_at);
    }

    /**
     * Test webhook handles missing donation gracefully
     */
    public function test_webhook_handles_missing_donation_gracefully()
    {
        // Mock Stripe session with non-existent donation ID
        $mockSession = (object)[
            'id' => 'cs_test_missing',
            'metadata' => (object)[
                'type' => 'donation',
                'donation_id' => 99999, // Non-existent
            ],
        ];

        // Expect warning log
        Log::shouldReceive('warning')
            ->once()
            ->with('Stripe: Donation not found', \Mockery::type('array'));

        // Call webhook handler - should not throw exception
        $webhookController = new \App\Http\Controllers\web\StripeWebhookController();
        $reflection = new \ReflectionClass($webhookController);
        $method = $reflection->getMethod('handleCheckoutSessionCompleted');
        $method->setAccessible(true);
        $method->invoke($webhookController, $mockSession);

        // No exception thrown = test passes
        $this->assertTrue(true);
    }
}
