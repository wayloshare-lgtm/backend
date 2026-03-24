<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Models\Ride;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ReviewController extends Controller
{
    /**
     * Create a new review
     * POST /api/v1/reviews
     */
    public function createReview(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'ride_id' => 'required|exists:rides,id',
                'reviewee_id' => 'required|exists:users,id',
                'rating' => 'required|integer|min:1|max:5',
                'comment' => 'nullable|string|max:500',
                'categories' => 'nullable|array',
                'categories.*.name' => 'string',
                'categories.*.rating' => 'integer|min:1|max:5',
                'photos' => 'nullable|array',
                'photos.*' => 'string',
            ]);

            $user = auth()->user();
            $ride = Ride::findOrFail($request->ride_id);

            // Verify that the ride exists and the user is part of it
            if ($ride->driver_id !== $user->id && $ride->rider_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'error' => 'Unauthorized',
                    'message' => 'You are not part of this ride',
                ], 403);
            }

            // Verify that the reviewee is the other party in the ride
            $revieweeId = $request->reviewee_id;
            if ($revieweeId !== $ride->driver_id && $revieweeId !== $ride->rider_id) {
                return response()->json([
                    'success' => false,
                    'error' => 'Invalid reviewee',
                    'message' => 'The reviewee must be the other party in the ride',
                ], 422);
            }

            // Verify that the ride is completed
            if ($ride->status !== 'completed') {
                return response()->json([
                    'success' => false,
                    'error' => 'Invalid ride status',
                    'message' => 'You can only review a completed ride',
                ], 422);
            }

            // Prevent self-reviews
            if ($revieweeId === $user->id) {
                return response()->json([
                    'success' => false,
                    'error' => 'Invalid review',
                    'message' => 'You cannot review yourself',
                ], 422);
            }

            // Check if review already exists
            $existingReview = Review::where('ride_id', $ride->id)
                ->where('reviewer_id', $user->id)
                ->where('reviewee_id', $revieweeId)
                ->first();

            if ($existingReview) {
                return response()->json([
                    'success' => false,
                    'error' => 'Review already exists',
                    'message' => 'You have already reviewed this user for this ride',
                ], 409);
            }

            // Create the review
            $review = Review::create([
                'ride_id' => $ride->id,
                'reviewer_id' => $user->id,
                'reviewee_id' => $revieweeId,
                'rating' => $request->rating,
                'comment' => $request->comment,
                'categories' => $request->categories,
                'photos' => $request->photos,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Review created successfully',
                'review' => $this->formatReview($review),
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to create review',
                'message' => $e->getMessage(),
            ], 400);
        }
    }
    /**
     * Rate a passenger
     * POST /api/v1/reviews/rate-passenger
     */
    public function ratePassenger(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'ride_id' => 'required|exists:rides,id',
                'reviewee_id' => 'required|exists:users,id',
                'rating' => 'required|integer|min:1|max:5',
                'comment' => 'nullable|string|max:500',
                'categories' => 'nullable|array',
                'categories.*.name' => 'string',
                'categories.*.rating' => 'integer|min:1|max:5',
                'photos' => 'nullable|array',
                'photos.*' => 'string',
            ]);

            $user = auth()->user();
            $ride = Ride::findOrFail($request->ride_id);

            // Verify that the ride exists and the user is part of it
            if ($ride->driver_id !== $user->id && $ride->rider_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'error' => 'Unauthorized',
                    'message' => 'You are not part of this ride',
                ], 403);
            }

            // Verify that the reviewee is a passenger in the ride (not the driver)
            $revieweeId = $request->reviewee_id;
            if ($revieweeId === $ride->driver_id) {
                return response()->json([
                    'success' => false,
                    'error' => 'Invalid reviewee',
                    'message' => 'You can only rate passengers, not drivers',
                ], 422);
            }

            if ($revieweeId !== $ride->rider_id) {
                return response()->json([
                    'success' => false,
                    'error' => 'Invalid reviewee',
                    'message' => 'The reviewee must be a passenger in this ride',
                ], 422);
            }

            // Verify that the ride is completed
            if ($ride->status !== 'completed') {
                return response()->json([
                    'success' => false,
                    'error' => 'Invalid ride status',
                    'message' => 'You can only review a completed ride',
                ], 422);
            }

            // Prevent self-reviews
            if ($revieweeId === $user->id) {
                return response()->json([
                    'success' => false,
                    'error' => 'Invalid review',
                    'message' => 'You cannot review yourself',
                ], 422);
            }

            // Check if review already exists
            $existingReview = Review::where('ride_id', $ride->id)
                ->where('reviewer_id', $user->id)
                ->where('reviewee_id', $revieweeId)
                ->first();

            if ($existingReview) {
                return response()->json([
                    'success' => false,
                    'error' => 'Review already exists',
                    'message' => 'You have already reviewed this passenger for this ride',
                ], 409);
            }

            // Create the review
            $review = Review::create([
                'ride_id' => $ride->id,
                'reviewer_id' => $user->id,
                'reviewee_id' => $revieweeId,
                'rating' => $request->rating,
                'comment' => $request->comment,
                'categories' => $request->categories,
                'photos' => $request->photos,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Passenger review created successfully',
                'review' => $this->formatReview($review),
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to create review',
                'message' => $e->getMessage(),
            ], 400);
        }
    }



    /**
     * Get a specific review
     * GET /api/v1/reviews/{id}
     */
    public function getReview(Review $review): JsonResponse
    {
        try {
            $review->load(['ride', 'reviewer', 'reviewee']);

            return response()->json([
                'success' => true,
                'review' => $this->formatReview($review),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch review',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get reviews for a user
     * GET /api/v1/reviews/user/{user_id}
     */
    public function getReviewsByUser(Request $request, $userId): JsonResponse
    {
        try {
            $perPage = $request->query('per_page', 10);

            $reviews = Review::where('reviewee_id', $userId)
                ->with(['ride', 'reviewer', 'reviewee'])
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);

            return response()->json([
                'success' => true,
                'reviews' => $reviews->items(),
                'pagination' => [
                    'total' => $reviews->total(),
                    'per_page' => $reviews->perPage(),
                    'current_page' => $reviews->currentPage(),
                    'last_page' => $reviews->lastPage(),
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch reviews',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get reviews for a ride
     * GET /api/v1/reviews/ride/{ride_id}
     */
    public function getReviewsByRide(Request $request, $rideId): JsonResponse
    {
        try {
            $perPage = $request->query('per_page', 10);

            $reviews = Review::where('ride_id', $rideId)
                ->with(['ride', 'reviewer', 'reviewee'])
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);

            return response()->json([
                'success' => true,
                'reviews' => $reviews->items(),
                'pagination' => [
                    'total' => $reviews->total(),
                    'per_page' => $reviews->perPage(),
                    'current_page' => $reviews->currentPage(),
                    'last_page' => $reviews->lastPage(),
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch reviews',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Format review data for response
     */
    private function formatReview(Review $review): array
    {
        return [
            'id' => $review->id,
            'ride_id' => $review->ride_id,
            'reviewer_id' => $review->reviewer_id,
            'reviewee_id' => $review->reviewee_id,
            'rating' => $review->rating,
            'comment' => $review->comment,
            'categories' => $review->categories,
            'photos' => $review->photos,
            'created_at' => $review->created_at,
            'updated_at' => $review->updated_at,
        ];
    }
}
