<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Model\Rider\Rider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

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

        $metrics = [
            'total' => Rider::query()->count(),
            'approved' => Rider::query()->where('active', true)->count(),
            'pending' => Rider::query()->where(function ($query) {
                $query->whereNull('active')->orWhere('active', false);
            })->count(),
            'credits' => (float) DB::table('rider_api_wallets')->sum('credit_amount'),
        ];

        return view('dashboard.pages.riders.index', compact('riders', 'metrics', 'search'));
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
}
