<?php

namespace App\Http\Controllers\Support;

use App\Http\Controllers\Controller;
use App\SupportTicket;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class TicketController extends Controller
{
    private function staff(Request $request): bool
    {
        $staff = $request->routeIs('support.admin.*');
        abort_if($staff && ! $request->user()->isAdmin(), 403);

        return $staff;
    }

    private function authorizeTicket(Request $request, SupportTicket $ticket): bool
    {
        $staff = $this->staff($request);
        abort_unless($staff || (int) $ticket->user_id === (int) $request->user()->id, 404);

        return $staff;
    }

    public function page(Request $request)
    {
        $this->staff($request);

        return view('dashboard.pages.support.index');
    }

    public function options(Request $request)
    {
        $staff = $this->staff($request);

        return response()->json([
            'categories' => SupportTicket::CATEGORIES,
            'statuses' => SupportTicket::STATUSES,
            'priorities' => SupportTicket::PRIORITIES,
            'customer' => ['name' => $request->user()->full_name, 'email' => $request->user()->email],
            'staff' => $staff ? User::role('admin')->get()->map(fn ($u) => ['id' => $u->id, 'name' => trim($u->full_name) ?: $u->email]) : [],
        ]);
    }

    public function orders(Request $request)
    {
        return DB::table('order')->where('user_id', $request->user()->id)->orderByDesc('id')->select('id')->paginate(25);
    }

    public function index(Request $request)
    {
        $staff = $this->staff($request);
        $request->validate([
            'q' => 'nullable|string|max:180', 'customer' => 'nullable|string|max:180',
            'category' => ['nullable', Rule::in(SupportTicket::CATEGORIES)],
            'status' => ['nullable', Rule::in(SupportTicket::STATUSES)],
            'priority' => ['nullable', Rule::in(SupportTicket::PRIORITIES)],
            'from' => 'nullable|date_format:Y-m-d', 'to' => ['nullable', 'date_format:Y-m-d', Rule::when($request->filled('from'), 'after_or_equal:from')],
        ]);
        $query = SupportTicket::query();
        if (! $staff) {
            $query->where('user_id', $request->user()->id);
        }
        foreach (['category', 'status', 'priority'] as $field) {
            if ($request->filled($field)) {
                $query->where($field, $request->input($field));
            }
        }
        if ($request->filled('q')) {
            $query->where(fn ($q) => $q->where('number', 'like', '%'.$request->q.'%')->orWhere('subject', 'like', '%'.$request->q.'%'));
        }
        if ($staff && $request->filled('customer')) {
            $query->where(fn ($q) => $q->where('customer_name', 'like', '%'.$request->customer.'%')->orWhere('customer_email', 'like', '%'.$request->customer.'%'));
        }
        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->input('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->input('to'));
        }
        $query->addSelect(['unread' => DB::table('support_alerts')->selectRaw('COUNT(*)')->whereColumn('ticket_id', 'support_tickets.id')->where('user_id', $request->user()->id)->where('audience', $staff ? 'staff' : 'customer')->whereNull('read_at')]);

        return $query->orderByDesc('updated_at')->orderByDesc('id')->paginate(15);
    }

    public function alerts(Request $request)
    {
        $staff = $this->staff($request);
        $query = DB::table('support_alerts')->where('user_id', $request->user()->id)->where('audience', $staff ? 'staff' : 'customer')->whereNull('read_at');

        return ['count' => (clone $query)->count(), 'items' => $query->orderByDesc('id')->limit(5)->get(['ticket_id', 'summary'])];
    }

    private function messageRules(): array
    {
        return ['message' => 'required|string|max:10000', 'attachments' => 'nullable|array|max:3', 'attachments.*' => 'required|file|mimes:jpg,jpeg,png,webp|max:2048'];
    }

    private function message(Request $request, SupportTicket $ticket, string $kind): void
    {
        $id = DB::table('support_messages')->insertGetId([
            'ticket_id' => $ticket->id, 'user_id' => $request->user()->id,
            'author' => trim($request->user()->full_name) ?: 'Support team', 'kind' => $kind,
            'body' => $request->message, 'created_at' => now(), 'updated_at' => now(),
        ]);
        foreach ($request->file('attachments', []) as $file) {
            DB::table('support_attachments')->insert(['message_id' => $id, 'name' => Str::limit(basename($file->getClientOriginalName()), 200, ''), 'mime' => $file->getMimeType(), 'content' => base64_encode(file_get_contents($file->getRealPath()))]);
        }
    }

    private function notify(SupportTicket $ticket, bool $staff, string $summary): void
    {
        $ids = $staff ? User::role('admin')->pluck('id') : collect([$ticket->user_id]);
        foreach ($ids as $id) {
            DB::table('support_alerts')->insert(['ticket_id' => $ticket->id, 'user_id' => $id, 'audience' => $staff ? 'staff' : 'customer', 'summary' => $summary, 'created_at' => now(), 'updated_at' => now()]);
        }
    }

    public function store(Request $request)
    {
        $request->validate($this->messageRules() + [
            'category' => ['required', Rule::in(SupportTicket::CATEGORIES)], 'subject' => 'required|string|max:180',
            'order_id' => ['nullable', 'integer', Rule::exists('order', 'id')->where('user_id', $request->user()->id)],
        ]);
        $ticket = DB::transaction(function () use ($request) {
            $ticket = SupportTicket::create([
                'number' => 'PF-'.strtoupper((string) Str::ulid()), 'user_id' => $request->user()->id,
                'customer_name' => trim($request->user()->full_name), 'customer_email' => $request->user()->email,
                'customer_mobile' => $request->user()->mobile, 'category' => $request->category,
                'subject' => $request->subject, 'order_id' => $request->order_id,
            ]);
            $this->message($request, $ticket, 'customer');
            $this->notify($ticket, true, 'New request: '.$ticket->number);

            return $ticket;
        });

        return response()->json($ticket, 201);
    }

    public function show(Request $request, SupportTicket $ticket)
    {
        $staff = $this->authorizeTicket($request, $ticket);

        return DB::transaction(function () use ($request, $ticket, $staff) {
            // Serialize reads with replies so a newly arriving reply cannot be marked read unseen.
            $ticket = SupportTicket::whereKey($ticket->id)->lockForUpdate()->firstOrFail();
            $messages = DB::table('support_messages')->where('ticket_id', $ticket->id)->when(! $staff, fn ($q) => $q->where('kind', '!=', 'internal'))->orderBy('id')->get();
            $attachments = DB::table('support_attachments')->whereIn('message_id', $messages->pluck('id'))->get(['id', 'message_id', 'name']);
            foreach ($messages as $message) {
                $message->attachments = $attachments->where('message_id', $message->id)->values();
                $message->created_at = Carbon::parse($message->created_at)->toISOString();
            }
            DB::table('support_alerts')->where('ticket_id', $ticket->id)->where('user_id', $request->user()->id)->where('audience', $staff ? 'staff' : 'customer')->whereNull('read_at')->update(['read_at' => now()]);

            return ['ticket' => $ticket, 'messages' => $messages];
        });
    }

    public function reply(Request $request, SupportTicket $ticket)
    {
        $staff = $this->authorizeTicket($request, $ticket);
        $request->validate($this->messageRules() + ['internal' => 'sometimes|boolean']);
        abort_if(! $staff && $request->boolean('internal'), 403);
        DB::transaction(function () use ($request, $ticket, $staff) {
            $ticket = SupportTicket::whereKey($ticket->id)->lockForUpdate()->firstOrFail();
            abort_if(! $staff && $ticket->status === 'Closed', 422, 'This ticket is closed. Please create a new request.');
            $internal = $staff && $request->boolean('internal');
            $this->message($request, $ticket, $internal ? 'internal' : ($staff ? 'staff' : 'customer'));
            if (! $staff && in_array($ticket->status, ['Resolved', 'Waiting for Customer'])) {
                $ticket->status = 'Open';
            }
            $ticket->updated_at = now();
            $ticket->save();
            if (! $internal) {
                $this->notify($ticket, ! $staff, 'New reply on '.$ticket->number);
            }
        });

        return response()->json(['saved' => true]);
    }

    public function update(Request $request, SupportTicket $ticket)
    {
        abort_unless($this->authorizeTicket($request, $ticket), 403);
        $data = $request->validate([
            'status' => ['required', Rule::in(SupportTicket::STATUSES)],
            'priority' => ['required', Rule::in(SupportTicket::PRIORITIES)], 'assigned_to' => 'nullable|integer',
        ]);
        if (! empty($data['assigned_to'])) {
            abort_unless(User::role('admin')->whereKey($data['assigned_to'])->exists(), 422, 'Choose an admin staff member.');
        }
        DB::transaction(function () use ($request, $ticket, $data) {
            $ticket = SupportTicket::whereKey($ticket->id)->lockForUpdate()->firstOrFail();
            $oldStatus = $ticket->status;
            $oldAssignment = $ticket->assigned_to;
            $ticket->fill($data);
            if (! $ticket->isDirty()) {
                return;
            }
            $ticket->save();
            if ($oldStatus !== $ticket->status) {
                DB::table('support_messages')->insert(['ticket_id' => $ticket->id, 'user_id' => $request->user()->id, 'author' => 'Support team', 'kind' => 'event', 'body' => 'Status changed from '.$oldStatus.' to '.$ticket->status.'.', 'created_at' => now(), 'updated_at' => now()]);
            }
            $this->notify($ticket, false, $ticket->number.' updated · '.$ticket->status);
            if ($oldAssignment != $ticket->assigned_to && $ticket->assigned_to) {
                DB::table('support_alerts')->insert(['ticket_id' => $ticket->id, 'user_id' => $ticket->assigned_to, 'audience' => 'staff', 'summary' => 'Assigned to you: '.$ticket->number, 'created_at' => now(), 'updated_at' => now()]);
            }
        });

        return response()->json(['saved' => true]);
    }

    public function attachment(Request $request, SupportTicket $ticket, int $attachment)
    {
        $staff = $this->authorizeTicket($request, $ticket);
        $file = DB::table('support_attachments as a')->join('support_messages as m', 'm.id', '=', 'a.message_id')->where('m.ticket_id', $ticket->id)->where('a.id', $attachment)->when(! $staff, fn ($q) => $q->where('m.kind', '!=', 'internal'))->select('a.*')->first();
        abort_unless($file, 404);

        return response(base64_decode($file->content))->header('Content-Type', $file->mime)->header('Content-Disposition', 'attachment; filename="screenshot-'.$file->id.'.'.match ($file->mime) {
            'image/png' => 'png', 'image/webp' => 'webp', default => 'jpg'
        }.'"')->header('Cache-Control', 'private, no-store')->header('X-Content-Type-Options', 'nosniff');
    }
}
