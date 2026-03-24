<?php

namespace Tests\Unit;

use App\Models\Review;
use App\Models\User;
use App\Models\Ride;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that comment field accepts null values
     */
    public function test_comment_field_can_be_null(): void
    {
        $user = User::factory()->create();
        $ride = Ride::factory()->create(['driver_id' => $user->id]);

        $review = Review::create([
            'ride_id' => $ride->id,
            'reviewer_id' => $user->id,
            'reviewee_id' => $user->id,
            'rating' => 5,
            'comment' => null,
        ]);

        $this->assertNull($review->comment);
    }

    /**
     * Test that comment field accepts strings up to 500 characters
     */
    public function test_comment_field_accepts_up_to_500_characters(): void
    {
        $user = User::factory()->create();
        $ride = Ride::factory()->create(['driver_id' => $user->id]);
        $comment = str_repeat('a', 500);

        $review = Review::create([
            'ride_id' => $ride->id,
            'reviewer_id' => $user->id,
            'reviewee_id' => $user->id,
            'rating' => 5,
            'comment' => $comment,
        ]);

        $this->assertEquals($comment, $review->comment);
        $this->assertEquals(500, strlen($review->comment));
    }

    /**
     * Test that comment field validation enforces max 500 characters
     */
    public function test_comment_field_validation_enforces_max_500_characters(): void
    {
        $rules = Review::rules();
        
        $this->assertArrayHasKey('comment', $rules);
        $this->assertStringContainsString('max:500', $rules['comment']);
    }

    /**
     * Test that comment field is optional
     */
    public function test_comment_field_is_optional(): void
    {
        $rules = Review::rules();
        
        $this->assertArrayHasKey('comment', $rules);
        $this->assertStringContainsString('nullable', $rules['comment']);
    }

    /**
     * Test that comment field accepts various string lengths
     */
    public function test_comment_field_accepts_various_lengths(): void
    {
        $user = User::factory()->create();
        $ride = Ride::factory()->create(['driver_id' => $user->id]);

        $testCases = [
            'Short comment',
            'This is a medium length comment with some details about the ride experience.',
            str_repeat('a', 250),
            str_repeat('a', 500),
        ];

        foreach ($testCases as $comment) {
            $review = Review::create([
                'ride_id' => $ride->id,
                'reviewer_id' => $user->id,
                'reviewee_id' => $user->id,
                'rating' => 5,
                'comment' => $comment,
            ]);

            $this->assertEquals($comment, $review->comment);
        }
    }

    /**
     * Test that categories field can be null
     */
    public function test_categories_field_can_be_null(): void
    {
        $user = User::factory()->create();
        $ride = Ride::factory()->create(['driver_id' => $user->id]);

        $review = Review::create([
            'ride_id' => $ride->id,
            'reviewer_id' => $user->id,
            'reviewee_id' => $user->id,
            'rating' => 5,
            'categories' => null,
        ]);

        $this->assertNull($review->categories);
    }

    /**
     * Test that categories field stores JSON data
     */
    public function test_categories_field_stores_json_data(): void
    {
        $user = User::factory()->create();
        $ride = Ride::factory()->create(['driver_id' => $user->id]);

        $categories = [
            'cleanliness' => 5,
            'driving' => 4,
            'communication' => 5,
            'comfort' => 4,
        ];

        $review = Review::create([
            'ride_id' => $ride->id,
            'reviewer_id' => $user->id,
            'reviewee_id' => $user->id,
            'rating' => 5,
            'categories' => $categories,
        ]);

        $this->assertEquals($categories, $review->categories);
        $this->assertIsArray($review->categories);
    }

    /**
     * Test that categories field is cast as JSON
     */
    public function test_categories_field_is_cast_as_json(): void
    {
        $rules = Review::rules();
        
        $this->assertArrayHasKey('categories', $rules);
        $this->assertStringContainsString('json', $rules['categories']);
    }

    /**
     * Test that categories field accepts flexible category structures
     */
    public function test_categories_field_accepts_flexible_structures(): void
    {
        $user = User::factory()->create();
        $ride = Ride::factory()->create(['driver_id' => $user->id]);

        $testCases = [
            ['cleanliness' => 5],
            ['driving' => 4, 'communication' => 5],
            ['cleanliness' => 5, 'driving' => 4, 'communication' => 5, 'comfort' => 4, 'safety' => 5],
            ['custom_category' => 3, 'another_category' => 4],
        ];

        foreach ($testCases as $categories) {
            $review = Review::create([
                'ride_id' => $ride->id,
                'reviewer_id' => $user->id,
                'reviewee_id' => $user->id,
                'rating' => 5,
                'categories' => $categories,
            ]);

            $this->assertEquals($categories, $review->categories);
        }
    }

    /**
     * Test that categories field is optional in validation
     */
    public function test_categories_field_is_optional_in_validation(): void
    {
        $rules = Review::rules();
        
        $this->assertArrayHasKey('categories', $rules);
        $this->assertStringContainsString('nullable', $rules['categories']);
    }
}
