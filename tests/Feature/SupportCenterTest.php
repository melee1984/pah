<?php

namespace Tests\Feature;

use App\SupportTicket;
use App\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class SupportCenterTest extends TestCase
{
    private User $customer;

    private User $other;

    private User $staff;

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'app.key' => 'base64:'.base64_encode(str_repeat('s', 32))]);
        DB::purge('sqlite');
        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('firstname');
            $t->string('lastname');
            $t->string('email');
            $t->string('mobile')->nullable();
            $t->timestamps();
        });
        Schema::create('order', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id');
        });
        foreach (glob(database_path('migrations/*create_permission_tables.php')) as $file) {
            (require $file)->up();
        }
        (require database_path('migrations/2026_10_02_000000_create_support_tables.php'))->up();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->customer = User::create(['firstname' => 'Ana', 'lastname' => 'Cruz', 'email' => 'ana@example.test']);
        $this->other = User::create(['firstname' => 'Ben', 'lastname' => 'Cruz', 'email' => 'ben@example.test']);
        $this->staff = User::create(['firstname' => 'Staff', 'lastname' => 'One', 'email' => 'staff@example.test']);
        Role::create(['name' => 'admin', 'guard_name' => 'web']);
        $this->staff->assignRole('admin');
        DB::table('order')->insert(['id' => 20, 'user_id' => $this->customer->id]);
        DB::table('order')->insert(['id' => 21, 'user_id' => $this->other->id]);
    }

    private function createTicket(array $extra = []): int
    {
        return $this->actingAs($this->customer)->postJson('/support/tickets', array_merge([
            'category' => 'Orders & Delivery', 'subject' => 'Missing item', 'message' => 'My drink is missing.', 'order_id' => 20,
        ], $extra))->assertCreated()->json('id');
    }

    public function test_creation_snapshots_customer_and_notifies_staff(): void
    {
        $id = $this->createTicket(['user_id' => $this->other->id, 'status' => 'Closed']);
        $ticket = SupportTicket::findOrFail($id);
        $this->assertStringStartsWith('PF-', $ticket->number);
        $this->assertSame($this->customer->id, $ticket->user_id);
        $this->assertSame('Open', $ticket->status);
        $this->assertDatabaseHas('support_alerts', ['ticket_id' => $id, 'user_id' => $this->staff->id, 'audience' => 'staff']);
        $this->getJson('/support/tickets')->assertOk()->assertJsonPath('data.0.id', $id);
        $this->getJson('/support/orders')->assertOk()->assertJsonCount(1, 'data');
        $conversation = $this->getJson('/support/tickets/'.$id)->assertOk();
        $this->assertSame($conversation->json('ticket.created_at'), $conversation->json('messages.0.created_at'));
    }

    public function test_ownership_and_staff_authorization_are_enforced(): void
    {
        $id = $this->createTicket();
        $this->actingAs($this->other)->getJson('/support/tickets/'.$id)->assertNotFound();
        $this->postJson('/support/tickets/'.$id.'/replies', ['message' => 'Hijack'])->assertNotFound();
        $this->getJson('/support/tickets')->assertJsonCount(0, 'data');
        foreach (['', '/options', '/alerts', '/tickets', '/tickets/'.$id] as $path) {
            $this->getJson('/data/dashboard/support'.$path)->assertForbidden();
        }
        $this->patchJson('/data/dashboard/support/tickets/'.$id, ['status' => 'Closed'])->assertForbidden();
        $this->postJson('/data/dashboard/support/tickets/'.$id.'/replies', ['message' => 'Hijack'])->assertForbidden();
        $this->actingAs($this->customer)->postJson('/support/tickets', ['category' => 'Orders & Delivery', 'subject' => 'Wrong order', 'message' => 'Help', 'order_id' => 21])->assertUnprocessable();
    }

    public function test_internal_notes_and_attachments_never_leak(): void
    {
        $id = $this->createTicket(['attachments' => [UploadedFile::fake()->image('customer.png')]]);
        $customerFile = DB::table('support_attachments')->value('id');
        $this->actingAs($this->staff)->post('/data/dashboard/support/tickets/'.$id.'/replies', ['message' => 'Private investigation', 'internal' => true, 'attachments' => [UploadedFile::fake()->image('internal.png')]], ['Accept' => 'application/json'])->assertOk();
        $privateFile = DB::table('support_attachments')->max('id');
        $this->getJson('/data/dashboard/support/tickets/'.$id)->assertJsonCount(2, 'messages');
        $this->actingAs($this->customer)->getJson('/support/tickets/'.$id)->assertJsonCount(1, 'messages')->assertDontSee('Private investigation');
        $this->get('/support/tickets/'.$id.'/attachments/'.$privateFile)->assertNotFound();
        $this->get('/support/tickets/'.$id.'/attachments/'.$customerFile)->assertOk()->assertHeader('Content-Type', 'image/png');
        $this->postJson('/support/tickets/'.$id.'/replies', ['message' => 'secret', 'internal' => true])->assertForbidden();
        $this->actingAs($this->other)->get('/support/tickets/'.$id.'/attachments/'.$customerFile)->assertNotFound();
        $this->assertDatabaseMissing('support_alerts', ['audience' => 'customer']);
    }

    public function test_reply_status_assignment_notifications_and_filters(): void
    {
        $id = $this->createTicket();
        $this->actingAs($this->staff)->patchJson('/data/dashboard/support/tickets/'.$id, ['status' => 'Waiting for Customer', 'priority' => 'High', 'assigned_to' => $this->staff->id])->assertOk();
        $this->postJson('/data/dashboard/support/tickets/'.$id.'/replies', ['message' => 'Please confirm the missing item.'])->assertOk();
        $this->getJson('/data/dashboard/support/tickets?status=Waiting%20for%20Customer&priority=High&customer=Ana&q=Missing&from='.now()->toDateString().'&to='.now()->toDateString())->assertJsonCount(1, 'data');
        $this->getJson('/data/dashboard/support/tickets?priority=Low')->assertJsonCount(0, 'data');
        $this->getJson('/data/dashboard/support/tickets?to='.now()->toDateString())->assertOk()->assertJsonCount(1, 'data');
        $this->actingAs($this->customer)->getJson('/support/alerts')->assertJsonPath('count', 2);
        $this->getJson('/support/tickets')->assertJsonPath('data.0.unread', 2);
        $this->getJson('/support/tickets/'.$id)->assertOk()->assertJsonCount(3, 'messages');
        $this->getJson('/support/alerts')->assertJsonPath('count', 0);
        $this->postJson('/support/tickets/'.$id.'/replies', ['message' => 'The orange juice.'])->assertOk();
        $this->assertDatabaseHas('support_tickets', ['id' => $id, 'status' => 'Open']);
        $this->actingAs($this->staff)->patchJson('/data/dashboard/support/tickets/'.$id, ['status' => 'Closed', 'priority' => 'Normal', 'assigned_to' => null])->assertOk();
        $this->actingAs($this->customer)->postJson('/support/tickets/'.$id.'/replies', ['message' => 'Another reply'])->assertUnprocessable();
    }

    public function test_validation_rejects_invalid_uploads_and_non_staff_assignment(): void
    {
        $id = $this->createTicket();
        $this->postJson('/support/tickets/'.$id.'/replies', ['message' => 'bad', 'attachments' => [UploadedFile::fake()->create('bad.svg', 1, 'image/svg+xml')]])->assertUnprocessable();
        $this->postJson('/support/tickets/'.$id.'/replies', ['message' => 'big', 'attachments' => [UploadedFile::fake()->image('big.png')->size(2049)]])->assertUnprocessable();
        $this->actingAs($this->staff)->patchJson('/data/dashboard/support/tickets/'.$id, ['status' => 'Open', 'priority' => 'Normal', 'assigned_to' => $this->other->id])->assertUnprocessable();
    }

    public function test_guest_cannot_access_support_data(): void
    {
        $this->getJson('/support/tickets')->assertUnauthorized();
        $this->getJson('/data/dashboard/support/alerts')->assertUnauthorized();
    }
}
