<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShowPhoneFieldTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that show_phone field defaults to true
     */
    public function test_show_phone_defaults_to_true()
    {
        $user = User::factory()->create();
        
        $this->assertTrue($user->show_phone);
    }

    /**
     * Test that show_phone field can be set to false
     */
    public function test_show_phone_can_be_set_to_false()
    {
        $user = User::factory()->create(['show_phone' => false]);
        
        $this->assertFalse($user->show_phone);
    }

    /**
     * Test that show_phone field can be updated via profile endpoint
     */
    public function test_update_show_phone_via_profile_endpoint()
    {
        $user = User::factory()->create(['show_phone' => true]);
        
        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/user/profile', [
                'show_phone' => false,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Profile updated successfully',
            ])
            ->assertJsonPath('profile.show_phone', false);

        $this->assertFalse($user->fresh()->show_phone);
    }

    /**
     * Test that show_phone field is included in profile response
     */
    public function test_show_phone_included_in_profile_response()
    {
        $user = User::factory()->create(['show_phone' => true]);
        
        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/user/profile');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonPath('profile.show_phone', true);
    }

    /**
     * Test that show_phone field is cast as boolean
     */
    public function test_show_phone_is_cast_as_boolean()
    {
        $user = User::factory()->create(['show_phone' => 1]);
        
        $this->assertIsBool($user->show_phone);
        $this->assertTrue($user->show_phone);
    }

    /**
     * Test that show_phone field can be toggled
     */
    public function test_show_phone_can_be_toggled()
    {
        $user = User::factory()->create(['show_phone' => true]);
        
        // Toggle to false
        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/user/profile', [
                'show_phone' => false,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('profile.show_phone', false);

        // Toggle back to true
        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/user/profile', [
                'show_phone' => true,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('profile.show_phone', true);
    }
}
