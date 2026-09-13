<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Http\Controllers\Controller;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class CommunicationController extends Controller
{
    public function conversations(Request $request): JsonResponse
    {
        $conversations = DB::table('rider_api_conversations')
            ->where('type', 'customer')
            ->whereIn('delivery_reference', $this->ownedDeliveryReferences((int) $request->user()->id))
            ->orderByDesc('last_message_at')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (object $conversation) => $this->conversationData($conversation));

        return response()->json(['conversations' => $conversations]);
    }

    public function conversation(Request $request, string $conversation): JsonResponse
    {
        $record = $this->ownedConversation($request, $conversation);

        return response()->json([
            'conversation' => $this->conversationData($record),
            'messages' => DB::table('rider_api_messages')
                ->where('conversation_id', $record->id)
                ->orderBy('created_at')
                ->limit(50)
                ->get()
                ->map(fn (object $message) => $this->messageData($message)),
        ]);
    }

    public function messages(Request $request, string $conversation): JsonResponse
    {
        $record = $this->ownedConversation($request, $conversation);
        $validated = $request->validate([
            'limit' => ['nullable', 'integer', 'between:1,100'],
        ]);
        $paginator = DB::table('rider_api_messages')
            ->where('conversation_id', $record->id)
            ->orderByDesc('created_at')
            ->cursorPaginate($validated['limit'] ?? 30);

        return response()->json([
            'messages' => collect($paginator->items())
                ->map(fn (object $message) => $this->messageData($message)),
            'next_cursor' => $paginator->nextCursor()?->encode(),
        ]);
    }

    public function sendMessage(Request $request, string $conversation): JsonResponse
    {
        $validated = $request->validate([
            'client_message_id' => ['required', 'uuid'],
            'body' => ['required', 'string', 'max:5000'],
            'attachment_ids' => ['nullable', 'array', 'max:10'],
            'attachment_ids.*' => ['uuid'],
        ]);
        $record = $this->ownedConversation($request, $conversation);
        $this->assertConversationOpen($record);
        $existing = DB::table('rider_api_messages')
            ->where('client_message_id', $validated['client_message_id'])
            ->first();

        if ($existing) {
            abort_if((int) $existing->conversation_id !== (int) $record->id, 409, 'The client message ID belongs to another conversation.');

            return response()->json([
                'message' => $this->messageData($existing),
                'idempotent_replay' => true,
            ]);
        }

        $messageReference = (string) Str::uuid();
        $messageId = DB::transaction(function () use ($record, $validated, $messageReference) {
            $id = DB::table('rider_api_messages')->insertGetId([
                'reference' => $messageReference,
                'conversation_id' => $record->id,
                'client_message_id' => $validated['client_message_id'],
                'sender_type' => 'customer',
                'body' => $validated['body'],
                'status' => 'sent',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            if (! empty($validated['attachment_ids'])) {
                $updated = DB::table('rider_api_message_attachments')
                    ->where('conversation_id', $record->id)
                    ->whereNull('message_id')
                    ->whereIn('reference', $validated['attachment_ids'])
                    ->update(['message_id' => $id, 'updated_at' => now()]);
                abort_if($updated !== count($validated['attachment_ids']), 422, 'One or more attachments are invalid.');
            }
            DB::table('rider_api_conversations')->where('id', $record->id)->update([
                'last_message_at' => now(),
                'updated_at' => now(),
            ]);

            return $id;
        });

        return response()->json([
            'message' => $this->messageData(
                DB::table('rider_api_messages')->where('id', $messageId)->first(),
            ),
        ], 201);
    }

    public function uploadAttachment(Request $request, string $conversation): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:10240'],
        ]);
        $record = $this->ownedConversation($request, $conversation);
        $this->assertConversationOpen($record);
        $reference = (string) Str::uuid();
        $file = $request->file('file');
        $path = $file->store("user-message-attachments/{$record->reference}", 'local');
        if (! $path) {
            throw new RuntimeException('The private message attachment could not be stored.');
        }

        try {
            DB::table('rider_api_message_attachments')->insert([
                'reference' => $reference,
                'conversation_id' => $record->id,
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'size_bytes' => $file->getSize(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($path);
            throw $exception;
        }

        return response()->json([
            'attachment' => [
                'id' => $reference,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'size_bytes' => $file->getSize(),
            ],
        ], 201);
    }

    public function markConversationRead(Request $request, string $conversation): JsonResponse
    {
        $record = $this->ownedConversation($request, $conversation);
        DB::table('rider_api_messages')
            ->where('conversation_id', $record->id)
            ->where('sender_type', '!=', 'customer')
            ->whereNull('read_at')
            ->update([
                'status' => 'read',
                'read_at' => now(),
                'updated_at' => now(),
            ]);

        return response()->json(['message' => 'Conversation marked as read.']);
    }

    private function ownedConversation(Request $request, string $reference): object
    {
        $conversation = DB::table('rider_api_conversations')
            ->where('reference', $reference)
            ->where('type', 'customer')
            ->whereIn('delivery_reference', $this->ownedDeliveryReferences((int) $request->user()->id))
            ->first();
        abort_if(! $conversation, 404);

        return $conversation;
    }

    private function ownedDeliveryReferences(int $userId): Builder
    {
        return DB::table('rider_api_deliveries as deliveries')
            ->select('deliveries.reference')
            ->where(function (Builder $query) use ($userId) {
                $query->whereExists(function (Builder $orders) use ($userId) {
                    $orders->selectRaw('1')
                        ->from('order as orders')
                        ->whereColumn('orders.id', 'deliveries.legacy_order_id')
                        ->where('orders.user_id', $userId);
                })->orWhereExists(function (Builder $bookings) use ($userId) {
                    $bookings->selectRaw('1')
                        ->from('bookings')
                        ->whereColumn('bookings.id', 'deliveries.legacy_booking_id')
                        ->where('bookings.user_id', $userId);
                });
            });
    }

    private function assertConversationOpen(object $conversation): void
    {
        abort_if($conversation->closed_at, 409, 'This conversation is closed.');

        $authorized = DB::table('rider_api_deliveries')
            ->where('reference', $conversation->delivery_reference)
            ->where('rider_id', $conversation->rider_id)
            ->whereNotIn('current_state', ['delivered', 'cancelled', 'failed'])
            ->exists();
        abort_if(! $authorized, 403, 'The authorized delivery conversation window has ended.');
    }

    /**
     * @return array<string, mixed>
     */
    private function conversationData(object $conversation): array
    {
        return [
            'id' => $conversation->reference,
            'type' => $conversation->type,
            'delivery_id' => $conversation->delivery_reference,
            'subject' => $conversation->subject,
            'last_message_at' => $conversation->last_message_at,
            'closed_at' => $conversation->closed_at,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function messageData(object $message): array
    {
        return [
            'id' => $message->reference,
            'client_message_id' => $message->client_message_id,
            'sender_type' => $message->sender_type,
            'body' => $message->body,
            'status' => $message->status,
            'delivered_at' => $message->delivered_at,
            'read_at' => $message->read_at,
            'created_at' => $message->created_at,
            'attachments' => DB::table('rider_api_message_attachments')
                ->where('message_id', $message->id)
                ->get()
                ->map(fn (object $attachment) => [
                    'id' => $attachment->reference,
                    'original_name' => $attachment->original_name,
                    'mime_type' => $attachment->mime_type,
                    'size_bytes' => $attachment->size_bytes,
                ]),
        ];
    }
}
