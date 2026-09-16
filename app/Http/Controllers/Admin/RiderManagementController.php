<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Model\Rider\Rider;
use App\RiderApplication;
use App\RiderApplicationDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RiderManagementController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        $search = trim($validated['search'] ?? '');
        $riders = Rider::query()
            ->with('wallet')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('mobile', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();
        $applications = RiderApplication::query()
            ->with('documents')
            ->where('status', RiderApplication::STATUS_PENDING)
            ->orderBy('submitted_at')
            ->paginate(15, ['*'], 'applications_page')
            ->withQueryString();

        $metrics = [
            'total' => Rider::query()->count(),
            'approved' => Rider::query()->where('active', true)->count(),
            'pending' => Rider::query()->where(function ($query) {
                $query->whereNull('active')->orWhere('active', false);
            })->count(),
            'credits' => (float) DB::table('rider_api_wallets')->sum('credit_amount'),
        ];
        $pendingTopUps = DB::table('rider_api_wallet_top_ups as top_ups')
            ->join('rider as riders', 'riders.id', '=', 'top_ups.rider_id')
            ->where('top_ups.status', 'pending')
            ->select([
                'top_ups.reference',
                'top_ups.rider_id',
                'top_ups.amount_centavos',
                'top_ups.payment_method',
                'top_ups.payment_reference',
                'top_ups.proof_original_name',
                'top_ups.created_at',
                'riders.name as rider_name',
            ])
            ->orderByDesc('top_ups.created_at')
            ->limit(50)
            ->get();

        return view('dashboard.pages.riders.index', compact('riders', 'applications', 'metrics', 'pendingTopUps', 'search'));
    }

    public function applicationDocument(RiderApplication $application, RiderApplicationDocument $document): StreamedResponse
    {
        abort_unless($document->rider_application_id === $application->id, 404);
        abort_unless(Storage::disk('local')->exists($document->path), 404);

        return Storage::disk('local')->response(
            $document->path,
            $document->original_name,
            ['Content-Type' => $document->mime_type ?: 'application/octet-stream'],
        );
    }

    public function approveApplication(RiderApplication $application): RedirectResponse
    {
        if ($application->status !== RiderApplication::STATUS_PENDING) {
            return back()->withErrors(['application' => 'Only pending rider applications can be approved.']);
        }

        $application->forceFill([
            'status' => RiderApplication::STATUS_APPROVED,
            'review_notes' => null,
        ])->save();

        return back()->with('success', $application->full_name.'\'s application was approved. They can now activate their rider account.');
    }

    public function approve(Rider $rider): RedirectResponse
    {
        $alreadyApproved = (bool) $rider->active && $rider->approved_at !== null;

        if ($alreadyApproved) {
            return back()->with('success', $rider->name.' is already approved.');
        }

        DB::transaction(function () use ($rider) {
            $approvedAt = now();

            DB::table('rider')->where('id', $rider->id)->update([
                'active' => true,
                'is_active' => true,
                'approved_at' => $approvedAt,
                'updated_at' => $approvedAt,
            ]);

            DB::table('rider_api_activity_logs')->insert([
                'rider_id' => $rider->id,
                'type' => 'admin_approval',
                'payload' => json_encode([
                    'performed_by_user_id' => auth()->id(),
                    'active' => true,
                    'is_active' => true,
                    'approved_at' => $approvedAt->toISOString(),
                ], JSON_THROW_ON_ERROR),
                'recorded_at' => $approvedAt,
                'created_at' => $approvedAt,
                'updated_at' => $approvedAt,
            ]);
        });

        return back()->with('success', $rider->name.' was approved and activated.');
    }

    public function adjustCredits(Request $request, Rider $rider): RedirectResponse
    {
        $validated = $request->validate([
            'action' => ['required', 'in:add,deduct'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:1000000', 'decimal:0,2'],
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $amountCentavos = (int) round((float) $validated['amount'] * 100);
        $signedAmountCentavos = $validated['action'] === 'deduct'
            ? -$amountCentavos
            : $amountCentavos;

        DB::transaction(function () use ($rider, $validated, $signedAmountCentavos) {
            $now = now();
            DB::table('rider_api_wallets')->insertOrIgnore([
                'rider_id' => $rider->id,
                'credit_amount' => 0,
                'credit_points' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $wallet = DB::table('rider_api_wallets')
                ->where('rider_id', $rider->id)
                ->lockForUpdate()
                ->first();
            $currentBalanceCentavos = (int) round((float) $wallet->credit_amount * 100);
            $newBalanceCentavos = $currentBalanceCentavos + $signedAmountCentavos;

            if ($newBalanceCentavos < 0) {
                throw ValidationException::withMessages([
                    'amount' => 'The deduction cannot be greater than the rider\'s current credit balance.',
                ]);
            }

            DB::table('rider_api_wallets')->where('id', $wallet->id)->update([
                'credit_amount' => $newBalanceCentavos / 100,
                'updated_at' => $now,
            ]);

            DB::table('rider_api_wallet_transactions')->insert([
                'reference' => (string) Str::uuid(),
                'rider_id' => $rider->id,
                'type' => 'admin_credit_adjustment',
                'amount_centavos' => $signedAmountCentavos,
                'balance_after_centavos' => $newBalanceCentavos,
                'description' => trim($validated['reason']),
                'related_type' => 'admin_adjustment',
                'related_reference' => (string) Str::uuid(),
                'performed_by_user_id' => auth()->id(),
                'occurred_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        });

        $verb = $validated['action'] === 'deduct' ? 'deducted from' : 'added to';

        return back()->with('success', '₱'.number_format((float) $validated['amount'], 2).' was '.$verb.' '.$rider->name.'\'s credits.');
    }

    public function viewTopUpProof(string $topUp): StreamedResponse
    {
        $record = DB::table('rider_api_wallet_top_ups')->where('reference', $topUp)->first();
        abort_if(! $record || ! Storage::disk('local')->exists($record->proof_path), 404);

        return Storage::disk('local')->response(
            $record->proof_path,
            $record->proof_original_name,
            ['Content-Type' => $record->proof_mime_type ?: 'application/octet-stream'],
        );
    }

    public function approveTopUp(string $topUp): RedirectResponse
    {
        $result = DB::transaction(function () use ($topUp) {
            $record = DB::table('rider_api_wallet_top_ups')
                ->where('reference', $topUp)
                ->lockForUpdate()
                ->first();
            abort_if(! $record, 404);

            if ($record->status === 'approved') {
                return ['already_approved' => true, 'record' => $record];
            }

            abort_if($record->status !== 'pending', 409, 'Only pending wallet top-ups can be approved.');

            $now = now();
            DB::table('rider_api_wallets')->insertOrIgnore([
                'rider_id' => $record->rider_id,
                'credit_amount' => 0,
                'credit_points' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $wallet = DB::table('rider_api_wallets')
                ->where('rider_id', $record->rider_id)
                ->lockForUpdate()
                ->first();
            $currentBalanceCentavos = (int) round((float) $wallet->credit_amount * 100);
            $newBalanceCentavos = $currentBalanceCentavos + (int) $record->amount_centavos;

            DB::table('rider_api_wallets')->where('id', $wallet->id)->update([
                'credit_amount' => $newBalanceCentavos / 100,
                'updated_at' => $now,
            ]);
            DB::table('rider_api_wallet_transactions')->insert([
                'reference' => (string) Str::uuid(),
                'rider_id' => $record->rider_id,
                'type' => 'wallet_top_up',
                'amount_centavos' => (int) $record->amount_centavos,
                'balance_after_centavos' => $newBalanceCentavos,
                'description' => 'Approved rider wallet top-up',
                'related_type' => 'wallet_top_up',
                'related_reference' => $record->reference,
                'performed_by_user_id' => auth()->id(),
                'occurred_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            DB::table('rider_api_wallet_top_ups')->where('id', $record->id)->update([
                'status' => 'approved',
                'reviewed_by_user_id' => auth()->id(),
                'reviewed_at' => $now,
                'updated_at' => $now,
            ]);
            DB::table('rider_api_activity_logs')->insert([
                'rider_id' => $record->rider_id,
                'type' => 'top_up_approved',
                'payload' => json_encode([
                    'top_up_reference' => $record->reference,
                    'amount_centavos' => (int) $record->amount_centavos,
                    'performed_by_user_id' => auth()->id(),
                ], JSON_THROW_ON_ERROR),
                'recorded_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            return ['already_approved' => false, 'record' => $record];
        });

        if ($result['already_approved']) {
            return back()->with('success', 'This wallet top-up was already approved.');
        }

        return back()->with('success', 'The rider wallet top-up was approved and credited.');
    }
}
