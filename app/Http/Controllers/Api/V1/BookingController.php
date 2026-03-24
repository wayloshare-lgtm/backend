<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Ride;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class BookingController extends Controller
{
    /**
     * Create a new booking
     * POST /api/v1/bookings
     */
    public function createBooking(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'ride_id' => 'required|exists:rides,id',
                'seats_booked' => 'required|integer|min:1|max:8',
                'passenger_name' => 'required|string|max:255',
                'passenger_phone' => 'required|string|max:20',
                'special_instructions' => 'nullable|string|max:1000',
                'luggage_info' => 'nullable|string|max:1000',
                'accessibility_requirements' => 'nullable|string|max:1000',
            ]);

            $user = auth()->user();
            $ride = Ride::findOrFail($request->ride_id);

            // Check if ride exists and is available
            if ($ride->status !== 'requested') {
                return response()->json([
                    'success' => false,
                    'error' => 'Ride is not available for booking',
                ], 409);
            }

            // Check if passenger already has a booking for this ride
            $existingBooking = Booking::where('ride_id', $ride->id)
                ->where('passenger_id', $user->id)
                ->first();

            if ($existingBooking) {
                return response()->json([
                    'success' => false,
                    'error' => 'You already have a booking for this ride',
                ], 409);
            }

            // Create the booking
            $booking = Booking::create([
                'ride_id' => $ride->id,
                'passenger_id' => $user->id,
                'seats_booked' => $request->seats_booked,
                'passenger_name' => $request->passenger_name,
                'passenger_phone' => $request->passenger_phone,
                'special_instructions' => $request->special_instructions,
                'luggage_info' => $request->luggage_info,
                'accessibility_requirements' => $request->accessibility_requirements,
                'booking_status' => 'pending',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Booking created successfully',
                'booking' => $this->formatBooking($booking),
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
                'error' => 'Failed to create booking',
                'message' => $e->getMessage(),
            ], 400);
        }
    }
    /**
     * List all bookings for the authenticated user
     * GET /api/v1/bookings
     */
    public function listBookings(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();
            $perPage = $request->query('per_page', 10);
            $status = $request->query('status');

            $query = Booking::where('passenger_id', $user->id)
                ->with(['ride', 'ride.driver'])
                ->orderBy('created_at', 'desc');

            // Filter by status if provided
            if ($status) {
                $query->where('booking_status', $status);
            }

            $bookings = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'bookings' => $bookings->items(),
                'pagination' => [
                    'total' => $bookings->total(),
                    'per_page' => $bookings->perPage(),
                    'current_page' => $bookings->currentPage(),
                    'last_page' => $bookings->lastPage(),
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch bookings',
                'message' => $e->getMessage(),
            ], 400);
        }
    }



    /**
     * Cancel a booking
     * POST /api/v1/bookings/{id}/cancel
     */
    public function cancelBooking(Request $request, Booking $booking): JsonResponse
    {
        try {
            $user = auth()->user();

            // Check authorization
            if ($booking->passenger_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'error' => 'Unauthorized',
                ], 403);
            }

            // Check if booking can be cancelled
            if ($booking->booking_status === 'cancelled') {
                return response()->json([
                    'success' => false,
                    'error' => 'Booking is already cancelled',
                ], 409);
            }

            if ($booking->booking_status === 'completed') {
                return response()->json([
                    'success' => false,
                    'error' => 'Cannot cancel a completed booking',
                ], 409);
            }

            $request->validate([
                'cancellation_reason' => 'nullable|string|max:500',
            ]);

            // Update booking status
            $booking->update([
                'booking_status' => 'cancelled',
                'cancellation_reason' => $request->cancellation_reason,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Booking cancelled successfully',
                'booking' => $this->formatBooking($booking),
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to cancel booking',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get booking history for the authenticated user
     * GET /api/v1/bookings/history
     */
    public function getBookingHistory(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();
            $perPage = $request->query('per_page', 15);
            $status = $request->query('status');

            $query = Booking::where('passenger_id', $user->id)
                ->with(['ride', 'ride.driver', 'passenger'])
                ->orderBy('created_at', 'desc');

            // Filter by status if provided
            if ($status) {
                $query->where('booking_status', $status);
            }

            $bookings = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'message' => 'Booking history retrieved successfully',
                'bookings' => $bookings->items(),
                'pagination' => [
                    'total' => $bookings->total(),
                    'per_page' => $bookings->perPage(),
                    'current_page' => $bookings->currentPage(),
                    'last_page' => $bookings->lastPage(),
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch booking history',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get booking details
     * GET /api/v1/bookings/{id}
     */
    public function getBookingDetails(Booking $booking): JsonResponse
    {
        try {
            $user = auth()->user();

            // Check authorization
            if ($booking->passenger_id !== $user->id && $booking->ride->driver_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'error' => 'Unauthorized',
                ], 403);
            }

            $booking->load(['ride', 'ride.driver', 'passenger']);

            return response()->json([
                'success' => true,
                'booking' => $this->formatBooking($booking),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch booking details',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Format booking data for response
     */
    private function formatBooking(Booking $booking): array
    {
        return [
            'id' => $booking->id,
            'ride_id' => $booking->ride_id,
            'passenger_id' => $booking->passenger_id,
            'seats_booked' => $booking->seats_booked,
            'passenger_name' => $booking->passenger_name,
            'passenger_phone' => $booking->passenger_phone,
            'special_instructions' => $booking->special_instructions,
            'luggage_info' => $booking->luggage_info,
            'accessibility_requirements' => $booking->accessibility_requirements,
            'booking_status' => $booking->booking_status,
            'cancellation_reason' => $booking->cancellation_reason,
            'created_at' => $booking->created_at,
            'updated_at' => $booking->updated_at,
        ];
    }
}
