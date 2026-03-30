<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmailValidationFeatureTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create([
            'email' => 'user@example.com',
        ]);
    }

    /**
     * Test updating profile with valid email
     */
    public function test_update_profile_with_valid_email(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/user/profile', [
                'email' => 'newemail@example.com',
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Profile updated successfully',
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'email' => 'newemail@example.com',
        ]);
    }

    /**
     * Test updating profile with invalid email - missing @
     */
    public function test_update_profile_with_invalid_email_missing_at(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/user/profile', [
                'email' => 'invalidemail.com',
            ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'error' => 'Validation failed',
        ]);
        $response->assertJsonStructure([
            'errors' => ['email'],
        ]);
    }

    /**
     * Test updating profile with invalid email - missing domain
     */
    public function test_update_profile_with_invalid_email_missing_domain(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/user/profile', [
                'email' => 'user@',
            ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'error' => 'Validation failed',
        ]);
        $response->assertJsonStructure([
            'errors' => ['email'],
        ]);
    }

    /**
     * Test updating profile with invalid email - spaces
     */
    public function test_update_profile_with_invalid_email_with_spaces(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/user/profile', [
                'email' => 'user @example.com',
            ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'error' => 'Validation failed',
        ]);
        $response->assertJsonStructure([
            'errors' => ['email'],
        ]);
    }

    /**
     * Test updating profile with valid email containing plus sign
     */
    public function test_update_profile_with_valid_email_plus_sign(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/user/profile', [
                'email' => 'user+tag@example.com',
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Profile updated successfully',
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'email' => 'user+tag@example.com',
        ]);
    }

    /**
     * Test updating profile with valid email containing dots
     */
    public function test_update_profile_with_valid_email_dots(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/user/profile', [
                'email' => 'user.name@example.com',
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Profile updated successfully',
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'email' => 'user.name@example.com',
        ]);
    }

    /**
     * Test updating profile with nullable email (should not update)
     */
    public function test_update_profile_with_nullable_email(): void
    {
        $originalEmail = $this->user->email;

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/user/profile', [
                'display_name' => 'New Name',
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Profile updated successfully',
        ]);

        // Email should remain unchanged
        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'email' => $originalEmail,
        ]);
    }

    /**
     * Test updating profile with empty email string
     */
    public function test_update_profile_with_empty_email(): void
    {
        $originalEmail = $this->user->email;

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/user/profile', [
                'email' => '',
            ]);

        // Empty string is filtered out by array_filter, so it's treated as not provided
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Profile updated successfully',
        ]);

        // Email should remain unchanged
        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'email' => $originalEmail,
        ]);
    }

    /**
     * Test updating profile with multiple @ symbols
     */
    public function test_update_profile_with_multiple_at_symbols(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/user/profile', [
                'email' => 'user@@example.com',
            ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'error' => 'Validation failed',
        ]);
        $response->assertJsonStructure([
            'errors' => ['email'],
        ]);
    }

    /**
     * Test updating profile with valid subdomain email
     */
    public function test_update_profile_with_valid_subdomain_email(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/user/profile', [
                'email' => 'user@mail.example.com',
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Profile updated successfully',
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'email' => 'user@mail.example.com',
        ]);
    }
}
