<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\RiderApplicationApprovedMail;
use App\Mail\RiderApplicationDeclinedMail;
use App\Model\Rider\Rider;
use App\Models\User;
use App\RiderApplication;
use App\RiderApplicationDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class RiderManagementController extends Controller
{
    public function available(Request $request): View
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        $search = trim($validated['search'] ?? '');
        $maxPendingOffers = max(1, (int) config('rider.offer_max_pending_per_rider', 3));

        $approvedRiders = DB::table('rider as riders')
            ->where('riders.active', true)
            ->whereNull('riders.archived_at');

        $availableRiders = (clone $approvedRiders)
            ->join('rider_api_availability as availability', 'availability.rider_id', '=', 'riders.id')
            ->where('availability.state', 'available');

        $readyRiders = (clone $availableRiders)
            ->whereExists(function ($query) {
                $query->selectRaw('1')
                    ->from('rider_api_locations as location')
                    ->whereColumn('location.rider_id', 'riders.id');
            })
            ->whereNotExists(function ($query) {
                $query->selectRaw('1')
                    ->from('rider_api_deliveries as active_delivery')
                    ->whereColumn('active_delivery.rider_id', 'riders.id')
                    ->whereNotIn('active_delivery.current_state', ['delivered', 'cancelled', 'failed']);
            })
            ->whereRaw('(SELECT COUNT(*) FROM rider_api_offers AS pending_offer JOIN rider_api_deliveries AS pending_delivery ON pending_delivery.id = pending_offer.delivery_id WHERE pending_offer.rider_id = riders.id AND pending_offer.status = ? AND pending_offer.expires_at > ? AND pending_delivery.current_state = ? AND pending_delivery.rider_id IS NULL) < ?', [
                'pending', now(), 'offered', $maxPendingOffers,
            ]);

        $readyCount = (clone $readyRiders)->count();
        $availableCount = (clone $availableRiders)->count();
        $approvedCount = (clone $approvedRiders)->count();
        $busyCount = (clone $approvedRiders)
            ->whereExists(function ($query) {
                $query->selectRaw('1')
                    ->from('rider_api_deliveries as active_delivery')
                    ->whereColumn('active_delivery.rider_id', 'riders.id')
                    ->whereNotIn('active_delivery.current_state', ['delivered', 'cancelled', 'failed']);
            })
            ->count();

        $riders = $readyRiders
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('riders.name', 'like', "%{$search}%")
                        ->orWhere('riders.mobile', 'like', "%{$search}%");
                });
            })
            ->select([
                'riders.id',
                'riders.name',
                'riders.mobile',
                'availability.heartbeat_at',
            ])
            ->selectSub(function ($query) {
                $query->from('rider_api_locations as latest_location')
                    ->whereColumn('latest_location.rider_id', 'riders.id')
                    ->orderByDesc('latest_location.recorded_at')
                    ->orderByDesc('latest_location.id')
                    ->limit(1)
                    ->select('latest_location.recorded_at');
            }, 'location_recorded_at')
            ->selectSub(function ($query) {
                $query->from('rider_api_devices as latest_device')
                    ->whereColumn('latest_device.rider_id', 'riders.id')
                    ->whereNull('latest_device.revoked_at')
                    ->whereNotNull('latest_device.push_token')
                    ->where('latest_device.push_token', '!=', '')
                    ->orderByDesc('latest_device.last_seen_at')
                    ->limit(1)
                    ->select('latest_device.last_seen_at');
            }, 'device_last_seen_at')
            ->selectSub(function ($query) {
                $query->from('rider_api_devices as push_device')
                    ->whereColumn('push_device.rider_id', 'riders.id')
                    ->whereNull('push_device.revoked_at')
                    ->whereNotNull('push_device.push_token')
                    ->where('push_device.push_token', '!=', '')
                    ->limit(1)
                    ->select('push_device.id');
            }, 'push_device_id')
            ->selectSub(function ($query) {
                $query->from('rider_api_offers as pending_offer')
                    ->join('rider_api_deliveries as pending_delivery', 'pending_delivery.id', '=', 'pending_offer.delivery_id')
                    ->whereColumn('pending_offer.rider_id', 'riders.id')
                    ->where('pending_offer.status', 'pending')
                    ->where('pending_offer.expires_at', '>', now())
                    ->where('pending_delivery.current_state', 'offered')
                    ->whereNull('pending_delivery.rider_id')
                    ->selectRaw('COUNT(*)');
            }, 'pending_offer_count')
            ->orderByDesc('availability.heartbeat_at')
            ->orderBy('riders.name')
            ->paginate(25)
            ->withQueryString();

        $metrics = [
            'ready' => $readyCount,
            'available' => $availableCount,
            'busy' => $busyCount,
            'unavailable' => max(0, $approvedCount - $availableCount - $busyCount),
        ];

        return view('dashboard.pages.riders.available', compact(
            'riders',
            'metrics',
            'search',
            'maxPendingOffers',
        ));
    }

    public function index(Request $request): View
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'view' => ['nullable', 'in:active,archived'],
        ]);

        $search = trim($validated['search'] ?? '');
        $view = $validated['view'] ?? 'active';
        $riders = Rider::query()
            ->with('wallet')
            ->when($view === 'archived', fn ($query) => $query->whereNotNull('archived_at'))
            ->when($view === 'active', fn ($query) => $query->whereNull('archived_at'))
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
            'total' => Rider::query()->whereNull('archived_at')->count(),
            'archived' => Rider::query()->whereNotNull('archived_at')->count(),
            'approved' => Rider::query()->whereNull('archived_at')->where('active', true)->count(),
            'pending' => Rider::query()->whereNull('archived_at')->where(function ($query) {
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

        return view('dashboard.pages.riders.index', compact('riders', 'applications', 'metrics', 'pendingTopUps', 'search', 'view'));
    }

    public function show(Rider $rider): View
    {
        $rider->load('wallet');

        // The legacy rider table has no application_id. Match both fields to avoid
        // showing another applicant's private details when a number is reused.
        $application = $rider->name && $rider->mobile
            ? RiderApplication::query()
                ->with('documents')
                ->where('full_name', $rider->name)
                ->where('mobile', $rider->mobile)
                ->latest('id')
                ->first()
            : null;

        return view('dashboard.pages.riders.show', compact('rider', 'application'));
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
        try {
            DB::transaction(function () use ($application) {
                $pendingApplication = RiderApplication::query()->lockForUpdate()->findOrFail($application->id);

                if ($pendingApplication->status !== RiderApplication::STATUS_PENDING) {
                    throw new \LogicException('Only pending rider applications can be approved.');
                }

                $pendingApplication->forceFill([
                    'status' => RiderApplication::STATUS_APPROVED,
                    'review_notes' => null,
                ])->save();
                // we need to create a user account for the rider if it doesn't exist yet.
                // user was created during submission of the application, so we just need to find the user by email.

                $user = User::where('email', $pendingApplication->email)->first();

                \Log::info(['user' => $user]);
            
                // we just need to update the rider table to active and approved_at timestamp.
               $rider = DB::table('rider')
                    ->where('user_id', $user->id)
                    ->first();

                if ($rider) {
                    DB::table('rider')
                        ->where('user_id', $user->id)
                        ->update([
                            'active' => true,
                            'is_active' => true,
                            'approved_at' => now(),
                            'updated_at' => now(),
                        ]);
                }

                DB::table('rider_api_activity_logs')->insert([
                    'rider_id' => $rider->id,
                    'type' => 'admin_approval',
                    'payload' => json_encode([
                        'performed_by_user_id' => auth()->id(),
                        'active' => true,
                        'is_active' => true,
                        'approved_at' => now(),
                    ], JSON_THROW_ON_ERROR),
                    'recorded_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);


                Mail::to($pendingApplication->email)->send(new RiderApplicationApprovedMail($pendingApplication));
            });
        } catch (\LogicException $exception) {
            return back()->withErrors(['application' => $exception->getMessage()]);
        } catch (Throwable $exception) {
            Log::error('Rider application approval could not be completed.', [
                'application_id' => $application->id,
                'exception' => $exception->getMessage(),
            ]);

            return back()->withErrors(['application' => 'The application could not be approved or the email could not be delivered. Please try again.']);
        }

        return back()->with('success', $application->full_name.'\'s application was approved and the rider was notified by email.');
    }

    public function declineApplication(Request $request, RiderApplication $application): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:2000'],
        ]);

        try {
            $declined = DB::transaction(function () use ($application, $validated) {
                $pendingApplication = RiderApplication::query()->lockForUpdate()->findOrFail($application->id);

                if ($pendingApplication->status !== RiderApplication::STATUS_PENDING) {
                    return false;
                }

                $pendingApplication->forceFill([
                    'status' => RiderApplication::STATUS_REJECTED,
                    'review_notes' => trim($validated['reason']),
                ])->save();

                $userId = DB::table('users')->where('email', $pendingApplication->email)->value('id');

                if ($userId) {
                    DB::table('rider')->where('user_id', $userId)->update([
                        'active' => false,
                        'is_active' => false,
                        'archived_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                Mail::to($pendingApplication->email)->send(new RiderApplicationDeclinedMail($pendingApplication));

                return true;
            });
        } catch (Throwable $exception) {
            Log::error('Rider application decline could not be completed.', [
                'application_id' => $application->id,
                'exception' => $exception->getMessage(),
            ]);

            return back()->withErrors(['application' => 'The application could not be declined or the email could not be delivered. Please try again.']);
        }

        return $declined
            ? back()->with('success', $application->full_name.'\'s application was declined, the rider was archived, and an email was sent.')
            : back()->withErrors(['application' => 'Only pending rider applications can be declined.']);
    }

    public function archive(Rider $rider): RedirectResponse
    {
        if ($rider->archived_at) {
            return back()->with('success', $rider->name.' is already archived.');
        }

        $rider->forceFill(['archived_at' => now()])->save();

        return back()->with('success', $rider->name.' was archived.');
    }

    public function restore(Rider $rider): RedirectResponse
    {
        $rider->forceFill(['archived_at' => null])->save();

        return back()->with('success', $rider->name.' was restored.');
    }

    public function approve(Rider $rider): RedirectResponse
    {
        if ($rider->archived_at) {
            return back()->withErrors(['rider' => 'Restore this rider before approving the account.']);
        }
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
