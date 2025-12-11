<?php

namespace Tests\Feature\Admin;

use App\Models\Size;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SizeValidationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create an admin user for testing
        $this->admin = User::factory()->create([
            'is_admin' => true,
        ]);
    }

    /**
     * Test that at least one name (name_en or name_ar) is required
     */
    public function test_size_requires_at_least_one_name()
    {
        $response = $this->actingAs($this->admin)->post(route('admin.sizes.store'), [
            'category_type' => 'general',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $response->assertSessionHasErrors();
        
        // Should have validation error for missing names
        $errors = session('errors');
        $this->assertTrue(
            $errors->has('name_en') || $errors->has('name_ar') || $errors->has('name'),
            'Expected validation error for missing name fields'
        );
    }

    /**
     * Test that both names can be provided
     */
    public function test_size_accepts_both_names()
    {
        $response = $this->actingAs($this->admin)->post(route('admin.sizes.store'), [
            'name_en' => 'Small',
            'name_ar' => 'صغير',
            'category_type' => 'general',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $response->assertSessionHasNoErrors();
        
        $this->assertDatabaseHas('sizes', [
            'name_en' => 'Small',
            'name_ar' => 'صغير',
            'category_type' => 'general',
        ]);
    }

    /**
     * Test that only English name is accepted
     */
    public function test_size_accepts_only_english_name()
    {
        $response = $this->actingAs($this->admin)->post(route('admin.sizes.store'), [
            'name_en' => 'Medium',
            'category_type' => 'clothing',
            'is_active' => true,
            'sort_order' => 2,
        ]);

        $response->assertSessionHasNoErrors();
        
        $this->assertDatabaseHas('sizes', [
            'name_en' => 'Medium',
            'category_type' => 'clothing',
        ]);
    }

    /**
     * Test that only Arabic name is accepted
     */
    public function test_size_accepts_only_arabic_name()
    {
        $response = $this->actingAs($this->admin)->post(route('admin.sizes.store'), [
            'name_ar' => 'كبير',
            'category_type' => 'shoes',
            'is_active' => true,
            'sort_order' => 3,
        ]);

        $response->assertSessionHasNoErrors();
        
        $this->assertDatabaseHas('sizes', [
            'name_ar' => 'كبير',
            'category_type' => 'shoes',
        ]);
    }

    /**
     * Test that empty strings for both names are rejected
     */
    public function test_size_rejects_empty_strings_for_both_names()
    {
        $response = $this->actingAs($this->admin)->post(route('admin.sizes.store'), [
            'name_en' => '',
            'name_ar' => '',
            'category_type' => 'general',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $response->assertSessionHasErrors();
    }

    /**
     * Test update with both names
     */
    public function test_size_update_accepts_both_names()
    {
        $size = Size::create([
            'name_en' => 'Old Name',
            'category_type' => 'general',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $response = $this->actingAs($this->admin)->put(route('admin.sizes.update', $size), [
            'name_en' => 'Updated English',
            'name_ar' => 'محدث',
            'category_type' => 'general',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $response->assertSessionHasNoErrors();
        
        $this->assertDatabaseHas('sizes', [
            'id' => $size->id,
            'name_en' => 'Updated English',
            'name_ar' => 'محدث',
        ]);
    }

    /**
     * Test update with only one name
     */
    public function test_size_update_accepts_single_name()
    {
        $size = Size::create([
            'name_en' => 'Original',
            'name_ar' => 'أصلي',
            'category_type' => 'general',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $response = $this->actingAs($this->admin)->put(route('admin.sizes.update', $size), [
            'name_en' => 'Updated Only English',
            'category_type' => 'general',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $response->assertSessionHasNoErrors();
        
        $this->assertDatabaseHas('sizes', [
            'id' => $size->id,
            'name_en' => 'Updated Only English',
        ]);
    }
}
