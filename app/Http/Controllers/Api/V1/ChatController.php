<?php

namespace App\Http\Controllers\Api\V1;

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
        $validated = $request->validate([
            'ride_id' => 'required|exists:rides,id',
        ]);

        $chat = Chat::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Chat created successfully',
            'data' => $chat,
        ], 201);
    }

    /**
     * Get all chats for the authenticated user
     */
    public function listChats(Request $request): JsonResponse
    {
        $userId = auth()->id();

        $chats = Chat::whereHas('ride', function ($query) use ($userId) {
            $query->where('driver_id', $userId)
                  ->orWhere('passenger_id', $userId);
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
        $validated = $request->validate([
            'message' => 'nullable|string|max:1000',
            'message_type' => 'required|in:text,image,location',
            'attachment' => 'nullable|file|mimes:jpeg,png,pdf|max:10240',
            'metadata' => 'nullable|json',
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

        $message = Message::create([
            'chat_id' => $chat->id,
            'sender_id' => auth()->id(),
            'message' => $validated['message'] ?? null,
            'message_type' => $validated['message_type'],
            'attachment' => $attachment,
            'metadata' => $validated['metadata'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Message sent successfully',
            'data' => $message,
        ], 201);
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
                'updated_at' => $now,
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
