<?php

namespace Tests\Feature\Api;

use App\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class UserCommunicationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_use_their_delivery_conversation(): void
    {
        Storage::fake('local');
        $user = $this->createUser('customer@example.com');
        $conversationReference = $this->createCustomerConversation($user);

        $this->apiAs($user)
            ->getJson('/api/v1/user/conversations')
            ->assertOk()
            ->assertJsonPath('conversations.0.id', $conversationReference);

        $attachmentId = $this->apiAs($user)
            ->post("/api/v1/user/conversations/{$conversationReference}/attachments", [
                'file' => UploadedFile::fake()->create('receipt.pdf', 100, 'application/pdf'),
            ])
            ->assertCreated()
            ->json('attachment.id');

        $clientMessageId = (string) Str::uuid();
        $this->apiAs($user)
            ->postJson("/api/v1/user/conversations/{$conversationReference}/messages", [
                'client_message_id' => $clientMessageId,
                'body' => 'I am waiting at the drop-off point.',
                'attachment_ids' => [$attachmentId],
            ])
            ->assertCreated()
            ->assertJsonPath('message.sender_type', 'customer')
            ->assertJsonPath('message.attachments.0.id', $attachmentId);

        $this->apiAs($user)
            ->postJson("/api/v1/user/conversations/{$conversationReference}/read")
            ->assertOk();

        $this->assertDatabaseHas('rider_api_messages', [
            'client_message_id' => $clientMessageId,
            'sender_type' => 'customer',
        ]);
        $this->assertDatabaseHas('rider_api_messages', [
            'sender_type' => 'rider',
            'status' => 'read',
        ]);
    }

    public function test_customer_cannot_access_another_customers_conversation(): void
    {
        $owner = $this->createUser('owner@example.com');
        $otherCustomer = $this->createUser('other@example.com');
        $conversationReference = $this->createCustomerConversation($owner);

        $this->apiAs($otherCustomer)
            ->getJson("/api/v1/user/conversations/{$conversationReference}")
            ->assertNotFound();

        $this->apiAs($otherCustomer)
            ->postJson("/api/v1/user/conversations/{$conversationReference}/messages", [
                'client_message_id' => (string) Str::uuid(),
                'body' => 'Unauthorized message.',
            ])
            ->assertNotFound();
    }

    private function createUser(string $email): User
    {
        return User::query()->forceCreate([
            'name' => 'Test Customer',
            'email' => $email,
            'password' => bcrypt('password'),
        ]);
    }

    private function createCustomerConversation(User $user): string
    {
        if (! Schema::hasTable('order')) {
            Schema::create('order', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->index();
            });
        }
        if (! Schema::hasTable('bookings')) {
            Schema::create('bookings', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->index();
            });
        }

        $orderId = DB::table('order')->insertGetId(['user_id' => $user->id]);
        $riderId = DB::table('rider')->insertGetId([
            'name' => 'Test Rider',
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $deliveryReference = (string) Str::uuid();
        DB::table('rider_api_deliveries')->insert([
            'reference' => $deliveryReference,
            'rider_id' => $riderId,
            'legacy_order_id' => $orderId,
            'current_state' => 'accepted',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $conversationReference = (string) Str::uuid();
        $conversationId = DB::table('rider_api_conversations')->insertGetId([
            'reference' => $conversationReference,
            'rider_id' => $riderId,
            'type' => 'customer',
            'delivery_reference' => $deliveryReference,
            'subject' => 'Delivery customer',
            'last_message_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('rider_api_messages')->insert([
            'reference' => (string) Str::uuid(),
            'conversation_id' => $conversationId,
            'client_message_id' => (string) Str::uuid(),
            'sender_type' => 'rider',
            'body' => 'I am on my way.',
            'status' => 'sent',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $conversationReference;
    }

    private function apiAs(User $user): static
    {
        return $this->actingAs($user, 'api')
            ->withHeader('X-Admin-Request', 'apiRequestHandle001');
    }
}
