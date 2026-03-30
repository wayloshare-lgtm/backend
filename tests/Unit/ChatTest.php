<?php

namespace Tests\Unit;

use App\Models\Chat;
use App\Models\Message;
use App\Models\Ride;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that chat can be created with ride_id
     */
    public function test_chat_can_be_created(): void
    {
        $ride = Ride::factory()->create();

        $chat = Chat::create([
            'ride_id' => $ride->id,
        ]);

        $this->assertNotNull($chat->id);
        $this->assertEquals($ride->id, $chat->ride_id);
    }

    /**
     * Test that chat belongs to a ride
     */
    public function test_chat_belongs_to_ride(): void
    {
        $ride = Ride::factory()->create();
        $chat = Chat::factory()->create(['ride_id' => $ride->id]);

        $this->assertTrue($chat->ride()->is($ride));
    }

    /**
     * Test that chat has many messages
     */
    public function test_chat_has_many_messages(): void
    {
        $chat = Chat::factory()->create();

        Message::factory()->create(['chat_id' => $chat->id]);
        Message::factory()->create(['chat_id' => $chat->id]);
        Message::factory()->create(['chat_id' => $chat->id]);

        $this->assertEquals(3, $chat->messages()->count());
    }

    /**
     * Test that chat can have zero messages
     */
    public function test_chat_can_have_zero_messages(): void
    {
        $chat = Chat::factory()->create();

        $this->assertEquals(0, $chat->messages()->count());
    }

    /**
     * Test that chat timestamps are cast to datetime
     */
    public function test_chat_timestamps_are_cast_to_datetime(): void
    {
        $ride = Ride::factory()->create();
        $chat = Chat::create(['ride_id' => $ride->id]);

        $this->assertIsObject($chat->created_at);
        $this->assertIsObject($chat->updated_at);
    }

    /**
     * Test that chat can be updated
     */
    public function test_chat_can_be_updated(): void
    {
        $ride1 = Ride::factory()->create();
        $ride2 = Ride::factory()->create();
        $chat = Chat::factory()->create(['ride_id' => $ride1->id]);

        $chat->update(['ride_id' => $ride2->id]);

        $this->assertEquals($ride2->id, $chat->ride_id);
    }

    /**
     * Test that chat is deleted when ride is deleted
     */
    public function test_chat_deleted_when_ride_deleted(): void
    {
        $ride = Ride::factory()->create();
        $chat = Chat::factory()->create(['ride_id' => $ride->id]);

        $chatId = $chat->id;
        $ride->delete();

        $this->assertNull(Chat::find($chatId));
    }

    /**
     * Test that messages are deleted when chat is deleted
     */
    public function test_messages_deleted_when_chat_deleted(): void
    {
        $chat = Chat::factory()->create();
        $message = Message::factory()->create(['chat_id' => $chat->id]);

        $messageId = $message->id;
        $chat->delete();

        $this->assertNull(Message::find($messageId));
    }

    /**
     * Test that multiple chats can be created for different rides
     */
    public function test_multiple_chats_can_be_created_for_different_rides(): void
    {
        $ride1 = Ride::factory()->create();
        $ride2 = Ride::factory()->create();

        Chat::factory()->create(['ride_id' => $ride1->id]);
        Chat::factory()->create(['ride_id' => $ride2->id]);

        $this->assertEquals(1, $ride1->chats()->count());
        $this->assertEquals(1, $ride2->chats()->count());
    }
}
