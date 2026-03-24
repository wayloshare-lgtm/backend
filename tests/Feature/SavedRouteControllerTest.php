<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\SavedRoute;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SavedRouteControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_create_saved_route_successfully()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/saved-routes', [
                'from_location' => 'Home',
                'to_location' => 'Office',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('saved_route.from_location', 'Home')
            ->assertJsonPath('saved_route.to_location', 'Office')
            ->assertJsonPath('saved_route.is_pinned', false);

        $this->assertDatabaseHas('saved_routes', [
            'user_id' => $this->user->id,
            'from_location' => 'Home',
            'to_location' => 'Office',
        ]);
    }

    public function test_create_saved_route_requires_authentication()
    {
        $response = $this->postJson('/api/v1/saved-routes', [
            'from_location' => 'Home',
            'to_location' => 'Office',
        ]);

        $response->assertStatus(401);
    }

    public function test_create_saved_route_validates_required_fields()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/saved-routes', [
                'from_location' => 'Home',
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error', 'Validation failed');
    }

    public function test_list_saved_routes_successfully()
    {
        SavedRoute::factory()->count(3)->create(['user_id' => $this->user->id]);
        SavedRoute::factory()->create(['user_id' => User::factory()->create()->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/saved-routes');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('count', 3);

        $this->assertCount(3, $response->json('saved_routes'));
    }

    public function test_list_saved_routes_orders_by_pinned_and_last_used()
    {
        $route1 = SavedRoute::factory()->create([
            'user_id' => $this->user->id,
            'is_pinned' => false,
            'last_used_at' => now()->subDays(5),
        ]);

        $route2 = SavedRoute::factory()->create([
            'user_id' => $this->user->id,
            'is_pinned' => true,
            'last_used_at' => now()->subDays(10),
        ]);

        $route3 = SavedRoute::factory()->create([
            'user_id' => $this->user->id,
            'is_pinned' => false,
            'last_used_at' => now(),
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/saved-routes');

        $routes = $response->json('saved_routes');
        // Pinned routes should come first
        $this->assertTrue($routes[0]['is_pinned']);
        // Then ordered by last_used_at
        $this->assertEquals($route3->id, $routes[1]['id']);
    }

    public function test_get_saved_route_successfully()
    {
        $savedRoute = SavedRoute::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/saved-routes/{$savedRoute->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('saved_route.id', $savedRoute->id)
            ->assertJsonPath('saved_route.from_location', $savedRoute->from_location);
    }

    public function test_get_saved_route_unauthorized()
    {
        $otherUser = User::factory()->create();
        $savedRoute = SavedRoute::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/saved-routes/{$savedRoute->id}");

        $response->assertStatus(403)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error', 'Unauthorized');
    }

    public function test_update_saved_route_successfully()
    {
        $savedRoute = SavedRoute::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/v1/saved-routes/{$savedRoute->id}", [
                'from_location' => 'New Home',
                'to_location' => 'New Office',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('saved_route.from_location', 'New Home')
            ->assertJsonPath('saved_route.to_location', 'New Office');

        $this->assertDatabaseHas('saved_routes', [
            'id' => $savedRoute->id,
            'from_location' => 'New Home',
            'to_location' => 'New Office',
        ]);
    }

    public function test_update_saved_route_partial()
    {
        $savedRoute = SavedRoute::factory()->create([
            'user_id' => $this->user->id,
            'from_location' => 'Home',
            'to_location' => 'Office',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/v1/saved-routes/{$savedRoute->id}", [
                'from_location' => 'New Home',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('saved_route.from_location', 'New Home')
            ->assertJsonPath('saved_route.to_location', 'Office');
    }

    public function test_update_saved_route_unauthorized()
    {
        $otherUser = User::factory()->create();
        $savedRoute = SavedRoute::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/v1/saved-routes/{$savedRoute->id}", [
                'from_location' => 'New Home',
            ]);

        $response->assertStatus(403)
            ->assertJsonPath('success', false);
    }

    public function test_delete_saved_route_successfully()
    {
        $savedRoute = SavedRoute::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/v1/saved-routes/{$savedRoute->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('saved_routes', [
            'id' => $savedRoute->id,
        ]);
    }

    public function test_delete_saved_route_unauthorized()
    {
        $otherUser = User::factory()->create();
        $savedRoute = SavedRoute::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/v1/saved-routes/{$savedRoute->id}");

        $response->assertStatus(403)
            ->assertJsonPath('success', false);

        $this->assertDatabaseHas('saved_routes', [
            'id' => $savedRoute->id,
        ]);
    }

    public function test_toggle_pin_successfully()
    {
        $savedRoute = SavedRoute::factory()->create([
            'user_id' => $this->user->id,
            'is_pinned' => false,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/v1/saved-routes/{$savedRoute->id}/pin");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('saved_route.is_pinned', true)
            ->assertJsonPath('message', 'Route pinned successfully');

        $this->assertDatabaseHas('saved_routes', [
            'id' => $savedRoute->id,
            'is_pinned' => true,
        ]);
    }

    public function test_toggle_pin_unpin()
    {
        $savedRoute = SavedRoute::factory()->create([
            'user_id' => $this->user->id,
            'is_pinned' => true,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/v1/saved-routes/{$savedRoute->id}/pin");

        $response->assertStatus(200)
            ->assertJsonPath('saved_route.is_pinned', false)
            ->assertJsonPath('message', 'Route unpinned successfully');

        $this->assertDatabaseHas('saved_routes', [
            'id' => $savedRoute->id,
            'is_pinned' => false,
        ]);
    }

    public function test_toggle_pin_unauthorized()
    {
        $otherUser = User::factory()->create();
        $savedRoute = SavedRoute::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/v1/saved-routes/{$savedRoute->id}/pin");

        $response->assertStatus(403)
            ->assertJsonPath('success', false);
    }

    public function test_get_recent_routes_successfully()
    {
        SavedRoute::factory()->create([
            'user_id' => $this->user->id,
            'last_used_at' => now()->subDays(5),
        ]);

        SavedRoute::factory()->create([
            'user_id' => $this->user->id,
            'last_used_at' => now()->subDays(2),
        ]);

        SavedRoute::factory()->create([
            'user_id' => $this->user->id,
            'last_used_at' => now(),
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/saved-routes/recent');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('count', 3);

        $routes = $response->json('recent_routes');
        $this->assertCount(3, $routes);
        // Most recent should be first
        $this->assertNotNull($routes[0]['last_used_at']);
    }

    public function test_get_recent_routes_respects_limit()
    {
        SavedRoute::factory()->count(15)->create([
            'user_id' => $this->user->id,
            'last_used_at' => now(),
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/saved-routes/recent?limit=5');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('count', 5);

        $this->assertCount(5, $response->json('recent_routes'));
    }

    public function test_get_recent_routes_excludes_routes_without_last_used_at()
    {
        SavedRoute::factory()->create([
            'user_id' => $this->user->id,
            'last_used_at' => now(),
        ]);

        SavedRoute::factory()->create([
            'user_id' => $this->user->id,
            'last_used_at' => null,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/saved-routes/recent');

        $response->assertStatus(200)
            ->assertJsonPath('count', 1);
    }

    public function test_get_recent_routes_orders_by_last_used_at()
    {
        $route1 = SavedRoute::factory()->create([
            'user_id' => $this->user->id,
            'last_used_at' => now()->subDays(10),
        ]);

        $route2 = SavedRoute::factory()->create([
            'user_id' => $this->user->id,
            'last_used_at' => now()->subDays(5),
        ]);

        $route3 = SavedRoute::factory()->create([
            'user_id' => $this->user->id,
            'last_used_at' => now(),
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/saved-routes/recent');

        $routes = $response->json('recent_routes');
        // Most recent first
        $this->assertEquals($route3->id, $routes[0]['id']);
        $this->assertEquals($route2->id, $routes[1]['id']);
        $this->assertEquals($route1->id, $routes[2]['id']);
    }

    public function test_get_recent_routes_requires_authentication()
    {
        $response = $this->getJson('/api/v1/saved-routes/recent');

        $response->assertStatus(401);
    }

    public function test_get_recent_routes_filters_by_user()
    {
        $otherUser = User::factory()->create();

        SavedRoute::factory()->create([
            'user_id' => $this->user->id,
            'last_used_at' => now(),
        ]);

        SavedRoute::factory()->create([
            'user_id' => $otherUser->id,
            'last_used_at' => now(),
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/saved-routes/recent');

        $response->assertStatus(200)
            ->assertJsonPath('count', 1);
    }

    public function test_get_recent_routes_validates_limit_parameter()
    {
        SavedRoute::factory()->count(5)->create([
            'user_id' => $this->user->id,
            'last_used_at' => now(),
        ]);

        // Invalid limit (too high) should default to 10
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/saved-routes/recent?limit=200');

        $response->assertStatus(200);
        // Should return 5 routes (all available)
        $this->assertCount(5, $response->json('recent_routes'));

        // Invalid limit (negative) should default to 10
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/saved-routes/recent?limit=-5');

        $response->assertStatus(200);
    }

    public function test_get_recent_routes_returns_empty_when_no_recent_routes()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/saved-routes/recent');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('count', 0)
            ->assertJsonPath('recent_routes', []);
    }
}