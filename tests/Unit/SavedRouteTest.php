<?php

namespace Tests\Unit;

use App\Models\SavedRoute;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SavedRouteTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that saved route can be created with all attributes
     */
    public function test_saved_route_can_be_created(): void
    {
        $user = User::factory()->create();

        $route = SavedRoute::create([
            'user_id' => $user->id,
            'from_location' => 'Home',
            'to_location' => 'Office',
            'is_pinned' => true,
            'last_used_at' => now(),
        ]);

        $this->assertNotNull($route->id);
        $this->assertEquals($user->id, $route->user_id);
        $this->assertEquals('Home', $route->from_location);
        $this->assertEquals('Office', $route->to_location);
        $this->assertTrue($route->is_pinned);
    }

    /**
     * Test that saved route belongs to a user
     */
    public function test_saved_route_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $route = SavedRoute::factory()->create(['user_id' => $user->id]);

        $this->assertTrue($route->user()->is($user));
    }

    /**
     * Test that is_pinned field is cast to boolean
     */
    public function test_is_pinned_field_is_cast_to_boolean(): void
    {
        $user = User::factory()->create();

        $route = SavedRoute::create([
            'user_id' => $user->id,
            'from_location' => 'Home',
            'to_location' => 'Office',
            'is_pinned' => 1,
        ]);

        $this->assertIsBool($route->is_pinned);
        $this->assertTrue($route->is_pinned);
    }

    /**
     * Test that is_pinned defaults to false
     */
    public function test_is_pinned_defaults_to_false(): void
    {
        $user = User::factory()->create();

        $route = SavedRoute::create([
            'user_id' => $user->id,
            'from_location' => 'Home',
            'to_location' => 'Office',
        ]);

        $route->refresh();
        $this->assertFalse($route->is_pinned);
    }

    /**
     * Test that last_used_at is cast to datetime
     */
    public function test_last_used_at_is_cast_to_datetime(): void
    {
        $user = User::factory()->create();
        $lastUsedAt = now();

        $route = SavedRoute::create([
            'user_id' => $user->id,
            'from_location' => 'Home',
            'to_location' => 'Office',
            'last_used_at' => $lastUsedAt,
        ]);

        $this->assertIsObject($route->last_used_at);
    }

    /**
     * Test that last_used_at can be null
     */
    public function test_last_used_at_can_be_null(): void
    {
        $user = User::factory()->create();

        $route = SavedRoute::create([
            'user_id' => $user->id,
            'from_location' => 'Home',
            'to_location' => 'Office',
            'last_used_at' => null,
        ]);

        $this->assertNull($route->last_used_at);
    }

    /**
     * Test that from_location can be null
     */
    public function test_from_location_can_be_null(): void
    {
        $user = User::factory()->create();

        $route = SavedRoute::create([
            'user_id' => $user->id,
            'from_location' => null,
            'to_location' => 'Office',
        ]);

        $this->assertNull($route->from_location);
    }

    /**
     * Test that to_location can be null
     */
    public function test_to_location_can_be_null(): void
    {
        $user = User::factory()->create();

        $route = SavedRoute::create([
            'user_id' => $user->id,
            'from_location' => 'Home',
            'to_location' => null,
        ]);

        $this->assertNull($route->to_location);
    }

    /**
     * Test that saved route can be updated
     */
    public function test_saved_route_can_be_updated(): void
    {
        $user = User::factory()->create();
        $route = SavedRoute::factory()->create(['user_id' => $user->id]);

        $route->update([
            'from_location' => 'New Home',
            'to_location' => 'New Office',
            'is_pinned' => true,
        ]);

        $this->assertEquals('New Home', $route->from_location);
        $this->assertEquals('New Office', $route->to_location);
        $this->assertTrue($route->is_pinned);
    }

    /**
     * Test that saved route is deleted when user is deleted
     */
    public function test_saved_route_deleted_when_user_deleted(): void
    {
        $user = User::factory()->create();
        $route = SavedRoute::factory()->create(['user_id' => $user->id]);

        $routeId = $route->id;
        $user->delete();

        $this->assertNull(SavedRoute::find($routeId));
    }

    /**
     * Test that user can have multiple saved routes
     */
    public function test_user_can_have_multiple_saved_routes(): void
    {
        $user = User::factory()->create();

        SavedRoute::factory()->create(['user_id' => $user->id]);
        SavedRoute::factory()->create(['user_id' => $user->id]);
        SavedRoute::factory()->create(['user_id' => $user->id]);

        $this->assertEquals(3, $user->savedRoutes()->count());
    }

    /**
     * Test that last_used_at can be updated
     */
    public function test_last_used_at_can_be_updated(): void
    {
        $user = User::factory()->create();
        $route = SavedRoute::factory()->create([
            'user_id' => $user->id,
            'last_used_at' => null,
        ]);

        $newTime = now();
        $route->update(['last_used_at' => $newTime]);

        $this->assertNotNull($route->last_used_at);
    }
}
