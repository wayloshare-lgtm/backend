<?php

namespace Tests\Integration;

use App\Models\User;
use App\Models\Ride;
use App\Models\Chat;
use App\Models\Message;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatWorkflowTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test complete chat workflow:
     * Create chat → Send messages → Mark as read → Retrieve history
     */
    public function test_complete_chat_workflow()
    {
        // Step 1: Create driver and passenger
        $driver = User::factory()->create(['user_preference' => 'driver']);
        $passenger = User::factory()->create(['user_preference' => 'passenger']);

        // Step 2: Create a ride
        $ride = Ride::factory()->create([
            'driver_id' => $driver->id,
            'rider_id' => $passenger->id,
        ]);

        // Step 3: Create chat for the ride
        $response = $this->actingAs($passenger, 'sanctum')
            ->postJson('/api/v1/chats', [
                'ride_id' => $ride->id,
            ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true]);

        $chat = Chat::where('ride_id', $ride->id)->first();
        $this->assertNotNull($chat);

        // Step 4: Passenger sends first message
        $response = $this->actingAs($passenger, 'sanctum')
            ->postJson("/api/v1/chats/{$chat->id}/messages", [
                'message' => 'Hi, I am ready for the ride',
                'message_type' => 'text',
            ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true]);

        $message1 = Message::where('chat_id', $chat->id)->first();
        $this->assertEquals('Hi, I am ready for the ride', $message1->message);
        $this->assertFalse($message1->is_read);

        // Step 5: Driver sends response
        $response = $this->actingAs($driver, 'sanctum')
            ->postJson("/api/v1/chats/{$chat->id}/messages", [
                'message' => 'I am on my way, ETA 5 minutes',
                'message_type' => 'text',
            ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true]);

        // Step 6: Passenger sends another message
        $response = $this->actingAs($passenger, 'sanctum')
            ->postJson("/api/v1/chats/{$chat->id}/messages", [
                'message' => 'Great! I will be waiting at the entrance',
                'message_type' => 'text',
            ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true]);

        // Step 7: Retrieve all messages
        $response = $this->actingAs($passenger, 'sanctum')
            ->getJson("/api/v1/chats/{$chat->id}/messages");

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');

        // Step 8: Mark messages as read
        $response = $this->actingAs($driver, 'sanctum')
            ->postJson("/api/v1/chats/{$chat->id}/mark-read", []);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        // Step 9: Verify messages are marked as read
        $messages = Message::where('chat_id', $chat->id)->get();
        foreach ($messages as $msg) {
            if ($msg->sender_id !== $driver->id) {
                $msg->refresh();
                $this->assertTrue($msg->is_read);
            }
        }
    }

    /**
     * Test sending different message types
     */
    public function test_sending_different_message_types()
    {
        $driver = User::factory()->create(['user_preference' => 'driver']);
        $passenger = User::factory()->create(['user_preference' => 'passenger']);

        $ride = Ride::factory()->create([
            'driver_id' => $driver->id,
            'rider_id' => $passenger->id,
        ]);

        $chat = Chat::factory()->create(['ride_id' => $ride->id]);

        // Send text message
        $response = $this->actingAs($passenger, 'sanctum')
            ->postJson("/api/v1/chats/{$chat->id}/messages", [
                'message' => 'Hello!',
                'message_type' => 'text',
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('messages', [
            'chat_id' => $chat->id,
            'message_type' => 'text',
        ]);

        // Send image message
        $response = $this->actingAs($passenger, 'sanctum')
            ->postJson("/api/v1/chats/{$chat->id}/messages", [
                'message' => 'Check this out',
                'message_type' => 'image',
                'attachment' => 'images/photo.jpg',
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('messages', [
            'chat_id' => $chat->id,
            'message_type' => 'image',
        ]);

        // Send location message
        $response = $this->actingAs($passenger, 'sanctum')
            ->postJson("/api/v1/chats/{$chat->id}/messages", [
                'message' => 'I am here',
                'message_type' => 'location',
                'metadata' => [
                    'latitude' => 12.9716,
                    'longitude' => 77.5946,
                ],
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('messages', [
            'chat_id' => $chat->id,
            'message_type' => 'location',
        ]);
    }

    /**
     * Test message read status tracking
     */
    public function test_message_read_status_tracking()
    {
        $driver = User::factory()->create(['user_preference' => 'driver']);
        $passenger = User::factory()->create(['user_preference' => 'passenger']);

        $ride = Ride::factory()->create([
            'driver_id' => $driver->id,
            'rider_id' => $passenger->id,
        ]);

        $chat = Chat::factory()->create(['ride_id' => $ride->id]);

        // Passenger sends message
        $this->actingAs($passenger, 'sanctum')
            ->postJson("/api/v1/chats/{$chat->id}/messages", [
                'message' => 'Hello driver!',
                'message_type' => 'text',
            ]);

        $message = Message::where('chat_id', $chat->id)->first();
        $this->assertFalse($message->is_read);
        $this->assertNull($message->read_at);

        // Driver marks chat as read
        $this->actingAs($driver, 'sanctum')
            ->postJson("/api/v1/chats/{$chat->id}/mark-read", []);

        $message->refresh();
        $this->assertTrue($message->is_read);
        $this->assertNotNull($message->read_at);
    }

    /**
     * Test chat can be deleted
     */
    public function test_chat_can_be_deleted()
    {
        $driver = User::factory()->create(['user_preference' => 'driver']);
        $passenger = User::factory()->create(['user_preference' => 'passenger']);

        $ride = Ride::factory()->create([
            'driver_id' => $driver->id,
            'rider_id' => $passenger->id,
        ]);

        $chat = Chat::factory()->create(['ride_id' => $ride->id]);

        // Add some messages
        $this->actingAs($passenger, 'sanctum')
            ->postJson("/api/v1/chats/{$chat->id}/messages", [
                'message' => 'Hello!',
                'message_type' => 'text',
            ]);

        // Delete chat
        $response = $this->actingAs($passenger, 'sanctum')
            ->deleteJson("/api/v1/chats/{$chat->id}");

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        // Verify chat is deleted
        $this->assertDatabaseMissing('chats', [
            'id' => $chat->id,
        ]);

        // Verify messages are also deleted (cascade)
        $this->assertDatabaseMissing('messages', [
            'chat_id' => $chat->id,
        ]);
    }

    /**
     * Test message pagination
     */
    public function test_message_pagination()
    {
        $driver = User::factory()->create(['user_preference' => 'driver']);
        $passenger = User::factory()->create(['user_preference' => 'passenger']);

        $ride = Ride::factory()->create([
            'driver_id' => $driver->id,
            'rider_id' => $passenger->id,
        ]);

        $chat = Chat::factory()->create(['ride_id' => $ride->id]);

        // Send 15 messages
        for ($i = 1; $i <= 15; $i++) {
            $this->actingAs($passenger, 'sanctum')
                ->postJson("/api/v1/chats/{$chat->id}/messages", [
                    'message' => "Message $i",
                    'message_type' => 'text',
                ]);
        }

        // Retrieve first page
        $response = $this->actingAs($passenger, 'sanctum')
            ->getJson("/api/v1/chats/{$chat->id}/messages?page=1&per_page=10");

        $response->assertStatus(200);
        $this->assertLessThanOrEqual(10, count($response->json('data')));
    }

    /**
     * Test message with attachment
     */
    public function test_message_with_attachment()
    {
        $driver = User::factory()->create(['user_preference' => 'driver']);
        $passenger = User::factory()->create(['user_preference' => 'passenger']);

        $ride = Ride::factory()->create([
            'driver_id' => $driver->id,
            'rider_id' => $passenger->id,
        ]);

        $chat = Chat::factory()->create(['ride_id' => $ride->id]);

        // Send message with attachment
        $response = $this->actingAs($passenger, 'sanctum')
            ->postJson("/api/v1/chats/{$chat->id}/messages", [
                'message' => 'Here is my document',
                'message_type' => 'text',
                'attachment' => 'documents/receipt.pdf',
            ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('messages', [
            'chat_id' => $chat->id,
            'attachment' => 'documents/receipt.pdf',
        ]);
    }

    /**
     * Test message metadata storage
     */
    public function test_message_metadata_storage()
    {
        $driver = User::factory()->create(['user_preference' => 'driver']);
        $passenger = User::factory()->create(['user_preference' => 'passenger']);

        $ride = Ride::factory()->create([
            'driver_id' => $driver->id,
            'rider_id' => $passenger->id,
        ]);

        $chat = Chat::factory()->create(['ride_id' => $ride->id]);

        // Send message with metadata
        $response = $this->actingAs($passenger, 'sanctum')
            ->postJson("/api/v1/chats/{$chat->id}/messages", [
                'message' => 'Location update',
                'message_type' => 'location',
                'metadata' => [
                    'latitude' => 12.9716,
                    'longitude' => 77.5946,
                    'accuracy' => 5.0,
                    'address' => 'Bangalore Central',
                ],
            ]);

        $response->assertStatus(201);

        $message = Message::where('chat_id', $chat->id)->first();
        $this->assertEquals(12.9716, $message->metadata['latitude']);
        $this->assertEquals(77.5946, $message->metadata['longitude']);
        $this->assertEquals('Bangalore Central', $message->metadata['address']);
    }

    /**
     * Test multiple chats for different rides
     */
    public function test_multiple_chats_for_different_rides()
    {
        $driver = User::factory()->create(['user_preference' => 'driver']);
        $passenger = User::factory()->create(['user_preference' => 'passenger']);

        // Create two rides
        $ride1 = Ride::factory()->create([
            'driver_id' => $driver->id,
            'rider_id' => $passenger->id,
        ]);

        $ride2 = Ride::factory()->create([
            'driver_id' => $driver->id,
            'rider_id' => $passenger->id,
        ]);

        // Create chats for both rides
        $chat1 = Chat::factory()->create(['ride_id' => $ride1->id]);
        $chat2 = Chat::factory()->create(['ride_id' => $ride2->id]);

        // Send messages to both chats
        $this->actingAs($passenger, 'sanctum')
            ->postJson("/api/v1/chats/{$chat1->id}/messages", [
                'message' => 'Message for ride 1',
                'message_type' => 'text',
            ]);

        $this->actingAs($passenger, 'sanctum')
            ->postJson("/api/v1/chats/{$chat2->id}/messages", [
                'message' => 'Message for ride 2',
                'message_type' => 'text',
            ]);

        // Verify messages are in correct chats
        $response1 = $this->actingAs($passenger, 'sanctum')
            ->getJson("/api/v1/chats/{$chat1->id}/messages");

        $response2 = $this->actingAs($passenger, 'sanctum')
            ->getJson("/api/v1/chats/{$chat2->id}/messages");

        $this->assertCount(1, $response1->json('data'));
        $this->assertCount(1, $response2->json('data'));
        $this->assertEquals('Message for ride 1', $response1->json('data.0.message'));
        $this->assertEquals('Message for ride 2', $response2->json('data.0.message'));
    }
}
