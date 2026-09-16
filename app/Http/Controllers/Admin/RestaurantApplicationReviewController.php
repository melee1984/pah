<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Partners;
use App\RestaurantEnrollmentDocument;
use App\Services\RestaurantApplicationNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RestaurantApplicationReviewController extends Controller
{
    public function show(Partners $restaurant): View
    {
        abort_unless($restaurant->agent_id, 404);
        $restaurant->load(['enrollmentDocuments', 'agent', 'enrollmentContact']);
        $documents = $restaurant->enrollmentDocuments->keyBy('document_type');
        $approvalBlockers = $restaurant->applicationApprovalBlockers();

        return view('dashboard.pages.merchant.application', compact('restaurant', 'documents', 'approvalBlockers'));
    }

    public function document(Request $request, Partners $restaurant, RestaurantEnrollmentDocument $document): RedirectResponse
    {
        abort_unless($restaurant->agent_id && $document->partner_id === $restaurant->id, 404);
        $validated = $request->validate([
            'status' => ['required', Rule::in(['approved', 'rejected'])],
            'remarks' => ['required_if:status,rejected', 'nullable', 'string', 'max:2000'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:today'],
        ]);

        DB::transaction(function () use ($restaurant, $document, $validated) {
            $document->update([
                'status' => $validated['status'],
                'remarks' => trim($validated['remarks'] ?? '') ?: null,
                'expires_at' => $validated['expires_at'] ?? null,
                'reviewed_at' => now(),
            ]);

            if ($restaurant->application_status === 'approved' && $validated['status'] !== 'approved') {
                $restaurant->forceFill(['application_status' => 'pending_review', 'active' => false, 'verified_at' => null])->save();
            }
        });

        app(RestaurantApplicationNotifier::class)->send($restaurant,
            RestaurantEnrollmentDocument::LABELS[$document->document_type].' status: '.str_replace('_', ' ', $validated['status']).'.',
            $validated['remarks'] ?? null);

        return back()->with('success', 'Document review saved and notifications sent.');
    }

    public function application(Request $request, Partners $restaurant): RedirectResponse
    {
        abort_unless($restaurant->agent_id, 404);
        $validated = $request->validate([
            'decision' => ['required', Rule::in(['approved', 'declined'])],
            'remarks' => ['required_if:decision,declined', 'nullable', 'string', 'max:2000'],
        ]);

        if ($validated['decision'] === 'approved' && ! $restaurant->applicationReadyForApproval()) {
            return back()->withErrors(['decision' => 'Complete the business information and approve every required document before approving the restaurant application.']);
        }

        $approved = $validated['decision'] === 'approved';
        $restaurant->forceFill([
            'application_status' => $validated['decision'],
            'application_remarks' => trim($validated['remarks'] ?? '') ?: null,
            'active' => $approved,
            'verified_at' => $approved ? now() : null,
            'verified_by' => $request->user()->id,
        ])->save();

        app(RestaurantApplicationNotifier::class)->send($restaurant,
            'Application '.($approved ? 'approved' : 'declined').'.', $validated['remarks'] ?? null);

        return back()->with('success', 'Application decision saved and notifications sent.');
    }
}
