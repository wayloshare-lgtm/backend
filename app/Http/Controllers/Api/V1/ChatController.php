<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Chat;
use App\Models\Message;
use App\Services\FileUploadService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ChatController extends Controller
{
    private FileUploadService $fileUploadService;

    public function __construct(FileUploadService $fileUploadService)
    {
        $this->fileUploadService = $fileUploadService;
    }

    /**
     * Create a new chat
     */
    public function createChat(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'ride_id' => 'required|exists:rides,id',
            ]);

            $chat = Chat::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Chat created successfully',
                'data' => $chat,
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    /**
     * Get all chats for the authenticated user
     */
    public function listChats(Request $request): JsonResponse
    {
        $userId = auth()->id();

        $chats = Chat::whereHas('ride', function ($query) use ($userId) {
            $query->where('driver_id', $userId)
                  ->orWhere('rider_id', $userId);
        })->with('ride', 'messages')->get();

        return response()->json([
            'success' => true,
            'data' => $chats,
        ]);
    }

    /**
     * Send a message with optional attachment
     */
    public function sendMessage(Request $request, Chat $chat): JsonResponse
    {
        try {
            $validated = $request->validate([
                'message' => 'nullable|string|max:1000',
                'message_type' => 'required|in:text,image,location',
                'attachment' => 'nullable|file|mimes:jpeg,jpg,png,pdf|max:10240',
                'metadata' => 'nullable|string',
            ]);

            $attachment = null;

            // Handle file upload if provided
            if ($request->hasFile('attachment')) {
                try {
                    $attachment = $this->fileUploadService->upload(
                        $request->file('attachment'),
                        'messages'
                    );
                } catch (\Exception $e) {
                    return response()->json([
                        'success' => false,
                        'error' => 'FILE_UPLOAD_FAILED',
                        'message' => $e->getMessage(),
                    ], 422);
                }
            }

            // Sanitize string fields using $request->input() for nullable fields
            $message = $request->input('message') ? strip_tags($request->input('message')) : null;
            $metadata = $request->input('metadata') ? strip_tags($request->input('metadata')) : null;

            $message = Message::create([
                'chat_id' => $chat->id,
                'sender_id' => auth()->id(),
                'message' => $message,
                'message_type' => $validated['message_type'],
                'attachment' => $attachment,
                'metadata' => $metadata,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Message sent successfully',
                'data' => $message,
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    /**
     * Get messages for a chat
     */
    public function getMessages(Request $request, Chat $chat): JsonResponse
    {
        $validated = $request->validate([
            'per_page' => 'nullable|integer|min:1|max:100',
            'message_type' => 'nullable|in:text,image,location',
        ]);

        $userId = auth()->id();

        // Verify user is a participant in the chat
        $isParticipant = $chat->ride()->where(function ($query) use ($userId) {
            $query->where('driver_id', $userId)
                  ->orWhere('rider_id', $userId);
        })->exists();

        // If not a direct participant, check if they're a booking passenger
        if (!$isParticipant) {
            $isParticipant = $chat->ride->bookings()
                ->where('passenger_id', $userId)
                ->exists();
        }

        if (!$isParticipant) {
            return response()->json([
                'success' => false,
                'error' => 'UNAUTHORIZED',
                'message' => 'You are not a participant in this chat',
            ], 403);
        }

        $perPage = $validated['per_page'] ?? 20;
        $query = $chat->messages()->with('sender');

        // Apply message_type filter if provided
        if (!empty($validated['message_type'])) {
            $query->where('message_type', $validated['message_type']);
        }

        $messages = $query->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $messages,
        ]);
    }

    /**
     * Mark messages as read
     */
    public function markAsRead(Request $request, Chat $chat): JsonResponse
    {
        $validated = $request->validate([
            'message_ids' => 'nullable|array',
            'message_ids.*' => 'integer|exists:messages,id',
        ]);

        $userId = auth()->id();
        $now = now();

        // Build query to mark messages as read
        $query = Message::where('chat_id', $chat->id)
            ->where('sender_id', '!=', $userId)
            ->where('is_read', false);

        // If specific message IDs are provided, only mark those
        if (!empty($validated['message_ids'])) {
            $query->whereIn('id', $validated['message_ids']);
        }

        // Count messages before update
        $markedCount = $query->count();

        // Update messages
        $query->update([
            'is_read' => true,
            'read_at' => $now,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Messages marked as read',
            'data' => [
                'marked_count' => $markedCount,
                'updated_at' => true,
            ],
        ]);
    }

    /**
     * Delete a chat
     */
    public function deleteChat(Chat $chat): JsonResponse
    {
        $chat->delete();

        return response()->json([
            'success' => true,
            'message' => 'Chat deleted successfully',
        ]);
    }
}
