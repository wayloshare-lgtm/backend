<?php

namespace Tests\Feature;

use App\Models\Review;
use App\Models\Ride;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $driver;
    protected User $passenger;
    protected Ride $ride;

    protected function setUp(): void
    {
        parent::setUp();

        // Create driver and passenger users
        $this->driver = User::factory()->create();
        $this->passenger = User::factory()->create();

        // Create a ride
        $this->ride = Ride::factory()->create([
            'driver_id' => $this->driver->id,
            'rider_id' => $this->passenger->id,
            'status' => 'completed',
        ]);
    }

    /**
     * Test creating a review with valid data
     */
    public function test_create_review_with_valid_data(): void
    {
        $response = $this->actingAs($this->passenger)->postJson('/api/v1/reviews', [
            'ride_id' => $this->ride->id,
            'reviewee_id' => $this->driver->id,
            'rating' => 5,
            'comment' => 'Great driver!',
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('review.rating', 5);
        $response->assertJsonPath('review.comment', 'Great driver!');

        $this->assertDatabaseHas('reviews', [
            'ride_id' => $this->ride->id,
            'reviewer_id' => $this->passenger->id,
            'reviewee_id' => $this->driver->id,
            'rating' => 5,
        ]);
    }

    /**
     * Test creating a review with minimum rating
     */
    public function test_create_review_with_minimum_rating(): void
    {
        $response = $this->actingAs($this->passenger)->postJson('/api/v1/reviews', [
            'ride_id' => $this->ride->id,
            'reviewee_id' => $this->driver->id,
            'rating' => 1,
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('review.rating', 1);
    }

    /**
     * Test creating a review with maximum rating
     */
    public function test_create_review_with_maximum_rating(): void
    {
        $response = $this->actingAs($this->passenger)->postJson('/api/v1/reviews', [
            'ride_id' => $this->ride->id,
            'reviewee_id' => $this->driver->id,
            'rating' => 5,
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('review.rating', 5);
    }

    /**
     * Test creating a review with rating below minimum fails
     */
    public function test_create_review_with_rating_below_minimum_fails(): void
    {
        $response = $this->actingAs($this->passenger)->postJson('/api/v1/reviews', [
            'ride_id' => $this->ride->id,
            'reviewee_id' => $this->driver->id,
            'rating' => 0,
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
    }

    /**
     * Test creating a review with rating above maximum fails
     */
    public function test_create_review_with_rating_above_maximum_fails(): void
    {
        $response = $this->actingAs($this->passenger)->postJson('/api/v1/reviews', [
            'ride_id' => $this->ride->id,
            'reviewee_id' => $this->driver->id,
            'rating' => 6,
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
    }

    /**
     * Test creating a review with comment at max length
     */
    public function test_create_review_with_comment_at_max_length(): void
    {
        $comment = str_repeat('a', 500);

        $response = $this->actingAs($this->passenger)->postJson('/api/v1/reviews', [
            'ride_id' => $this->ride->id,
            'reviewee_id' => $this->driver->id,
            'rating' => 5,
            'comment' => $comment,
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('review.comment', $comment);
    }

    /**
     * Test creating a review with comment exceeding max length fails
     */
    public function test_create_review_with_comment_exceeding_max_length_fails(): void
    {
        $comment = str_repeat('a', 501);

        $response = $this->actingAs($this->passenger)->postJson('/api/v1/reviews', [
            'ride_id' => $this->ride->id,
            'reviewee_id' => $this->driver->id,
            'rating' => 5,
            'comment' => $comment,
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
    }

    /**
     * Test creating a review with category ratings
     */
    public function test_create_review_with_category_ratings(): void
    {
        $categories = [
            ['name' => 'cleanliness', 'rating' => 5],
            ['name' => 'driving', 'rating' => 4],
            ['name' => 'communication', 'rating' => 5],
        ];

        $response = $this->actingAs($this->passenger)->postJson('/api/v1/reviews', [
            'ride_id' => $this->ride->id,
            'reviewee_id' => $this->driver->id,
            'rating' => 5,
            'categories' => $categories,
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('review.categories', $categories);
    }

    /**
     * Test creating a review with photos
     */
    public function test_create_review_with_photos(): void
    {
        $photos = ['photo1.jpg', 'photo2.jpg'];

        $response = $this->actingAs($this->passenger)->postJson('/api/v1/reviews', [
            'ride_id' => $this->ride->id,
            'reviewee_id' => $this->driver->id,
            'rating' => 5,
            'photos' => $photos,
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('review.photos', $photos);
    }

    /**
     * Test creating a review without authentication fails
     */
    public function test_create_review_without_authentication_fails(): void
    {
        $response = $this->postJson('/api/v1/reviews', [
            'ride_id' => $this->ride->id,
            'reviewee_id' => $this->driver->id,
            'rating' => 5,
        ]);

        $response->assertStatus(401);
    }

    /**
     * Test creating a review for non-existent ride fails
     */
    public function test_create_review_for_non_existent_ride_fails(): void
    {
        $response = $this->actingAs($this->passenger)->postJson('/api/v1/reviews', [
            'ride_id' => 9999,
            'reviewee_id' => $this->driver->id,
            'rating' => 5,
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
    }

    /**
     * Test creating a review for non-existent reviewee fails
     */
    public function test_create_review_for_non_existent_reviewee_fails(): void
    {
        $response = $this->actingAs($this->passenger)->postJson('/api/v1/reviews', [
            'ride_id' => $this->ride->id,
            'reviewee_id' => 9999,
            'rating' => 5,
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
    }

    /**
     * Test creating a review when user is not part of the ride fails
     */
    public function test_create_review_when_user_not_part_of_ride_fails(): void
    {
        $otherUser = User::factory()->create();

        $response = $this->actingAs($otherUser)->postJson('/api/v1/reviews', [
            'ride_id' => $this->ride->id,
            'reviewee_id' => $this->driver->id,
            'rating' => 5,
        ]);

        $response->assertStatus(403);
        $response->assertJsonPath('success', false);
    }

    /**
     * Test creating a self-review fails
     */
    public function test_create_self_review_fails(): void
    {
        $response = $this->actingAs($this->passenger)->postJson('/api/v1/reviews', [
            'ride_id' => $this->ride->id,
            'reviewee_id' => $this->passenger->id,
            'rating' => 5,
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
    }

    /**
     * Test creating duplicate review fails
     */
    public function test_create_duplicate_review_fails(): void
    {
        // Create first review
        $this->actingAs($this->passenger)->postJson('/api/v1/reviews', [
            'ride_id' => $this->ride->id,
            'reviewee_id' => $this->driver->id,
            'rating' => 5,
        ]);

        // Try to create duplicate
        $response = $this->actingAs($this->passenger)->postJson('/api/v1/reviews', [
            'ride_id' => $this->ride->id,
            'reviewee_id' => $this->driver->id,
            'rating' => 4,
        ]);

        $response->assertStatus(409);
        $response->assertJsonPath('success', false);
    }

    /**
     * Test getting a specific review
     */
    public function test_get_review(): void
    {
        $review = Review::factory()->create([
            'ride_id' => $this->ride->id,
            'reviewer_id' => $this->passenger->id,
            'reviewee_id' => $this->driver->id,
        ]);

        $response = $this->actingAs($this->passenger)->getJson("/api/v1/reviews/{$review->id}");

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('review.id', $review->id);
    }

    /**
     * Test getting reviews for a user
     */
    public function test_get_reviews_for_user(): void
    {
        Review::factory()->count(3)->create([
            'reviewee_id' => $this->driver->id,
        ]);

        $response = $this->actingAs($this->passenger)->getJson("/api/v1/reviews/user/{$this->driver->id}");

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('pagination.total', 3);
    }

    /**
     * Test getting reviews for a ride
     */
    public function test_get_reviews_for_ride(): void
    {
        Review::factory()->count(2)->create([
            'ride_id' => $this->ride->id,
        ]);

        $response = $this->actingAs($this->passenger)->getJson("/api/v1/reviews/ride/{$this->ride->id}");

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('pagination.total', 2);
    }

    /**
     * Test getting reviews with pagination
     */
    public function test_get_reviews_with_pagination(): void
    {
        Review::factory()->count(15)->create([
            'reviewee_id' => $this->driver->id,
        ]);

        $response = $this->actingAs($this->passenger)->getJson("/api/v1/reviews/user/{$this->driver->id}?per_page=5");

        $response->assertStatus(200);
        $response->assertJsonPath('pagination.per_page', 5);
        $response->assertJsonPath('pagination.total', 15);
    }

    /**
     * Test rating a passenger with valid data
     */
    public function test_rate_passenger_with_valid_data(): void
    {
        // Create another passenger for the ride
        $otherPassenger = User::factory()->create();

        // Create a ride with multiple passengers (driver and two passengers)
        $ride = Ride::factory()->create([
            'driver_id' => $this->driver->id,
            'rider_id' => $this->passenger->id,
            'status' => 'completed',
        ]);

        // Rate the other passenger
        $response = $this->actingAs($this->driver)->postJson('/api/v1/reviews/rate-passenger', [
            'ride_id' => $ride->id,
            'reviewee_id' => $this->passenger->id,
            'rating' => 5,
            'comment' => 'Great passenger!',
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('review.rating', 5);
        $response->assertJsonPath('review.comment', 'Great passenger!');

        $this->assertDatabaseHas('reviews', [
            'ride_id' => $ride->id,
            'reviewer_id' => $this->driver->id,
            'reviewee_id' => $this->passenger->id,
            'rating' => 5,
        ]);
    }

    /**
     * Test rating a passenger with minimum rating
     */
    public function test_rate_passenger_with_minimum_rating(): void
    {
        $response = $this->actingAs($this->driver)->postJson('/api/v1/reviews/rate-passenger', [
            'ride_id' => $this->ride->id,
            'reviewee_id' => $this->passenger->id,
            'rating' => 1,
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('review.rating', 1);
    }

    /**
     * Test rating a passenger with maximum rating
     */
    public function test_rate_passenger_with_maximum_rating(): void
    {
        $response = $this->actingAs($this->driver)->postJson('/api/v1/reviews/rate-passenger', [
            'ride_id' => $this->ride->id,
            'reviewee_id' => $this->passenger->id,
            'rating' => 5,
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('review.rating', 5);
    }

    /**
     * Test rating a passenger with rating below minimum fails
     */
    public function test_rate_passenger_with_rating_below_minimum_fails(): void
    {
        $response = $this->actingAs($this->driver)->postJson('/api/v1/reviews/rate-passenger', [
            'ride_id' => $this->ride->id,
            'reviewee_id' => $this->passenger->id,
            'rating' => 0,
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
    }

    /**
     * Test rating a passenger with rating above maximum fails
     */
    public function test_rate_passenger_with_rating_above_maximum_fails(): void
    {
        $response = $this->actingAs($this->driver)->postJson('/api/v1/reviews/rate-passenger', [
            'ride_id' => $this->ride->id,
            'reviewee_id' => $this->passenger->id,
            'rating' => 6,
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
    }

    /**
     * Test rating a passenger with comment at max length
     */
    public function test_rate_passenger_with_comment_at_max_length(): void
    {
        $comment = str_repeat('a', 500);

        $response = $this->actingAs($this->driver)->postJson('/api/v1/reviews/rate-passenger', [
            'ride_id' => $this->ride->id,
            'reviewee_id' => $this->passenger->id,
            'rating' => 5,
            'comment' => $comment,
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('review.comment', $comment);
    }

    /**
     * Test rating a passenger with comment exceeding max length fails
     */
    public function test_rate_passenger_with_comment_exceeding_max_length_fails(): void
    {
        $comment = str_repeat('a', 501);

        $response = $this->actingAs($this->driver)->postJson('/api/v1/reviews/rate-passenger', [
            'ride_id' => $this->ride->id,
            'reviewee_id' => $this->passenger->id,
            'rating' => 5,
            'comment' => $comment,
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
    }

    /**
     * Test rating a passenger with category ratings
     */
    public function test_rate_passenger_with_category_ratings(): void
    {
        $categories = [
            ['name' => 'behavior', 'rating' => 5],
            ['name' => 'communication', 'rating' => 4],
        ];

        $response = $this->actingAs($this->driver)->postJson('/api/v1/reviews/rate-passenger', [
            'ride_id' => $this->ride->id,
            'reviewee_id' => $this->passenger->id,
            'rating' => 5,
            'categories' => $categories,
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('review.categories', $categories);
    }

    /**
     * Test rating a passenger with photos
     */
    public function test_rate_passenger_with_photos(): void
    {
        $photos = ['photo1.jpg', 'photo2.jpg'];

        $response = $this->actingAs($this->driver)->postJson('/api/v1/reviews/rate-passenger', [
            'ride_id' => $this->ride->id,
            'reviewee_id' => $this->passenger->id,
            'rating' => 5,
            'photos' => $photos,
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('review.photos', $photos);
    }

    /**
     * Test rating a passenger without authentication fails
     */
    public function test_rate_passenger_without_authentication_fails(): void
    {
        $response = $this->postJson('/api/v1/reviews/rate-passenger', [
            'ride_id' => $this->ride->id,
            'reviewee_id' => $this->passenger->id,
            'rating' => 5,
        ]);

        $response->assertStatus(401);
    }

    /**
     * Test rating a passenger for non-existent ride fails
     */
    public function test_rate_passenger_for_non_existent_ride_fails(): void
    {
        $response = $this->actingAs($this->driver)->postJson('/api/v1/reviews/rate-passenger', [
            'ride_id' => 9999,
            'reviewee_id' => $this->passenger->id,
            'rating' => 5,
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
    }

    /**
     * Test rating a passenger for non-existent reviewee fails
     */
    public function test_rate_passenger_for_non_existent_reviewee_fails(): void
    {
        $response = $this->actingAs($this->driver)->postJson('/api/v1/reviews/rate-passenger', [
            'ride_id' => $this->ride->id,
            'reviewee_id' => 9999,
            'rating' => 5,
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
    }

    /**
     * Test rating a passenger when user is not part of the ride fails
     */
    public function test_rate_passenger_when_user_not_part_of_ride_fails(): void
    {
        $otherUser = User::factory()->create();

        $response = $this->actingAs($otherUser)->postJson('/api/v1/reviews/rate-passenger', [
            'ride_id' => $this->ride->id,
            'reviewee_id' => $this->passenger->id,
            'rating' => 5,
        ]);

        $response->assertStatus(403);
        $response->assertJsonPath('success', false);
    }

    /**
     * Test rating the driver as a passenger fails
     */
    public function test_rate_driver_as_passenger_fails(): void
    {
        $response = $this->actingAs($this->passenger)->postJson('/api/v1/reviews/rate-passenger', [
            'ride_id' => $this->ride->id,
            'reviewee_id' => $this->driver->id,
            'rating' => 5,
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
        $response->assertJsonPath('message', 'You can only rate passengers, not drivers');
    }

    /**
     * Test rating a passenger who is not in the ride fails
     */
    public function test_rate_passenger_not_in_ride_fails(): void
    {
        $otherPassenger = User::factory()->create();

        $response = $this->actingAs($this->driver)->postJson('/api/v1/reviews/rate-passenger', [
            'ride_id' => $this->ride->id,
            'reviewee_id' => $otherPassenger->id,
            'rating' => 5,
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
        $response->assertJsonPath('message', 'The reviewee must be a passenger in this ride');
    }

    /**
     * Test rating a passenger self-review fails
     */
    public function test_rate_passenger_self_review_fails(): void
    {
        $response = $this->actingAs($this->passenger)->postJson('/api/v1/reviews/rate-passenger', [
            'ride_id' => $this->ride->id,
            'reviewee_id' => $this->passenger->id,
            'rating' => 5,
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
        $response->assertJsonPath('message', 'You cannot review yourself');
    }

    /**
     * Test rating a passenger duplicate review fails
     */
    public function test_rate_passenger_duplicate_review_fails(): void
    {
        // Create first review
        $this->actingAs($this->driver)->postJson('/api/v1/reviews/rate-passenger', [
            'ride_id' => $this->ride->id,
            'reviewee_id' => $this->passenger->id,
            'rating' => 5,
        ]);

        // Try to create duplicate
        $response = $this->actingAs($this->driver)->postJson('/api/v1/reviews/rate-passenger', [
            'ride_id' => $this->ride->id,
            'reviewee_id' => $this->passenger->id,
            'rating' => 4,
        ]);

        $response->assertStatus(409);
        $response->assertJsonPath('success', false);
    }

    /**
     * Test rating a passenger on incomplete ride fails
     */
    public function test_rate_passenger_on_incomplete_ride_fails(): void
    {
        $incompleteRide = Ride::factory()->create([
            'driver_id' => $this->driver->id,
            'rider_id' => $this->passenger->id,
            'status' => 'started',
        ]);

        $response = $this->actingAs($this->driver)->postJson('/api/v1/reviews/rate-passenger', [
            'ride_id' => $incompleteRide->id,
            'reviewee_id' => $this->passenger->id,
            'rating' => 5,
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
        $response->assertJsonPath('message', 'You can only review a completed ride');
    }
}
