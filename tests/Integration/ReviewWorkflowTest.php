<?php

namespace Tests\Integration;

use App\Models\User;
use App\Models\Ride;
use App\Models\Review;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewWorkflowTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test complete review workflow:
     * Complete ride → Passenger reviews driver → Driver reviews passenger
     */
    public function test_complete_review_workflow()
    {
        // Step 1: Create driver and passenger
        $driver = User::factory()->create(['user_preference' => 'driver']);
        $passenger = User::factory()->create(['user_preference' => 'passenger']);

        // Step 2: Create and complete a ride
        $ride = Ride::factory()->completed()->create([
            'driver_id' => $driver->id,
            'rider_id' => $passenger->id,
            'actual_fare' => 500,
        ]);

        // Step 3: Passenger reviews driver
        $response = $this->actingAs($passenger, 'sanctum')
            ->postJson('/api/v1/reviews', [
                'ride_id' => $ride->id,
                'reviewee_id' => $driver->id,
                'rating' => 5,
                'comment' => 'Excellent driver! Very professional and courteous.',
                'categories' => [
                    'cleanliness' => 5,
                    'driving' => 5,
                    'communication' => 4,
                    'vehicle_condition' => 5,
                ],
            ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true]);

        $driverReview = Review::where('ride_id', $ride->id)
            ->where('reviewer_id', $passenger->id)
            ->first();

        $this->assertNotNull($driverReview);
        $this->assertEquals(5, $driverReview->rating);
        $this->assertEquals('Excellent driver! Very professional and courteous.', $driverReview->comment);
        $this->assertEquals(5, $driverReview->categories['cleanliness']);

        // Step 4: Driver reviews passenger
        $response = $this->actingAs($driver, 'sanctum')
            ->postJson('/api/v1/reviews', [
                'ride_id' => $ride->id,
                'reviewee_id' => $passenger->id,
                'rating' => 4,
                'comment' => 'Good passenger, polite and on time.',
                'categories' => [
                    'behavior' => 4,
                    'punctuality' => 5,
                    'cleanliness' => 4,
                ],
            ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true]);

        $passengerReview = Review::where('ride_id', $ride->id)
            ->where('reviewer_id', $driver->id)
            ->first();

        $this->assertNotNull($passengerReview);
        $this->assertEquals(4, $passengerReview->rating);
        $this->assertEquals('Good passenger, polite and on time.', $passengerReview->comment);

        // Step 5: Retrieve reviews for the ride
        $response = $this->actingAs($passenger, 'sanctum')
            ->getJson("/api/v1/reviews/ride/{$ride->id}");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');

        // Step 6: Retrieve reviews for driver
        $response = $this->actingAs($driver, 'sanctum')
            ->getJson("/api/v1/reviews/user/{$driver->id}");

        $response->assertStatus(200);
        $this->assertGreaterThan(0, count($response->json('data')));
    }

    /**
     * Test review rating validation (1-5 stars)
     */
    public function test_review_rating_validation()
    {
        $driver = User::factory()->create(['user_preference' => 'driver']);
        $passenger = User::factory()->create(['user_preference' => 'passenger']);

        $ride = Ride::factory()->completed()->create([
            'driver_id' => $driver->id,
            'rider_id' => $passenger->id,
        ]);

        // Test invalid rating (0)
        $response = $this->actingAs($passenger, 'sanctum')
            ->postJson('/api/v1/reviews', [
                'ride_id' => $ride->id,
                'reviewee_id' => $driver->id,
                'rating' => 0,
                'comment' => 'Bad driver',
            ]);

        $response->assertStatus(422);

        // Test invalid rating (6)
        $response = $this->actingAs($passenger, 'sanctum')
            ->postJson('/api/v1/reviews', [
                'ride_id' => $ride->id,
                'reviewee_id' => $driver->id,
                'rating' => 6,
                'comment' => 'Great driver',
            ]);

        $response->assertStatus(422);

        // Test valid ratings
        for ($rating = 1; $rating <= 5; $rating++) {
            $response = $this->actingAs($passenger, 'sanctum')
                ->postJson('/api/v1/reviews', [
                    'ride_id' => $ride->id,
                    'reviewee_id' => $driver->id,
                    'rating' => $rating,
                    'comment' => "Rating $rating",
                ]);

            $response->assertStatus(201);
        }
    }

    /**
     * Test review with category ratings
     */
    public function test_review_with_category_ratings()
    {
        $driver = User::factory()->create(['user_preference' => 'driver']);
        $passenger = User::factory()->create(['user_preference' => 'passenger']);

        $ride = Ride::factory()->completed()->create([
            'driver_id' => $driver->id,
            'rider_id' => $passenger->id,
        ]);

        $response = $this->actingAs($passenger, 'sanctum')
            ->postJson('/api/v1/reviews', [
                'ride_id' => $ride->id,
                'reviewee_id' => $driver->id,
                'rating' => 4,
                'comment' => 'Good overall',
                'categories' => [
                    'cleanliness' => 5,
                    'driving' => 4,
                    'communication' => 3,
                    'vehicle_condition' => 4,
                    'punctuality' => 5,
                ],
            ]);

        $response->assertStatus(201);

        $review = Review::where('ride_id', $ride->id)->first();
        $this->assertEquals(5, $review->categories['cleanliness']);
        $this->assertEquals(4, $review->categories['driving']);
        $this->assertEquals(3, $review->categories['communication']);
        $this->assertEquals(4, $review->categories['vehicle_condition']);
        $this->assertEquals(5, $review->categories['punctuality']);
    }

    /**
     * Test review with photos
     */
    public function test_review_with_photos()
    {
        $driver = User::factory()->create(['user_preference' => 'driver']);
        $passenger = User::factory()->create(['user_preference' => 'passenger']);

        $ride = Ride::factory()->completed()->create([
            'driver_id' => $driver->id,
            'rider_id' => $passenger->id,
        ]);

        $response = $this->actingAs($passenger, 'sanctum')
            ->postJson('/api/v1/reviews', [
                'ride_id' => $ride->id,
                'reviewee_id' => $driver->id,
                'rating' => 5,
                'comment' => 'Great ride!',
                'photos' => [
                    'reviews/photo1.jpg',
                    'reviews/photo2.jpg',
                    'reviews/photo3.jpg',
                ],
            ]);

        $response->assertStatus(201);

        $review = Review::where('ride_id', $ride->id)->first();
        $this->assertCount(3, $review->photos);
        $this->assertContains('reviews/photo1.jpg', $review->photos);
    }

    /**
     * Test user cannot review themselves
     */
    public function test_user_cannot_review_themselves()
    {
        $driver = User::factory()->create(['user_preference' => 'driver']);

        $ride = Ride::factory()->completed()->create([
            'driver_id' => $driver->id,
            'rider_id' => $driver->id,
        ]);

        $response = $this->actingAs($driver, 'sanctum')
            ->postJson('/api/v1/reviews', [
                'ride_id' => $ride->id,
                'reviewee_id' => $driver->id,
                'rating' => 5,
                'comment' => 'I am great!',
            ]);

        $response->assertStatus(422);
    }

    /**
     * Test user can only review after ride is completed
     */
    public function test_user_can_only_review_completed_ride()
    {
        $driver = User::factory()->create(['user_preference' => 'driver']);
        $passenger = User::factory()->create(['user_preference' => 'passenger']);

        $ride = Ride::factory()->create([
            'driver_id' => $driver->id,
            'rider_id' => $passenger->id,
            'status' => 'in_progress',
        ]);

        $response = $this->actingAs($passenger, 'sanctum')
            ->postJson('/api/v1/reviews', [
                'ride_id' => $ride->id,
                'reviewee_id' => $driver->id,
                'rating' => 5,
                'comment' => 'Great ride!',
            ]);

        $response->assertStatus(422);
    }

    /**
     * Test review retrieval by user
     */
    public function test_review_retrieval_by_user()
    {
        $driver = User::factory()->create(['user_preference' => 'driver']);
        $passenger1 = User::factory()->create(['user_preference' => 'passenger']);
        $passenger2 = User::factory()->create(['user_preference' => 'passenger']);

        // Create two rides and reviews
        $ride1 = Ride::factory()->completed()->create([
            'driver_id' => $driver->id,
            'rider_id' => $passenger1->id,
        ]);

        $ride2 = Ride::factory()->completed()->create([
            'driver_id' => $driver->id,
            'rider_id' => $passenger2->id,
        ]);

        $this->actingAs($passenger1, 'sanctum')
            ->postJson('/api/v1/reviews', [
                'ride_id' => $ride1->id,
                'reviewee_id' => $driver->id,
                'rating' => 5,
                'comment' => 'Excellent!',
            ]);

        $this->actingAs($passenger2, 'sanctum')
            ->postJson('/api/v1/reviews', [
                'ride_id' => $ride2->id,
                'reviewee_id' => $driver->id,
                'rating' => 4,
                'comment' => 'Good!',
            ]);

        // Retrieve all reviews for driver
        $response = $this->actingAs($driver, 'sanctum')
            ->getJson("/api/v1/reviews/user/{$driver->id}");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    /**
     * Test review retrieval by ride
     */
    public function test_review_retrieval_by_ride()
    {
        $driver = User::factory()->create(['user_preference' => 'driver']);
        $passenger = User::factory()->create(['user_preference' => 'passenger']);

        $ride = Ride::factory()->completed()->create([
            'driver_id' => $driver->id,
            'rider_id' => $passenger->id,
        ]);

        // Passenger reviews driver
        $this->actingAs($passenger, 'sanctum')
            ->postJson('/api/v1/reviews', [
                'ride_id' => $ride->id,
                'reviewee_id' => $driver->id,
                'rating' => 5,
                'comment' => 'Excellent driver!',
            ]);

        // Driver reviews passenger
        $this->actingAs($driver, 'sanctum')
            ->postJson('/api/v1/reviews', [
                'ride_id' => $ride->id,
                'reviewee_id' => $passenger->id,
                'rating' => 4,
                'comment' => 'Good passenger!',
            ]);

        // Retrieve reviews for ride
        $response = $this->actingAs($passenger, 'sanctum')
            ->getJson("/api/v1/reviews/ride/{$ride->id}");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    /**
     * Test review comment length validation
     */
    public function test_review_comment_length_validation()
    {
        $driver = User::factory()->create(['user_preference' => 'driver']);
        $passenger = User::factory()->create(['user_preference' => 'passenger']);

        $ride = Ride::factory()->completed()->create([
            'driver_id' => $driver->id,
            'rider_id' => $passenger->id,
        ]);

        // Test with very long comment (should fail if max 500 chars)
        $longComment = str_repeat('a', 501);

        $response = $this->actingAs($passenger, 'sanctum')
            ->postJson('/api/v1/reviews', [
                'ride_id' => $ride->id,
                'reviewee_id' => $driver->id,
                'rating' => 5,
                'comment' => $longComment,
            ]);

        $response->assertStatus(422);

        // Test with valid comment (500 chars)
        $validComment = str_repeat('a', 500);

        $response = $this->actingAs($passenger, 'sanctum')
            ->postJson('/api/v1/reviews', [
                'ride_id' => $ride->id,
                'reviewee_id' => $driver->id,
                'rating' => 5,
                'comment' => $validComment,
            ]);

        $response->assertStatus(201);
    }

    /**
     * Test review retrieval with pagination
     */
    public function test_review_retrieval_with_pagination()
    {
        $driver = User::factory()->create(['user_preference' => 'driver']);

        // Create multiple reviews
        for ($i = 0; $i < 15; $i++) {
            $passenger = User::factory()->create(['user_preference' => 'passenger']);
            $ride = Ride::factory()->completed()->create([
                'driver_id' => $driver->id,
                'rider_id' => $passenger->id,
            ]);

            $this->actingAs($passenger, 'sanctum')
                ->postJson('/api/v1/reviews', [
                    'ride_id' => $ride->id,
                    'reviewee_id' => $driver->id,
                    'rating' => rand(1, 5),
                    'comment' => "Review $i",
                ]);
        }

        // Retrieve first page
        $response = $this->actingAs($driver, 'sanctum')
            ->getJson("/api/v1/reviews/user/{$driver->id}?page=1&per_page=10");

        $response->assertStatus(200);
        $this->assertLessThanOrEqual(10, count($response->json('data')));
    }

    /**
     * Test review cannot be created for non-existent ride
     */
    public function test_review_cannot_be_created_for_nonexistent_ride()
    {
        $driver = User::factory()->create(['user_preference' => 'driver']);
        $passenger = User::factory()->create(['user_preference' => 'passenger']);

        $response = $this->actingAs($passenger, 'sanctum')
            ->postJson('/api/v1/reviews', [
                'ride_id' => 99999,
                'reviewee_id' => $driver->id,
                'rating' => 5,
                'comment' => 'Great ride!',
            ]);

        $response->assertStatus(404);
    }
}
