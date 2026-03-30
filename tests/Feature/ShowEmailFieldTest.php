<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShowEmailFieldTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that show_email field defaults to false
     */
    public function test_show_email_defaults_to_false()
    {
        $user = User::factory()->create();
        
        $this->assertFalse($user->show_email);
    }

    /**
     * Test that show_email field can be set to true
     */
    public function test_show_email_can_be_set_to_true()
    {
        $user = User::factory()->create(['show_email' => true]);
        
        $this->assertTrue($user->show_email);
    }

    /**
     * Test that show_email field can be updated via profile endpoint
     */
    public function test_update_show_email_via_profile_endpoint()
    {
        $user = User::factory()->create(['show_email' => false]);
        
        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/user/profile', [
                'show_email' => true,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Profile updated successfully',
            ])
            ->assertJsonPath('profile.show_email', true);

        $this->assertTrue($user->fresh()->show_email);
    }

    /**
     * Test that show_email field is included in profile response
     */
    public function test_show_email_included_in_profile_response()
    {
        $user = User::factory()->create(['show_email' => true]);
        
        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/user/profile');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonPath('profile.show_email', true);
    }

    /**
     * Test that show_email field is cast as boolean
     */
    public function test_show_email_is_cast_as_boolean()
    {
        $user = User::factory()->create(['show_email' => 1]);
        
        $this->assertIsBool($user->show_email);
        $this->assertTrue($user->show_email);
    }

    /**
     * Test that show_email field can be toggled
     */
    public function test_show_email_can_be_toggled()
    {
        $user = User::factory()->create(['show_email' => false]);
        
        // Toggle to true
        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/user/profile', [
                'show_email' => true,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('profile.show_email', true);

        // Toggle back to false
        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/user/profile', [
                'show_email' => false,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('profile.show_email', false);
    }
}
