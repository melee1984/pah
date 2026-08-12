<?php

namespace App\Http\Controllers\Api\V1\Rider;

use App\Http\Controllers\Controller;
use App\Services\RiderApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class WalletController extends Controller
{
    public function __construct(private readonly RiderApiService $riders) {}

    public function wallet(Request $request): JsonResponse
    {
        $wallet = $this->riders->wallet($this->riders->rider($request)->id);

        return response()->json(['wallet' => $this->walletData($wallet)]);
    }

    public function earnings(Request $request): JsonResponse
    {
        $riderId = $this->riders->rider($request)->id;
        $query = fn () => DB::table('rider_api_wallet_transactions')
            ->where('rider_id', $riderId)
            ->where('type', 'earning');

        return response()->json([
            'earnings' => [
                'today_centavos' => (int) $query()->whereDate('occurred_at', today())->sum('amount_centavos'),
                'week_centavos' => (int) $query()->whereBetween('occurred_at', [
                    now()->startOfWeek(),
                    now()->endOfWeek(),
                ])->sum('amount_centavos'),
                'month_centavos' => (int) $query()->whereBetween('occurred_at', [
                    now()->startOfMonth(),
                    now()->endOfMonth(),
                ])->sum('amount_centavos'),
            ],
        ]);
    }

    public function transactions(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['nullable', 'string', 'max:40'],
            'limit' => ['nullable', 'integer', 'between:1,100'],
        ]);
        $query = DB::table('rider_api_wallet_transactions')
            ->where('rider_id', $this->riders->rider($request)->id)
            ->orderByDesc('occurred_at');

        if (isset($validated['type'])) {
            $query->where('type', $validated['type']);
        }

        $paginator = $query->cursorPaginate($validated['limit'] ?? 20);

        return response()->json([
            'transactions' => collect($paginator->items())->map(fn (object $transaction) => [
                'id' => $transaction->reference,
                'type' => $transaction->type,
                'amount_centavos' => (int) $transaction->amount_centavos,
                'balance_after_centavos' => (int) $transaction->balance_after_centavos,
                'description' => $transaction->description,
                'related_type' => $transaction->related_type,
                'related_reference' => $transaction->related_reference,
                'occurred_at' => $transaction->occurred_at,
            ]),
            'next_cursor' => $paginator->nextCursor()?->encode(),
        ]);
    }

    public function cod(Request $request): JsonResponse
    {
        $wallet = $this->riders->wallet($this->riders->rider($request)->id);

        return response()->json([
            'cod' => [
                'cash_collected_centavos' => (int) $wallet->cash_collected_centavos,
                'amount_owed_centavos' => (int) $wallet->amount_owed_centavos,
                'daily_limit_centavos' => (int) $wallet->daily_cod_limit_centavos,
                'remaining_limit_centavos' => max(
                    0,
                    (int) $wallet->daily_cod_limit_centavos - (int) $wallet->amount_owed_centavos,
                ),
            ],
        ]);
    }

    public function remittanceInstructions(): JsonResponse
    {
        return response()->json([
            'instructions' => [
                'method' => config('services.rider_cod.method', 'contact_support'),
                'account_name' => config('services.rider_cod.account_name'),
                'account_number' => config('services.rider_cod.account_number'),
                'notes' => config(
                    'services.rider_cod.notes',
                    'Contact Pahatud support for the current COD remittance destination.',
                ),
            ],
        ]);
    }

    public function submitRemittance(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'amount_centavos' => ['required', 'integer', 'min:1'],
            'proof' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:10240'],
        ]);
        $rider = $this->riders->rider($request);
        $wallet = $this->riders->wallet($rider->id);

        if ($validated['amount_centavos'] > $wallet->amount_owed_centavos) {
            return response()->json([
                'message' => 'Remittance amount exceeds the current amount owed.',
                'amount_owed_centavos' => (int) $wallet->amount_owed_centavos,
            ], 409);
        }

        $reference = (string) Str::uuid();
        $path = $request->file('proof')->store("rider-cod-remittances/{$reference}", 'local');
        if (! $path) {
            throw new RuntimeException('The COD remittance proof could not be stored.');
        }

        try {
            DB::table('rider_api_cod_remittances')->insert([
                'reference' => $reference,
                'rider_id' => $rider->id,
                'amount_centavos' => $validated['amount_centavos'],
                'proof_path' => $path,
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($path);
            throw $exception;
        }

        return response()->json([
            'message' => 'COD remittance submitted for review.',
            'remittance' => [
                'id' => $reference,
                'amount_centavos' => (int) $validated['amount_centavos'],
                'status' => 'pending',
            ],
        ], 201);
    }

    public function remittance(Request $request, string $remittance): JsonResponse
    {
        $record = DB::table('rider_api_cod_remittances')
            ->where('rider_id', $this->riders->rider($request)->id)
            ->where('reference', $remittance)
            ->first();
        abort_if(! $record, 404);

        return response()->json([
            'remittance' => [
                'id' => $record->reference,
                'amount_centavos' => (int) $record->amount_centavos,
                'status' => $record->status,
                'review_notes' => $record->review_notes,
                'submitted_at' => $record->created_at,
            ],
        ]);
    }

    public function payouts(Request $request): JsonResponse
    {
        $payouts = DB::table('rider_api_withdrawals')
            ->where('rider_id', $this->riders->rider($request)->id)
            ->whereIn('status', ['processing', 'paid', 'failed'])
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json([
            'payouts' => collect($payouts->items())->map(fn (object $payout) => $this->withdrawalData($payout)),
            'pagination' => [
                'current_page' => $payouts->currentPage(),
                'last_page' => $payouts->lastPage(),
                'total' => $payouts->total(),
            ],
        ]);
    }

    public function payout(Request $request, string $payout): JsonResponse
    {
        return response()->json([
            'payout' => $this->withdrawalData($this->ownedWithdrawal($request, $payout)),
        ]);
    }

    public function requestWithdrawal(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'payout_account_id' => ['required', 'uuid'],
            'amount_centavos' => ['required', 'integer', 'min:100'],
        ]);
        $rider = $this->riders->rider($request);
        $account = DB::table('rider_api_payout_accounts')
            ->where('rider_id', $rider->id)
            ->where('reference', $validated['payout_account_id'])
            ->first();
        abort_if(! $account, 404);
        $wallet = $this->riders->wallet($rider->id);

        if ($validated['amount_centavos'] > $wallet->available_centavos) {
            return response()->json([
                'message' => 'Withdrawal amount exceeds the available balance.',
                'available_centavos' => (int) $wallet->available_centavos,
            ], 409);
        }

        $reference = (string) Str::uuid();
        DB::transaction(function () use ($rider, $account, $wallet, $validated, $reference) {
            $newAvailable = $wallet->available_centavos - $validated['amount_centavos'];
            DB::table('rider_api_wallets')->where('id', $wallet->id)->update([
                'available_centavos' => $newAvailable,
                'pending_centavos' => $wallet->pending_centavos + $validated['amount_centavos'],
                'updated_at' => now(),
            ]);
            DB::table('rider_api_withdrawals')->insert([
                'reference' => $reference,
                'rider_id' => $rider->id,
                'payout_account_id' => $account->id,
                'amount_centavos' => $validated['amount_centavos'],
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('rider_api_wallet_transactions')->insert([
                'reference' => (string) Str::uuid(),
                'rider_id' => $rider->id,
                'type' => 'withdrawal_requested',
                'amount_centavos' => -$validated['amount_centavos'],
                'balance_after_centavos' => $newAvailable,
                'description' => 'Withdrawal requested',
                'related_type' => 'withdrawal',
                'related_reference' => $reference,
                'occurred_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        return response()->json([
            'message' => 'Withdrawal requested.',
            'withdrawal' => $this->withdrawalData(
                DB::table('rider_api_withdrawals')->where('reference', $reference)->first(),
            ),
        ], 201);
    }

    public function withdrawal(Request $request, string $withdrawal): JsonResponse
    {
        return response()->json([
            'withdrawal' => $this->withdrawalData(
                $this->ownedWithdrawal($request, $withdrawal),
            ),
        ]);
    }

    public function payoutAccounts(Request $request): JsonResponse
    {
        $accounts = DB::table('rider_api_payout_accounts')
            ->where('rider_id', $this->riders->rider($request)->id)
            ->orderByDesc('is_default')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (object $account) => $this->payoutAccountData($account));

        return response()->json(['payout_accounts' => $accounts]);
    }

    public function addPayoutAccount(Request $request): JsonResponse
    {
        $validated = $this->validatePayoutAccount($request);
        $rider = $this->riders->rider($request);
        $reference = (string) Str::uuid();
        $hasAccount = DB::table('rider_api_payout_accounts')
            ->where('rider_id', $rider->id)
            ->exists();

        DB::table('rider_api_payout_accounts')->insert([
            'reference' => $reference,
            'rider_id' => $rider->id,
            'method' => $validated['method'],
            'account_name' => $validated['account_name'],
            'account_number' => Crypt::encryptString($validated['account_number']),
            'is_default' => ! $hasAccount || ($validated['is_default'] ?? false),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'message' => 'Payout account added.',
            'payout_account' => $this->payoutAccountData(
                DB::table('rider_api_payout_accounts')->where('reference', $reference)->first(),
            ),
        ], 201);
    }

    public function updatePayoutAccount(Request $request, string $account): JsonResponse
    {
        $validated = $this->validatePayoutAccount($request, false);
        $rider = $this->riders->rider($request);
        $record = DB::table('rider_api_payout_accounts')
            ->where('rider_id', $rider->id)
            ->where('reference', $account)
            ->first();
        abort_if(! $record, 404);

        $updates = ['updated_at' => now(), 'verified_at' => null];
        foreach (['method', 'account_name'] as $field) {
            if (array_key_exists($field, $validated)) {
                $updates[$field] = $validated[$field];
            }
        }
        if (array_key_exists('account_number', $validated)) {
            $updates['account_number'] = Crypt::encryptString($validated['account_number']);
        }
        if ($validated['is_default'] ?? false) {
            DB::table('rider_api_payout_accounts')
                ->where('rider_id', $rider->id)
                ->update(['is_default' => false, 'updated_at' => now()]);
            $updates['is_default'] = true;
        }

        DB::table('rider_api_payout_accounts')->where('id', $record->id)->update($updates);

        return response()->json([
            'message' => 'Payout account updated.',
            'payout_account' => $this->payoutAccountData(
                DB::table('rider_api_payout_accounts')->where('id', $record->id)->first(),
            ),
        ]);
    }

    public function deletePayoutAccount(Request $request, string $account): JsonResponse
    {
        $rider = $this->riders->rider($request);
        $record = DB::table('rider_api_payout_accounts')
            ->where('rider_id', $rider->id)
            ->where('reference', $account)
            ->first();
        abort_if(! $record, 404);
        $pending = DB::table('rider_api_withdrawals')
            ->where('payout_account_id', $record->id)
            ->whereIn('status', ['pending', 'processing'])
            ->exists();
        abort_if($pending, 409, 'This payout account has an active withdrawal.');

        DB::table('rider_api_payout_accounts')->where('id', $record->id)->delete();

        return response()->json(['message' => 'Payout account removed.']);
    }

    public function dispute(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'transaction_id' => ['nullable', 'uuid'],
            'type' => ['required', 'string', 'max:50'],
            'description' => ['required', 'string', 'max:2000'],
        ]);
        $rider = $this->riders->rider($request);

        if (isset($validated['transaction_id'])) {
            $exists = DB::table('rider_api_wallet_transactions')
                ->where('rider_id', $rider->id)
                ->where('reference', $validated['transaction_id'])
                ->exists();
            abort_if(! $exists, 404);
        }

        $reference = (string) Str::uuid();
        DB::table('rider_api_wallet_disputes')->insert([
            'reference' => $reference,
            'rider_id' => $rider->id,
            'transaction_reference' => $validated['transaction_id'] ?? null,
            'type' => $validated['type'],
            'description' => $validated['description'],
            'status' => 'open',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'message' => 'Wallet dispute submitted.',
            'dispute' => ['id' => $reference, 'status' => 'open'],
        ], 201);
    }

    /**
     * @return array<string, int>
     */
    private function walletData(object $wallet): array
    {
        return [
            'available_centavos' => (int) $wallet->available_centavos,
            'pending_centavos' => (int) $wallet->pending_centavos,
            'cash_collected_centavos' => (int) $wallet->cash_collected_centavos,
            'amount_owed_centavos' => (int) $wallet->amount_owed_centavos,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function withdrawalData(object $withdrawal): array
    {
        return [
            'id' => $withdrawal->reference,
            'amount_centavos' => (int) $withdrawal->amount_centavos,
            'status' => $withdrawal->status,
            'created_at' => $withdrawal->created_at,
            'updated_at' => $withdrawal->updated_at,
        ];
    }

    private function ownedWithdrawal(Request $request, string $reference): object
    {
        $withdrawal = DB::table('rider_api_withdrawals')
            ->where('rider_id', $this->riders->rider($request)->id)
            ->where('reference', $reference)
            ->first();
        abort_if(! $withdrawal, 404);

        return $withdrawal;
    }

    /**
     * @return array<string, mixed>
     */
    private function payoutAccountData(object $account): array
    {
        $number = Crypt::decryptString($account->account_number);

        return [
            'id' => $account->reference,
            'method' => $account->method,
            'account_name' => $account->account_name,
            'masked_account_number' => str_repeat('•', max(0, strlen($number) - 4)).substr($number, -4),
            'is_default' => (bool) $account->is_default,
            'verified_at' => $account->verified_at,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validatePayoutAccount(Request $request, bool $required = true): array
    {
        $presence = $required ? 'required' : 'sometimes';

        return $request->validate([
            'method' => [$presence, 'string', 'max:50'],
            'account_name' => [$presence, 'string', 'max:255'],
            'account_number' => [$presence, 'string', 'max:100'],
            'is_default' => ['nullable', 'boolean'],
        ]);
    }
}
