<?php

namespace App\Http\Controllers\Api\V1\Rider;

use App\Http\Controllers\Controller;
use App\Services\RiderApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use RuntimeException;
use Throwable;

class CommunicationController extends Controller
{
    public function __construct(private readonly RiderApiService $riders) {}

    public function conversations(Request $request): JsonResponse
    {
        $conversations = DB::table('rider_api_conversations')
            ->where('rider_id', $this->riders->rider($request)->id)
            ->orderByDesc('last_message_at')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (object $conversation) => $this->conversationData($conversation));

        return response()->json(['conversations' => $conversations]);
    }

    public function startConversation(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['required', Rule::in(['support'])],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
            'client_message_id' => ['required', 'uuid'],
        ]);
        $rider = $this->riders->rider($request);
        $reference = (string) Str::uuid();
        $messageReference = (string) Str::uuid();

        DB::transaction(function () use ($rider, $validated, $reference, $messageReference) {
            $conversationId = DB::table('rider_api_conversations')->insertGetId([
                'reference' => $reference,
                'rider_id' => $rider->id,
                'type' => 'support',
                'subject' => $validated['subject'],
                'last_message_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('rider_api_messages')->insert([
                'reference' => $messageReference,
                'conversation_id' => $conversationId,
                'client_message_id' => $validated['client_message_id'],
                'sender_type' => 'rider',
                'body' => $validated['message'],
                'status' => 'sent',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        return response()->json([
            'message' => 'Support conversation created.',
            'conversation' => $this->conversationData(
                DB::table('rider_api_conversations')->where('reference', $reference)->first(),
            ),
        ], 201);
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
                'sender_type' => 'rider',
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
        $path = $file->store("rider-message-attachments/{$record->reference}", 'local');
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
            ->where('sender_type', '!=', 'rider')
            ->whereNull('read_at')
            ->update([
                'status' => 'read',
                'read_at' => now(),
                'updated_at' => now(),
            ]);

        return response()->json(['message' => 'Conversation marked as read.']);
    }

    public function notifications(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['nullable', 'string', 'max:50'],
            'unread_only' => ['nullable', 'boolean'],
            'limit' => ['nullable', 'integer', 'between:1,100'],
        ]);
        $query = DB::table('rider_api_notifications')
            ->where('rider_id', $this->riders->rider($request)->id)
            ->orderByDesc('created_at');
        if (isset($validated['type'])) {
            $query->where('type', $validated['type']);
        }
        if ($validated['unread_only'] ?? false) {
            $query->whereNull('read_at');
        }
        $paginator = $query->cursorPaginate($validated['limit'] ?? 30);

        return response()->json([
            'notifications' => collect($paginator->items())
                ->map(fn (object $notification) => $this->notificationData($notification)),
            'next_cursor' => $paginator->nextCursor()?->encode(),
        ]);
    }

    public function markNotificationRead(Request $request, string $notification): JsonResponse
    {
        $rider = $this->riders->rider($request);
        $updated = DB::table('rider_api_notifications')
            ->where('rider_id', $rider->id)
            ->where('reference', $notification)
            ->update(['read_at' => now(), 'updated_at' => now()]);
        abort_if($updated === 0, 404);

        return response()->json(['message' => 'Notification marked as read.']);
    }

    public function markAllNotificationsRead(Request $request): JsonResponse
    {
        DB::table('rider_api_notifications')
            ->where('rider_id', $this->riders->rider($request)->id)
            ->whereNull('read_at')
            ->update(['read_at' => now(), 'updated_at' => now()]);

        return response()->json(['message' => 'All notifications marked as read.']);
    }

    public function notificationPreferences(Request $request): JsonResponse
    {
        $preferences = $this->preferences($this->riders->rider($request)->id);

        return response()->json(['preferences' => $this->preferenceData($preferences)]);
    }

    public function updateNotificationPreferences(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'delivery_offers' => ['sometimes', 'boolean'],
            'delivery_updates' => ['sometimes', 'boolean'],
            'wallet_updates' => ['sometimes', 'boolean'],
            'support_messages' => ['sometimes', 'boolean'],
            'marketing' => ['sometimes', 'boolean'],
        ]);
        $riderId = $this->riders->rider($request)->id;
        $this->preferences($riderId);

        DB::table('rider_api_notification_preferences')
            ->where('rider_id', $riderId)
            ->update([...$validated, 'updated_at' => now()]);

        return $this->notificationPreferences($request);
    }

    private function ownedConversation(Request $request, string $reference): object
    {
        $conversation = DB::table('rider_api_conversations')
            ->where('rider_id', $this->riders->rider($request)->id)
            ->where('reference', $reference)
            ->first();
        abort_if(! $conversation, 404);

        return $conversation;
    }

    private function assertConversationOpen(object $conversation): void
    {
        abort_if($conversation->closed_at, 409, 'This conversation is closed.');

        if ($conversation->type !== 'support' && $conversation->delivery_reference) {
            $authorized = DB::table('rider_api_deliveries')
                ->where('reference', $conversation->delivery_reference)
                ->where('rider_id', $conversation->rider_id)
                ->whereNotIn('current_state', ['delivered', 'cancelled', 'failed'])
                ->exists();
            abort_if(! $authorized, 403, 'The authorized delivery conversation window has ended.');
        }
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

    /**
     * @return array<string, mixed>
     */
    private function notificationData(object $notification): array
    {
        return [
            'id' => $notification->reference,
            'type' => $notification->type,
            'title' => $notification->title,
            'body' => $notification->body,
            'deep_link' => $this->riders->validDeepLink($notification->deep_link),
            'data' => $notification->data
                ? json_decode($notification->data, true, 512, JSON_THROW_ON_ERROR)
                : null,
            'read_at' => $notification->read_at,
            'created_at' => $notification->created_at,
        ];
    }

    private function preferences(int $riderId): object
    {
        DB::table('rider_api_notification_preferences')->insertOrIgnore([
            'rider_id' => $riderId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return DB::table('rider_api_notification_preferences')
            ->where('rider_id', $riderId)
            ->first();
    }

    /**
     * @return array<string, bool>
     */
    private function preferenceData(object $preferences): array
    {
        return [
            'delivery_offers' => (bool) $preferences->delivery_offers,
            'delivery_updates' => (bool) $preferences->delivery_updates,
            'wallet_updates' => (bool) $preferences->wallet_updates,
            'support_messages' => (bool) $preferences->support_messages,
            'marketing' => (bool) $preferences->marketing,
        ];
    }
}
