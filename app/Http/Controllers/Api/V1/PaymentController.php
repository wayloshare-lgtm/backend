<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\PaymentMethod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class PaymentController extends Controller
{
    /**
     * Add a new payment method
     * POST /api/v1/payment-methods
     */
    public function addPaymentMethod(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'error' => 'User not authenticated',
                ], 401);
            }

            $request->validate([
                'payment_type' => 'required|in:card,wallet,upi',
                'payment_details' => 'required|array',
                'is_default' => 'nullable|boolean',
            ]);

            // If this is the first payment method or is_default is true, set it as default
            $isDefault = $request->is_default ?? false;
            if (!$isDefault) {
                $existingCount = PaymentMethod::where('user_id', $user->id)->count();
                $isDefault = $existingCount === 0;
            }

            // If setting as default, unset other defaults
            if ($isDefault) {
                PaymentMethod::where('user_id', $user->id)
                    ->update(['is_default' => false]);
            }

            $paymentMethod = PaymentMethod::create([
                'user_id' => $user->id,
                'payment_type' => $request->payment_type,
                'payment_details' => $request->payment_details,
                'is_default' => $isDefault,
                'is_active' => true,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Payment method added successfully',
                'payment_method' => $this->formatPaymentMethod($paymentMethod),
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
                'error' => 'Failed to add payment method',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get all payment methods for authenticated user
     * GET /api/v1/payment-methods
     */
    public function getPaymentMethods(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'error' => 'User not authenticated',
                ], 401);
            }

            $paymentMethods = PaymentMethod::where('user_id', $user->id)
                ->where('is_active', true)
                ->orderBy('is_default', 'desc')
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Payment methods retrieved successfully',
                'payment_methods' => $paymentMethods->map(fn($pm) => $this->formatPaymentMethod($pm))->toArray(),
                'count' => $paymentMethods->count(),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve payment methods',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Update a payment method
     * PUT /api/v1/payment-methods/{id}
     */
    public function updatePaymentMethod(Request $request, PaymentMethod $paymentMethod): JsonResponse
    {
        try {
            $user = auth()->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'error' => 'User not authenticated',
                ], 401);
            }

            // Check if user owns this payment method
            if ($paymentMethod->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'error' => 'Unauthorized',
                    'message' => 'You do not have permission to update this payment method',
                ], 403);
            }

            $request->validate([
                'payment_type' => 'nullable|in:card,wallet,upi',
                'payment_details' => 'nullable|array',
                'is_active' => 'nullable|boolean',
            ]);

            $paymentMethod->update($request->only([
                'payment_type',
                'payment_details',
                'is_active',
            ]));

            return response()->json([
                'success' => true,
                'message' => 'Payment method updated successfully',
                'payment_method' => $this->formatPaymentMethod($paymentMethod),
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
                'error' => 'Failed to update payment method',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Delete a payment method
     * DELETE /api/v1/payment-methods/{id}
     */
    public function deletePaymentMethod(Request $request, PaymentMethod $paymentMethod): JsonResponse
    {
        try {
            $user = auth()->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'error' => 'User not authenticated',
                ], 401);
            }

            // Check if user owns this payment method
            if ($paymentMethod->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'error' => 'Unauthorized',
                    'message' => 'You do not have permission to delete this payment method',
                ], 403);
            }

            $wasDefault = $paymentMethod->is_default;
            $paymentMethod->delete();

            // If deleted payment method was default, set another as default
            if ($wasDefault) {
                $nextPaymentMethod = PaymentMethod::where('user_id', $user->id)
                    ->where('is_active', true)
                    ->orderBy('created_at', 'desc')
                    ->first();

                if ($nextPaymentMethod) {
                    $nextPaymentMethod->update(['is_default' => true]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Payment method deleted successfully',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to delete payment method',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Set payment method as default
     * POST /api/v1/payment-methods/{id}/set-default
     */
    public function setDefaultPaymentMethod(Request $request, PaymentMethod $paymentMethod): JsonResponse
    {
        try {
            $user = auth()->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'error' => 'User not authenticated',
                ], 401);
            }

            // Check if user owns this payment method
            if ($paymentMethod->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'error' => 'Unauthorized',
                    'message' => 'You do not have permission to update this payment method',
                ], 403);
            }

            // Unset all other payment methods as default for this user
            PaymentMethod::where('user_id', $user->id)
                ->where('id', '!=', $paymentMethod->id)
                ->update(['is_default' => false]);

            // Set this payment method as default
            $paymentMethod->update(['is_default' => true]);

            return response()->json([
                'success' => true,
                'message' => 'Payment method set as default successfully',
                'payment_method' => $this->formatPaymentMethod($paymentMethod),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to set default payment method',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Format payment method response
     */
    private function formatPaymentMethod(PaymentMethod $paymentMethod): array
    {
        return [
            'id' => $paymentMethod->id,
            'user_id' => $paymentMethod->user_id,
            'payment_type' => $paymentMethod->payment_type,
            'payment_details' => $paymentMethod->payment_details,
            'is_default' => $paymentMethod->is_default,
            'is_active' => $paymentMethod->is_active,
            'created_at' => $paymentMethod->created_at,
            'updated_at' => $paymentMethod->updated_at,
        ];
    }
}
