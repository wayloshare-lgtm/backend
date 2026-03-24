<?php

namespace Tests\Feature;

use App\Models\Chat;
use App\Models\Message;
use App\Models\Ride;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $driver;
    protected User $passenger;
    protected Ride $ride;
    protected Chat $chat;

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
            'status' => 'accepted',
        ]);

        // Create a chat for the ride
        $this->chat = Chat::factory()->create([
            'ride_id' => $this->ride->id,
        ]);
    }

    public function test_create_chat_with_valid_ride_id(): void
    {
        $newRide = Ride::factory()->create([
            'driver_id' => $this->driver->id,
            'rider_id' => $this->passenger->id,
            'status' => 'accepted',
        ]);

        $response = $this->actingAs($this->passenger)->postJson('/api/v1/chats', [
            'ride_id' => $newRide->id,
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.ride_id', $newRide->id);

        $this->assertDatabaseHas('chats', [
            'ride_id' => $newRide->id,
        ]);
    }

    public function test_create_chat_with_nonexistent_ride_fails(): void
    {
        $response = $this->actingAs($this->passenger)->postJson('/api/v1/chats', [
            'ride_id' => 99999,
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
    }

    public function test_create_chat_without_ride_id_fails(): void
    {
        $response = $this->actingAs($this->passenger)->postJson('/api/v1/chats', []);

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
    }

    public function test_list_chats_for_driver(): void
    {
        $response = $this->actingAs($this->driver)->getJson('/api/v1/chats');

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals($this->chat->id, $response->json('data.0.id'));
    }

    public function test_list_chats_for_passenger(): void
    {
        $response = $this->actingAs($this->passenger)->getJson('/api/v1/chats');

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals($this->chat->id, $response->json('data.0.id'));
    }

    public function test_list_chats_only_shows_user_chats(): void
    {
        $otherUser = User::factory()->create();

        $response = $this->actingAs($otherUser)->getJson('/api/v1/chats');

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $this->assertCount(0, $response->json('data'));
    }

    public function test_list_chats_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/chats');

        $response->assertStatus(401);
    }

    public function test_send_text_message_successfully(): void
    {
        $response = $this->actingAs($this->passenger)->postJson(
            "/api/v1/chats/{$this->chat->id}/messages",
            [
                'message' => 'Hello, I am on my way!',
                'message_type' => 'text',
            ]
        );

        $response->assertStatus(201);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.message', 'Hello, I am on my way!');
        $response->assertJsonPath('data.message_type', 'text');
        $response->assertJsonPath('data.sender_id', $this->passenger->id);

        $this->assertDatabaseHas('messages', [
            'chat_id' => $this->chat->id,
            'sender_id' => $this->passenger->id,
            'message' => 'Hello, I am on my way!',
            'message_type' => 'text',
        ]);
    }

    public function test_send_message_without_message_type_fails(): void
    {
        $response = $this->actingAs($this->passenger)->postJson(
            "/api/v1/chats/{$this->chat->id}/messages",
            [
                'message' => 'Hello!',
            ]
        );

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
    }

    public function test_send_message_with_invalid_message_type_fails(): void
    {
        $response = $this->actingAs($this->passenger)->postJson(
            "/api/v1/chats/{$this->chat->id}/messages",
            [
                'message' => 'Hello!',
                'message_type' => 'video',
            ]
        );

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
    }

    public function test_send_message_with_max_length(): void
    {
        $longMessage = str_repeat('a', 1000);

        $response = $this->actingAs($this->passenger)->postJson(
            "/api/v1/chats/{$this->chat->id}/messages",
            [
                'message' => $longMessage,
                'message_type' => 'text',
            ]
        );

        $response->assertStatus(201);
        $response->assertJsonPath('success', true);
    }

    public function test_send_message_exceeding_max_length_fails(): void
    {
        $tooLongMessage = str_repeat('a', 1001);

        $response = $this->actingAs($this->passenger)->postJson(
            "/api/v1/chats/{$this->chat->id}/messages",
            [
                'message' => $tooLongMessage,
                'message_type' => 'text',
            ]
        );

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
    }

    public function test_send_image_message_with_attachment(): void
    {
        $file = \Illuminate\Http\UploadedFile::fake()->image('test.jpg', 100, 100);

        $response = $this->actingAs($this->passenger)->postJson(
            "/api/v1/chats/{$this->chat->id}/messages",
            [
                'message_type' => 'image',
                'attachment' => $file,
            ]
        );

        $response->assertStatus(201);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.message_type', 'image');
        $this->assertNotNull($response->json('data.attachment'));
    }

    public function test_send_location_message_with_metadata(): void
    {
        $response = $this->actingAs($this->passenger)->postJson(
            "/api/v1/chats/{$this->chat->id}/messages",
            [
                'message_type' => 'location',
                'metadata' => json_encode([
                    'latitude' => 28.7041,
                    'longitude' => 77.1025,
                ]),
            ]
        );

        $response->assertStatus(201);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.message_type', 'location');
        $this->assertNotNull($response->json('data.metadata'));
    }

    public function test_send_message_without_message_content_for_text_type(): void
    {
        $response = $this->actingAs($this->passenger)->postJson(
            "/api/v1/chats/{$this->chat->id}/messages",
            [
                'message_type' => 'text',
            ]
        );

        // Text messages can have null message if attachment is provided
        $response->assertStatus(201);
    }

    public function test_get_messages_successfully(): void
    {
        // Create multiple messages
        Message::factory()->count(5)->create([
            'chat_id' => $this->chat->id,
            'sender_id' => $this->driver->id,
        ]);

        $response = $this->actingAs($this->passenger)->getJson(
            "/api/v1/chats/{$this->chat->id}/messages"
        );

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $this->assertCount(5, $response->json('data.data'));
    }

    public function test_get_messages_with_pagination(): void
    {
        // Create 25 messages
        Message::factory()->count(25)->create([
            'chat_id' => $this->chat->id,
            'sender_id' => $this->driver->id,
        ]);

        $response = $this->actingAs($this->passenger)->getJson(
            "/api/v1/chats/{$this->chat->id}/messages"
        );

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $this->assertCount(20, $response->json('data.data'));
        $this->assertEquals(25, $response->json('data.total'));
    }

    public function test_get_messages_with_custom_per_page(): void
    {
        // Create 15 messages
        Message::factory()->count(15)->create([
            'chat_id' => $this->chat->id,
            'sender_id' => $this->driver->id,
        ]);

        $response = $this->actingAs($this->passenger)->getJson(
            "/api/v1/chats/{$this->chat->id}/messages?per_page=10"
        );

        $response->assertStatus(200);
        $this->assertCount(10, $response->json('data.data'));
        $this->assertEquals(15, $response->json('data.total'));
    }

    public function test_get_messages_includes_sender_info(): void
    {
        Message::factory()->create([
            'chat_id' => $this->chat->id,
            'sender_id' => $this->driver->id,
            'message' => 'Test message',
        ]);

        $response = $this->actingAs($this->passenger)->getJson(
            "/api/v1/chats/{$this->chat->id}/messages"
        );

        $response->assertStatus(200);
        $messages = $response->json('data.data');
        $this->assertNotNull($messages[0]['sender']);
        $this->assertEquals($this->driver->id, $messages[0]['sender']['id']);
    }

    public function test_get_messages_ordered_by_created_at_descending(): void
    {
        $message1 = Message::factory()->create([
            'chat_id' => $this->chat->id,
            'sender_id' => $this->driver->id,
            'created_at' => now()->subHours(2),
        ]);

        $message2 = Message::factory()->create([
            'chat_id' => $this->chat->id,
            'sender_id' => $this->driver->id,
            'created_at' => now(),
        ]);

        $response = $this->actingAs($this->passenger)->getJson(
            "/api/v1/chats/{$this->chat->id}/messages"
        );

        $response->assertStatus(200);
        $messages = $response->json('data.data');
        // Most recent message should be first
        $this->assertEquals($message2->id, $messages[0]['id']);
        $this->assertEquals($message1->id, $messages[1]['id']);
    }

    public function test_get_messages_with_message_type_filter(): void
    {
        // Create messages of different types
        Message::factory()->create([
            'chat_id' => $this->chat->id,
            'sender_id' => $this->driver->id,
            'message_type' => 'text',
            'message' => 'Hello!',
        ]);

        Message::factory()->create([
            'chat_id' => $this->chat->id,
            'sender_id' => $this->driver->id,
            'message_type' => 'image',
            'attachment' => 'messages/test.jpg',
        ]);

        Message::factory()->create([
            'chat_id' => $this->chat->id,
            'sender_id' => $this->driver->id,
            'message_type' => 'location',
            'metadata' => json_encode(['latitude' => 28.7041, 'longitude' => 77.1025]),
        ]);

        // Filter by text messages
        $response = $this->actingAs($this->passenger)->getJson(
            "/api/v1/chats/{$this->chat->id}/messages?message_type=text"
        );

        $response->assertStatus(200);
        $messages = $response->json('data.data');
        $this->assertCount(1, $messages);
        $this->assertEquals('text', $messages[0]['message_type']);
    }

    public function test_get_messages_with_image_type_filter(): void
    {
        // Create messages of different types
        Message::factory()->create([
            'chat_id' => $this->chat->id,
            'sender_id' => $this->driver->id,
            'message_type' => 'text',
        ]);

        Message::factory()->create([
            'chat_id' => $this->chat->id,
            'sender_id' => $this->driver->id,
            'message_type' => 'image',
        ]);

        // Filter by image messages
        $response = $this->actingAs($this->passenger)->getJson(
            "/api/v1/chats/{$this->chat->id}/messages?message_type=image"
        );

        $response->assertStatus(200);
        $messages = $response->json('data.data');
        $this->assertCount(1, $messages);
        $this->assertEquals('image', $messages[0]['message_type']);
    }

    public function test_get_messages_with_location_type_filter(): void
    {
        // Create messages of different types
        Message::factory()->create([
            'chat_id' => $this->chat->id,
            'sender_id' => $this->driver->id,
            'message_type' => 'text',
        ]);

        Message::factory()->create([
            'chat_id' => $this->chat->id,
            'sender_id' => $this->driver->id,
            'message_type' => 'location',
        ]);

        // Filter by location messages
        $response = $this->actingAs($this->passenger)->getJson(
            "/api/v1/chats/{$this->chat->id}/messages?message_type=location"
        );

        $response->assertStatus(200);
        $messages = $response->json('data.data');
        $this->assertCount(1, $messages);
        $this->assertEquals('location', $messages[0]['message_type']);
    }

    public function test_get_messages_denies_non_participant(): void
    {
        $otherUser = User::factory()->create();

        $response = $this->actingAs($otherUser)->getJson(
            "/api/v1/chats/{$this->chat->id}/messages"
        );

        $response->assertStatus(403);
        $response->assertJsonPath('success', false);
        $response->assertJsonPath('error', 'UNAUTHORIZED');
    }

    public function test_mark_messages_as_read_successfully(): void
    {
        // Create unread messages from driver
        Message::factory()->count(3)->create([
            'chat_id' => $this->chat->id,
            'sender_id' => $this->driver->id,
            'is_read' => false,
        ]);

        $response = $this->actingAs($this->passenger)->postJson(
            "/api/v1/chats/{$this->chat->id}/mark-read"
        );

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.marked_count', 3);
        $response->assertJsonPath('data.updated_at', true);

        // Verify messages are marked as read
        $this->assertDatabaseHas('messages', [
            'chat_id' => $this->chat->id,
            'sender_id' => $this->driver->id,
            'is_read' => true,
        ]);
    }

    public function test_mark_as_read_only_marks_other_users_messages(): void
    {
        // Create messages from driver
        Message::factory()->count(2)->create([
            'chat_id' => $this->chat->id,
            'sender_id' => $this->driver->id,
            'is_read' => false,
        ]);

        // Create messages from passenger
        Message::factory()->count(2)->create([
            'chat_id' => $this->chat->id,
            'sender_id' => $this->passenger->id,
            'is_read' => false,
        ]);

        $response = $this->actingAs($this->passenger)->postJson(
            "/api/v1/chats/{$this->chat->id}/mark-read"
        );

        $response->assertStatus(200);

        // Verify only driver's messages are marked as read
        $this->assertEquals(2, Message::where('chat_id', $this->chat->id)
            ->where('sender_id', $this->driver->id)
            ->where('is_read', true)
            ->count());

        $this->assertEquals(2, Message::where('chat_id', $this->chat->id)
            ->where('sender_id', $this->passenger->id)
            ->where('is_read', false)
            ->count());
    }

    public function test_mark_as_read_sets_read_at_timestamp(): void
    {
        Message::factory()->create([
            'chat_id' => $this->chat->id,
            'sender_id' => $this->driver->id,
            'is_read' => false,
            'read_at' => null,
        ]);

        $response = $this->actingAs($this->passenger)->postJson(
            "/api/v1/chats/{$this->chat->id}/mark-read"
        );

        $response->assertStatus(200);

        $message = Message::where('chat_id', $this->chat->id)->first();
        $this->assertTrue($message->is_read);
        $this->assertNotNull($message->read_at);
    }

    public function test_delete_chat_successfully(): void
    {
        $response = $this->actingAs($this->passenger)->deleteJson(
            "/api/v1/chats/{$this->chat->id}"
        );

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);

        $this->assertDatabaseMissing('chats', [
            'id' => $this->chat->id,
        ]);
    }

    public function test_delete_chat_also_deletes_messages(): void
    {
        Message::factory()->count(5)->create([
            'chat_id' => $this->chat->id,
            'sender_id' => $this->driver->id,
        ]);

        $response = $this->actingAs($this->passenger)->deleteJson(
            "/api/v1/chats/{$this->chat->id}"
        );

        $response->assertStatus(200);

        $this->assertDatabaseMissing('chats', [
            'id' => $this->chat->id,
        ]);

        $this->assertEquals(0, Message::where('chat_id', $this->chat->id)->count());
    }

    public function test_delete_nonexistent_chat_fails(): void
    {
        $response = $this->actingAs($this->passenger)->deleteJson(
            "/api/v1/chats/99999"
        );

        $response->assertStatus(404);
    }

    public function test_send_message_requires_authentication(): void
    {
        $response = $this->postJson(
            "/api/v1/chats/{$this->chat->id}/messages",
            [
                'message' => 'Hello!',
                'message_type' => 'text',
            ]
        );

        $response->assertStatus(401);
    }

    public function test_get_messages_requires_authentication(): void
    {
        $response = $this->getJson(
            "/api/v1/chats/{$this->chat->id}/messages"
        );

        $response->assertStatus(401);
    }

    public function test_mark_as_read_requires_authentication(): void
    {
        $response = $this->postJson(
            "/api/v1/chats/{$this->chat->id}/mark-read"
        );

        $response->assertStatus(401);
    }

    public function test_delete_chat_requires_authentication(): void
    {
        $response = $this->deleteJson(
            "/api/v1/chats/{$this->chat->id}"
        );

        $response->assertStatus(401);
    }

    public function test_send_message_with_invalid_file_type_fails(): void
    {
        $file = \Illuminate\Http\UploadedFile::fake()->create('test.txt', 100);

        $response = $this->actingAs($this->passenger)->postJson(
            "/api/v1/chats/{$this->chat->id}/messages",
            [
                'message_type' => 'image',
                'attachment' => $file,
            ]
        );

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
    }

    public function test_send_message_with_oversized_file_fails(): void
    {
        $file = \Illuminate\Http\UploadedFile::fake()->create('test.pdf', 11000);

        $response = $this->actingAs($this->passenger)->postJson(
            "/api/v1/chats/{$this->chat->id}/messages",
            [
                'message_type' => 'image',
                'attachment' => $file,
            ]
        );

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
    }

    public function test_send_message_with_valid_pdf_attachment(): void
    {
        $file = \Illuminate\Http\UploadedFile::fake()->create('test.pdf', 1000);

        $response = $this->actingAs($this->passenger)->postJson(
            "/api/v1/chats/{$this->chat->id}/messages",
            [
                'message_type' => 'image',
                'attachment' => $file,
            ]
        );

        $response->assertStatus(201);
        $response->assertJsonPath('success', true);
    }

    public function test_send_message_with_png_attachment(): void
    {
        $file = \Illuminate\Http\UploadedFile::fake()->image('test.png', 100, 100);

        $response = $this->actingAs($this->passenger)->postJson(
            "/api/v1/chats/{$this->chat->id}/messages",
            [
                'message_type' => 'image',
                'attachment' => $file,
            ]
        );

        $response->assertStatus(201);
        $response->assertJsonPath('success', true);
    }

    public function test_list_chats_includes_ride_and_messages(): void
    {
        Message::factory()->count(3)->create([
            'chat_id' => $this->chat->id,
            'sender_id' => $this->driver->id,
        ]);

        $response = $this->actingAs($this->driver)->getJson('/api/v1/chats');

        $response->assertStatus(200);
        $chats = $response->json('data');
        $this->assertNotNull($chats[0]['ride']);
        $this->assertNotNull($chats[0]['messages']);
        $this->assertCount(3, $chats[0]['messages']);
    }

    public function test_send_message_with_metadata_json(): void
    {
        $metadata = [
            'latitude' => 28.7041,
            'longitude' => 77.1025,
            'address' => 'New Delhi',
        ];

        $response = $this->actingAs($this->passenger)->postJson(
            "/api/v1/chats/{$this->chat->id}/messages",
            [
                'message_type' => 'location',
                'metadata' => json_encode($metadata),
            ]
        );

        $response->assertStatus(201);
        $response->assertJsonPath('success', true);
        $this->assertNotNull($response->json('data.metadata'));
    }

    public function test_mark_specific_messages_as_read_with_message_ids(): void
    {
        // Create unread messages from driver
        $messages = Message::factory()->count(5)->create([
            'chat_id' => $this->chat->id,
            'sender_id' => $this->driver->id,
            'is_read' => false,
        ]);

        // Mark only first 2 messages as read
        $messageIds = $messages->take(2)->pluck('id')->toArray();

        $response = $this->actingAs($this->passenger)->postJson(
            "/api/v1/chats/{$this->chat->id}/mark-read",
            ['message_ids' => $messageIds]
        );

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.marked_count', 2);

        // Verify only specified messages are marked as read
        $this->assertEquals(2, Message::where('chat_id', $this->chat->id)
            ->whereIn('id', $messageIds)
            ->where('is_read', true)
            ->count());

        // Verify other messages remain unread
        $this->assertEquals(3, Message::where('chat_id', $this->chat->id)
            ->whereNotIn('id', $messageIds)
            ->where('is_read', false)
            ->count());
    }

    public function test_mark_as_read_returns_zero_when_no_unread_messages(): void
    {
        // Create already read messages from driver
        Message::factory()->count(3)->create([
            'chat_id' => $this->chat->id,
            'sender_id' => $this->driver->id,
            'is_read' => true,
        ]);

        $response = $this->actingAs($this->passenger)->postJson(
            "/api/v1/chats/{$this->chat->id}/mark-read"
        );

        $response->assertStatus(200);
        $response->assertJsonPath('data.marked_count', 0);
    }
}

