<?php

namespace App\Http\Controllers\Admin;

use App\Agent;
use App\Http\Controllers\Controller;
use App\Mail\MerchantApplicationStatusMail;
use App\MerchantApplication;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use LogicException;
use Throwable;

class MerchantApplicationController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'agent_id' => ['nullable', 'integer', Rule::exists('agents', 'id')],
            'status' => ['nullable', Rule::in([
                MerchantApplication::STATUS_PENDING,
                MerchantApplication::STATUS_APPROVED,
                MerchantApplication::STATUS_DECLINED,
            ])],
        ]);

        $search = trim($validated['search'] ?? '');
        $status = $validated['status'] ?? '';
        $agentId = isset($validated['agent_id']) ? (int) $validated['agent_id'] : null;
        $applications = MerchantApplication::query()
            ->with('agent:id,name,referral_code')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('business_name', 'like', "%{$search}%")
                        ->orWhere('registered_business_name', 'like', "%{$search}%")
                        ->orWhere('owner_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('mobile', 'like', "%{$search}%");
                });
            })
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when($agentId !== null, fn ($query) => $query->where('agent_id', $agentId))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        $metrics = [
            'total' => MerchantApplication::query()->count(),
            'pending' => MerchantApplication::query()->where('status', MerchantApplication::STATUS_PENDING)->count(),
            'approved' => MerchantApplication::query()->where('status', MerchantApplication::STATUS_APPROVED)->count(),
            'declined' => MerchantApplication::query()->where('status', MerchantApplication::STATUS_DECLINED)->count(),
        ];
        $agents = Agent::query()
            ->withCount('referredApplications')
            ->orderBy('name')
            ->get(['id', 'name', 'referral_code']);

        return view('dashboard.pages.merchant-applications.index', compact('applications', 'metrics', 'search', 'status', 'agents', 'agentId'));
    }

    public function show(MerchantApplication $application): View
    {
        $application->load('agent:id,name,email,mobile,referral_code');

        return view('dashboard.pages.merchant-applications.show', compact('application'));
    }

    public function approve(Request $request, MerchantApplication $application): RedirectResponse
    {
        return $this->review($request, $application, MerchantApplication::STATUS_APPROVED);
    }

    public function decline(Request $request, MerchantApplication $application): RedirectResponse
    {
        return $this->review($request, $application, MerchantApplication::STATUS_DECLINED);
    }

    private function review(Request $request, MerchantApplication $application, string $decision): RedirectResponse
    {
        $validated = $request->validate([
            'message' => [$decision === MerchantApplication::STATUS_DECLINED ? 'required' : 'nullable', 'string', 'max:2000'],
        ]);
        $message = trim($validated['message'] ?? '') ?: null;

        if (! $application->isPending()) {
            return back()->withErrors(['review' => 'This merchant application has already been reviewed.']);
        }

        try {
            DB::transaction(function () use ($application, $decision, $message, $request) {
                $pendingApplication = MerchantApplication::query()->lockForUpdate()->findOrFail($application->id);

                if (! $pendingApplication->isPending()) {
                    throw new LogicException('This merchant application has already been reviewed.');
                }

                $pendingApplication->update([
                    'status' => $decision,
                    'review_message' => $message,
                    'reviewed_by' => $request->user()?->getKey(),
                    'reviewed_at' => now(),
                ]);

                $pendingApplication->syncReferralToPartner();

                Mail::to($pendingApplication->email)->send(new MerchantApplicationStatusMail($pendingApplication, $message));
            });
        } catch (Throwable $exception) {
            Log::error('Merchant application review could not be completed.', [
                'merchant_application_id' => $application->id,
                'email' => $application->email,
                'decision' => $decision,
                'exception' => $exception->getMessage(),
            ]);

            return back()->withErrors(['review' => 'The application could not be reviewed or the email could not be delivered. Please try again.']);
        }

        return back()->with('success', $application->business_name.' was '.$decision.' and the applicant was notified by email.');
    }
}
