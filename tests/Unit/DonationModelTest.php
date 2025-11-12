<?php

namespace Tests\Unit;

use App\Models\Donation;
use PHPUnit\Framework\TestCase;

class DonationModelTest extends TestCase
{
    /**
     * Test donation status constants exist
     */
    public function test_status_constants_exist()
    {
        $this->assertEquals('pending', Donation::STATUS_PENDING);
        $this->assertEquals('completed', Donation::STATUS_COMPLETED);
        $this->assertEquals('failed', Donation::STATUS_FAILED);
    }

    /**
     * Test donation fillable attributes
     */
    public function test_fillable_attributes()
    {
        $fillable = [
            'name',
            'phone',
            'value',
            'message',
            'status',
            'payment_method',
            'payment_id',
            'payment_intent_id',
            'paid_at',
        ];

        $donation = new Donation();
        $this->assertEquals($fillable, $donation->getFillable());
    }

    /**
     * Test get statuses method
     */
    public function test_get_statuses_returns_array()
    {
        $statuses = Donation::getStatuses();
        
        $this->assertIsArray($statuses);
        $this->assertCount(3, $statuses);
        $this->assertEquals('pending', $statuses[0]['value']);
        $this->assertEquals('completed', $statuses[1]['value']);
        $this->assertEquals('failed', $statuses[2]['value']);
    }
}
