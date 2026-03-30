<?php

namespace Tests\Unit;

use App\Models\Chat;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MessageTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that message can be created with all attributes
     */
    public function test_message_can_be_created(): void
    {
        $chat = Chat::factory()->create();
        $sender = User::factory()->create();

        $message = Message::create([
            'chat_id' => $chat->id,
            'sender_id' => $sender->id,
            'message' => 'Hello, how are you?',
            'message_type' => 'text',
            'is_read' => false,
        ]);

        $this->assertNotNull($message->id);
        $this->assertEquals($chat->id, $message->chat_id);
        $this->assertEquals($sender->id, $message->sender_id);
        $this->assertEquals('Hello, how are you?', $message->message);
        $this->assertEquals('text', $message->message_type);
    }

    /**
     * Test that message belongs to a chat
     */
    public function test_message_belongs_to_chat(): void
    {
        $chat = Chat::factory()->create();
        $message = Message::factory()->create(['chat_id' => $chat->id]);

        $this->assertTrue($message->chat()->is($chat));
    }

    /**
     * Test that message belongs to a sender
     */
    public function test_message_belongs_to_sender(): void
    {
        $sender = User::factory()->create();
        $message = Message::factory()->create(['sender_id' => $sender->id]);

        $this->assertTrue($message->sender()->is($sender));
    }

    /**
     * Test that message type can be text
     */
    public function test_message_type_can_be_text(): void
    {
        $chat = Chat::factory()->create();
        $sender = User::factory()->create();

        $message = Message::create([
            'chat_id' => $chat->id,
            'sender_id' => $sender->id,
            'message' => 'Hello',
            'message_type' => 'text',
        ]);

        $this->assertEquals('text', $message->message_type);
    }

    /**
     * Test that message type can be image
     */
    public function test_message_type_can_be_image(): void
    {
        $chat = Chat::factory()->create();
        $sender = User::factory()->create();

        $message = Message::create([
            'chat_id' => $chat->id,
            'sender_id' => $sender->id,
            'message_type' => 'image',
            'attachment' => 'path/to/image.jpg',
        ]);

        $this->assertEquals('image', $message->message_type);
    }

    /**
     * Test that message type can be location
     */
    public function test_message_type_can_be_location(): void
    {
        $chat = Chat::factory()->create();
        $sender = User::factory()->create();

        $message = Message::create([
            'chat_id' => $chat->id,
            'sender_id' => $sender->id,
            'message_type' => 'location',
            'metadata' => json_encode(['lat' => 28.7041, 'lng' => 77.1025]),
        ]);

        $this->assertEquals('location', $message->message_type);
    }

    /**
     * Test that attachment can be null
     */
    public function test_attachment_can_be_null(): void
    {
        $chat = Chat::factory()->create();
        $sender = User::factory()->create();

        $message = Message::create([
            'chat_id' => $chat->id,
            'sender_id' => $sender->id,
            'message' => 'Hello',
            'attachment' => null,
        ]);

        $this->assertNull($message->attachment);
    }

    /**
     * Test that metadata is cast to JSON
     */
    public function test_metadata_is_cast_to_json(): void
    {
        $chat = Chat::factory()->create();
        $sender = User::factory()->create();
        $metadata = ['lat' => 28.7041, 'lng' => 77.1025, 'accuracy' => 10];

        $message = Message::create([
            'chat_id' => $chat->id,
            'sender_id' => $sender->id,
            'message_type' => 'location',
            'metadata' => $metadata,
        ]);

        $this->assertIsArray($message->metadata);
        $this->assertEquals($metadata, $message->metadata);
    }

    /**
     * Test that is_read field is cast to boolean
     */
    public function test_is_read_field_is_cast_to_boolean(): void
    {
        $chat = Chat::factory()->create();
        $sender = User::factory()->create();

        $message = Message::create([
            'chat_id' => $chat->id,
            'sender_id' => $sender->id,
            'message' => 'Hello',
            'is_read' => 1,
        ]);

        $this->assertIsBool($message->is_read);
        $this->assertTrue($message->is_read);
    }

    /**
     * Test that read_at timestamp can be set
     */
    public function test_read_at_timestamp_can_be_set(): void
    {
        $chat = Chat::factory()->create();
        $sender = User::factory()->create();
        $readAt = now();

        $message = Message::create([
            'chat_id' => $chat->id,
            'sender_id' => $sender->id,
            'message' => 'Hello',
            'is_read' => true,
            'read_at' => $readAt,
        ]);

        $this->assertNotNull($message->read_at);
        $this->assertIsObject($message->read_at);
    }

    /**
     * Test that message can be updated
     */
    public function test_message_can_be_updated(): void
    {
        $chat = Chat::factory()->create();
        $sender = User::factory()->create();
        $message = Message::factory()->create([
            'chat_id' => $chat->id,
            'sender_id' => $sender->id,
            'is_read' => false,
        ]);

        $message->update(['is_read' => true, 'read_at' => now()]);

        $this->assertTrue($message->is_read);
        $this->assertNotNull($message->read_at);
    }

    /**
     * Test that message is deleted when chat is deleted
     */
    public function test_message_deleted_when_chat_deleted(): void
    {
        $chat = Chat::factory()->create();
        $message = Message::factory()->create(['chat_id' => $chat->id]);

        $messageId = $message->id;
        $chat->delete();

        $this->assertNull(Message::find($messageId));
    }

    /**
     * Test that message is deleted when sender is deleted
     */
    public function test_message_deleted_when_sender_deleted(): void
    {
        $sender = User::factory()->create();
        $message = Message::factory()->create(['sender_id' => $sender->id]);

        $messageId = $message->id;
        $sender->delete();

        $this->assertNull(Message::find($messageId));
    }

    /**
     * Test that multiple messages can be created in a chat
     */
    public function test_multiple_messages_can_be_created_in_chat(): void
    {
        $chat = Chat::factory()->create();

        Message::factory()->create(['chat_id' => $chat->id]);
        Message::factory()->create(['chat_id' => $chat->id]);
        Message::factory()->create(['chat_id' => $chat->id]);

        $this->assertEquals(3, $chat->messages()->count());
    }
}
