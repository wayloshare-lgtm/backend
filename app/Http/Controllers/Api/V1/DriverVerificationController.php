<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\DriverVerification;
use App\Services\FileUploadService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DriverVerificationController extends Controller
{
    private FileUploadService $fileUploadService;

    public function __construct(FileUploadService $fileUploadService)
    {
        $this->fileUploadService = $fileUploadService;
    }

    /**
     * Create or update driver verification
     * POST /api/v1/driver/verification
     */
    public function createOrUpdateVerification(Request $request): JsonResponse
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
                'dl_number' => 'nullable|string|max:255',
                'dl_expiry_date' => 'nullable|date|after:today',
                'rc_number' => 'nullable|string|max:255',
            ]);

            // Get or create verification record
            $verification = DriverVerification::firstOrCreate(
                ['user_id' => $user->id],
                ['verification_status' => 'pending']
            );

            // Update with provided data
            $updateData = $request->only(['dl_number', 'dl_expiry_date', 'rc_number']);
            $updateData = array_filter($updateData, fn($value) => $value !== null);

            if (!empty($updateData)) {
                $verification->update($updateData);
            }

            return response()->json([
                'success' => true,
                'message' => 'Verification record updated successfully',
                'verification' => $this->formatVerification($verification),
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
                'error' => 'Failed to create or update verification',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get driver verification status
     * GET /api/v1/driver/verification/status
     */
    public function getVerificationStatus(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'error' => 'User not authenticated',
                ], 401);
            }

            $verification = DriverVerification::where('user_id', $user->id)->first();

            if (!$verification) {
                return response()->json([
                    'success' => true,
                    'verification' => null,
                    'message' => 'No verification record found',
                ], 200);
            }

            return response()->json([
                'success' => true,
                'verification' => $this->formatVerification($verification),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch verification status',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Upload driving license front image
     * POST /api/v1/driver/verification/dl-front-image
     */
    public function uploadDlFrontImage(Request $request): JsonResponse
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
                'dl_front_image' => 'required|file|mimes:jpeg,png,pdf|max:10240',
            ]);

            $file = $request->file('dl_front_image');

            // Validate file
            $validationErrors = $this->fileUploadService->validate($file);
            if (!empty($validationErrors)) {
                return response()->json([
                    'success' => false,
                    'error' => 'File validation failed',
                    'errors' => $validationErrors,
                ], 422);
            }

            // Get or create verification record
            $verification = DriverVerification::firstOrCreate(
                ['user_id' => $user->id],
                ['verification_status' => 'pending']
            );

            // Delete old image if exists
            if ($verification->dl_front_image) {
                try {
                    $this->fileUploadService->delete($verification->dl_front_image);
                } catch (\Exception $e) {
                    // Log error but continue with upload
                }
            }

            // Upload new image
            $filePath = $this->fileUploadService->upload($file, 'driver-verifications/dl-front');
            $fileUrl = $this->fileUploadService->getUrl($filePath);

            // Update verification record
            $verification->update([
                'dl_front_image' => $filePath,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Driving license front image uploaded successfully',
                'dl_front_image_url' => $fileUrl,
                'verification' => $this->formatVerification($verification),
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
                'error' => 'Failed to upload driving license front image',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Upload driving license back image
     * POST /api/v1/driver/verification/dl-back-image
     */
    public function uploadDlBackImage(Request $request): JsonResponse
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
                'dl_back_image' => 'required|file|mimes:jpeg,png,pdf|max:10240',
            ]);

            $file = $request->file('dl_back_image');

            // Validate file
            $validationErrors = $this->fileUploadService->validate($file);
            if (!empty($validationErrors)) {
                return response()->json([
                    'success' => false,
                    'error' => 'File validation failed',
                    'errors' => $validationErrors,
                ], 422);
            }

            // Get or create verification record
            $verification = DriverVerification::firstOrCreate(
                ['user_id' => $user->id],
                ['verification_status' => 'pending']
            );

            // Delete old image if exists
            if ($verification->dl_back_image) {
                try {
                    $this->fileUploadService->delete($verification->dl_back_image);
                } catch (\Exception $e) {
                    // Log error but continue with upload
                }
            }

            // Upload new image
            $filePath = $this->fileUploadService->upload($file, 'driver-verifications/dl-back');
            $fileUrl = $this->fileUploadService->getUrl($filePath);

            // Update verification record
            $verification->update([
                'dl_back_image' => $filePath,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Driving license back image uploaded successfully',
                'dl_back_image_url' => $fileUrl,
                'verification' => $this->formatVerification($verification),
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
                'error' => 'Failed to upload driving license back image',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Upload registration certificate front image
     * POST /api/v1/driver/verification/rc-front-image
     */
    public function uploadRcFrontImage(Request $request): JsonResponse
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
                'rc_front_image' => 'required|file|mimes:jpeg,png,pdf|max:10240',
            ]);

            $file = $request->file('rc_front_image');

            // Validate file
            $validationErrors = $this->fileUploadService->validate($file);
            if (!empty($validationErrors)) {
                return response()->json([
                    'success' => false,
                    'error' => 'File validation failed',
                    'errors' => $validationErrors,
                ], 422);
            }

            // Get or create verification record
            $verification = DriverVerification::firstOrCreate(
                ['user_id' => $user->id],
                ['verification_status' => 'pending']
            );

            // Delete old image if exists
            if ($verification->rc_front_image) {
                try {
                    $this->fileUploadService->delete($verification->rc_front_image);
                } catch (\Exception $e) {
                    // Log error but continue with upload
                }
            }

            // Upload new image
            $filePath = $this->fileUploadService->upload($file, 'driver-verifications/rc-front');
            $fileUrl = $this->fileUploadService->getUrl($filePath);

            // Update verification record
            $verification->update([
                'rc_front_image' => $filePath,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Registration certificate front image uploaded successfully',
                'rc_front_image_url' => $fileUrl,
                'verification' => $this->formatVerification($verification),
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
                'error' => 'Failed to upload registration certificate front image',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Upload registration certificate back image
     * POST /api/v1/driver/verification/rc-back-image
     */
    public function uploadRcBackImage(Request $request): JsonResponse
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
                'rc_back_image' => 'required|file|mimes:jpeg,png,pdf|max:10240',
            ]);

            $file = $request->file('rc_back_image');

            // Validate file
            $validationErrors = $this->fileUploadService->validate($file);
            if (!empty($validationErrors)) {
                return response()->json([
                    'success' => false,
                    'error' => 'File validation failed',
                    'errors' => $validationErrors,
                ], 422);
            }

            // Get or create verification record
            $verification = DriverVerification::firstOrCreate(
                ['user_id' => $user->id],
                ['verification_status' => 'pending']
            );

            // Delete old image if exists
            if ($verification->rc_back_image) {
                try {
                    $this->fileUploadService->delete($verification->rc_back_image);
                } catch (\Exception $e) {
                    // Log error but continue with upload
                }
            }

            // Upload new image
            $filePath = $this->fileUploadService->upload($file, 'driver-verifications/rc-back');
            $fileUrl = $this->fileUploadService->getUrl($filePath);

            // Update verification record
            $verification->update([
                'rc_back_image' => $filePath,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Registration certificate back image uploaded successfully',
                'rc_back_image_url' => $fileUrl,
                'verification' => $this->formatVerification($verification),
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
                'error' => 'Failed to upload registration certificate back image',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get all verification documents
     * GET /api/v1/driver/verification/documents
     */
    public function getDocuments(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'error' => 'User not authenticated',
                ], 401);
            }

            $verification = DriverVerification::where('user_id', $user->id)->first();

            if (!$verification) {
                return response()->json([
                    'success' => true,
                    'documents' => null,
                    'message' => 'No verification documents found',
                ], 200);
            }

            $documents = [
                'dl_front_image' => $verification->dl_front_image ? $this->fileUploadService->getUrl($verification->dl_front_image) : null,
                'dl_back_image' => $verification->dl_back_image ? $this->fileUploadService->getUrl($verification->dl_back_image) : null,
                'rc_front_image' => $verification->rc_front_image ? $this->fileUploadService->getUrl($verification->rc_front_image) : null,
                'rc_back_image' => $verification->rc_back_image ? $this->fileUploadService->getUrl($verification->rc_back_image) : null,
            ];

            return response()->json([
                'success' => true,
                'documents' => $documents,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch documents',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Submit verification for review
     * POST /api/v1/driver/verification/submit
     */
    public function submitVerification(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'error' => 'User not authenticated',
                ], 401);
            }

            $verification = DriverVerification::where('user_id', $user->id)->first();

            if (!$verification) {
                return response()->json([
                    'success' => false,
                    'error' => 'No verification record found',
                ], 404);
            }

            // Validate that all required documents are uploaded
            $requiredDocuments = ['dl_number', 'dl_expiry_date', 'dl_front_image', 'dl_back_image', 'rc_number', 'rc_front_image', 'rc_back_image'];
            $missingDocuments = [];

            foreach ($requiredDocuments as $doc) {
                if (empty($verification->$doc)) {
                    $missingDocuments[] = $doc;
                }
            }

            if (!empty($missingDocuments)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Missing required documents',
                    'missing_documents' => $missingDocuments,
                ], 422);
            }

            // Update verification status to pending review
            $verification->update([
                'verification_status' => 'pending',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Verification submitted for review',
                'verification' => $this->formatVerification($verification),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to submit verification',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get KYC status
     * GET /api/v1/driver/kyc-status
     */
    public function getKycStatus(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'error' => 'User not authenticated',
                ], 401);
            }

            $verification = DriverVerification::where('user_id', $user->id)->first();

            if (!$verification) {
                return response()->json([
                    'success' => true,
                    'kyc_status' => 'not_started',
                    'message' => 'KYC process not started',
                ], 200);
            }

            $kycStatus = [
                'status' => $verification->verification_status,
                'verified_at' => $verification->verified_at,
                'rejection_reason' => $verification->rejection_reason,
                'documents_uploaded' => [
                    'dl_front' => !empty($verification->dl_front_image),
                    'dl_back' => !empty($verification->dl_back_image),
                    'rc_front' => !empty($verification->rc_front_image),
                    'rc_back' => !empty($verification->rc_back_image),
                ],
                'details_filled' => [
                    'dl_number' => !empty($verification->dl_number),
                    'dl_expiry_date' => !empty($verification->dl_expiry_date),
                    'rc_number' => !empty($verification->rc_number),
                ],
            ];

            return response()->json([
                'success' => true,
                'kyc_status' => $kycStatus,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch KYC status',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Format verification record with signed URLs
     */
    private function formatVerification(DriverVerification $verification): array
    {
        return [
            'id' => $verification->id,
            'user_id' => $verification->user_id,
            'dl_number' => $verification->dl_number,
            'dl_expiry_date' => $verification->dl_expiry_date,
            'dl_front_image' => $verification->dl_front_image ? $this->fileUploadService->getUrl($verification->dl_front_image) : null,
            'dl_back_image' => $verification->dl_back_image ? $this->fileUploadService->getUrl($verification->dl_back_image) : null,
            'rc_number' => $verification->rc_number,
            'rc_front_image' => $verification->rc_front_image ? $this->fileUploadService->getUrl($verification->rc_front_image) : null,
            'rc_back_image' => $verification->rc_back_image ? $this->fileUploadService->getUrl($verification->rc_back_image) : null,
            'verification_status' => $verification->verification_status,
            'rejection_reason' => $verification->rejection_reason,
            'verified_at' => $verification->verified_at,
            'created_at' => $verification->created_at,
            'updated_at' => $verification->updated_at,
        ];
    }
}
