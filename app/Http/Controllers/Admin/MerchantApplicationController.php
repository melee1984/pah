<?php

namespace App\Http\Controllers\Admin;

use App\Agent;
use App\Http\Controllers\Controller;
use App\Mail\MerchantApplicationStatusMail;
use App\Partners;
use App\Services\AdminCityScope;
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
    public function __construct(private readonly AdminCityScope $cityScope) {}

    public function index(Request $request): View
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'agent_id' => ['nullable', 'integer', Rule::exists('agents', 'id')],
            'status' => ['nullable', Rule::in([
                Partners::STATUS_PENDING,
                Partners::STATUS_DECLINED,
            ])],
        ]);

        $search = trim($validated['search'] ?? '');
        $status = $validated['status'] ?? '';
        $agentId = isset($validated['agent_id']) ? (int) $validated['agent_id'] : null;
        $applicationQuery = Partners::query()
            ->where('application_source', Partners::APPLICATION_SOURCE_REGISTRATION)
            ->where('recruitment_status', '!=', Partners::STATUS_APPROVED)
            ->with('agent:id,name,referral_code');
        $this->cityScope->partners($applicationQuery);
        $applications = $applicationQuery
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('restaurant_name', 'like', "%{$search}%")
                        ->orWhere('registered_business_name', 'like', "%{$search}%")
                        ->orWhere('owner_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('mobile', 'like', "%{$search}%");
                });
            })
            ->when($status !== '', fn ($query) => $query->where('recruitment_status', $status))
            ->when($agentId !== null, fn ($query) => $query->where('agent_id', $agentId))
            ->latest('recruitment_submitted_at')
            ->paginate(25)
            ->withQueryString();

        $metricApplications = fn () => $this->cityScope->partners(
            Partners::query()->where('application_source', Partners::APPLICATION_SOURCE_REGISTRATION)
        );
        $metrics = [
            'total' => $metricApplications()->count(),
            'pending' => $metricApplications()->where('recruitment_status', Partners::STATUS_PENDING)->count(),
            'approved' => $metricApplications()->where('recruitment_status', Partners::STATUS_APPROVED)->count(),
            'declined' => $metricApplications()->where('recruitment_status', Partners::STATUS_DECLINED)->count(),
        ];
        $agents = Agent::query()
            ->withCount([
                'referredApplications as active_referred_applications_count' => fn ($query) => $query
                    ->where('recruitment_status', '!=', Partners::STATUS_APPROVED),
            ])
            ->orderBy('name')
            ->get(['id', 'name', 'referral_code']);

        return view('dashboard.pages.merchant-applications.index', compact('applications', 'metrics', 'search', 'status', 'agents', 'agentId'));
    }

    public function show(Partners $application): View
    {
        abort_unless($application->application_source === Partners::APPLICATION_SOURCE_REGISTRATION, 404);
        $application->load(['agent:id,name,email,mobile,referral_code', 'enrollmentDocuments']);
        $documents = $application->enrollmentDocuments->keyBy('document_type');

        return view('dashboard.pages.merchant-applications.show', compact('application', 'documents'));
    }

    public function approve(Request $request, Partners $application): RedirectResponse
    {
        return $this->review($request, $application, Partners::STATUS_APPROVED);
    }

    public function decline(Request $request, Partners $application): RedirectResponse
    {
        return $this->review($request, $application, Partners::STATUS_DECLINED);
    }

    private function review(Request $request, Partners $application, string $decision): RedirectResponse
    {
        abort_unless($application->application_source === Partners::APPLICATION_SOURCE_REGISTRATION, 404);

        $validated = $request->validate([
            'message' => [$decision === Partners::STATUS_DECLINED ? 'required' : 'nullable', 'string', 'max:2000'],
        ]);
        $message = trim($validated['message'] ?? '') ?: null;

        if (! $application->isPending()) {
            return back()->withErrors(['review' => 'This merchant application has already been reviewed.']);
        }

        try {
            DB::transaction(function () use ($application, $decision, $message, $request) {
                $pendingApplication = Partners::query()
                    ->where('application_source', Partners::APPLICATION_SOURCE_REGISTRATION)
                    ->lockForUpdate()
                    ->findOrFail($application->id);

                if (! $pendingApplication->isPending()) {
                    throw new LogicException('This merchant application has already been reviewed.');
                }

                $pendingApplication->update([
                    'recruitment_status' => $decision,
                    'recruitment_review_message' => $message,
                    'recruitment_reviewed_by' => $request->user()?->getKey(),
                    'recruitment_reviewed_at' => now(),
                    ...($decision === Partners::STATUS_APPROVED && blank($pendingApplication->application_status)
                        ? ['application_status' => 'pending_review']
                        : []),
                ]);

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

        $success = $application->restaurant_name.' was '.$decision.' and the applicant was notified by email.';

        if ($decision === Partners::STATUS_APPROVED) {
            return redirect()->route('dashboard.merchant-applications.index')->with('success', $success);
        }

        return back()->with('success', $success);
    }
}
