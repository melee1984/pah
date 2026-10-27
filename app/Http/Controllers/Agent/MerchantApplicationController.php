<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Partners;
use App\RestaurantEnrollmentDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class MerchantApplicationController extends Controller
{
    public function edit(Request $request, Partners $application): View
    {
        $this->authorizeApplication($request, $application);
        $documents = $application->enrollmentDocuments()->get()->keyBy('document_type');

        return view('agent.restaurants.edit-merchant-application', [
            'application' => $application,
            'documents' => $documents,
            'services' => Partners::SERVICES,
        ]);
    }

    public function update(Request $request, Partners $application): RedirectResponse
    {
        $this->authorizeApplication($request, $application);

        $validated = $request->validate([
            'restaurant_name' => ['required', 'string', 'max:255'],
            'registered_business_name' => ['required', 'string', 'max:255'],
            'owner_name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('partners', 'email')->ignore($application->getKey()),
            ],
            'mobile' => ['required', 'string', 'max:30'],
            'telephone' => ['nullable', 'string', 'max:30'],
            'address' => ['required', 'string', 'max:500'],
            'city' => ['required', 'string', 'max:120'],
            'business_structure' => ['required', Rule::in(['sole_proprietorship', 'corporation', 'partnership', 'cooperative'])],
            'enrolling_as' => [
                'required',
                Rule::in(['owner', 'authorized_representative']),
                function ($attribute, $value, $fail) use ($request) {
                    if ($value === 'owner' && $request->input('business_structure') !== 'sole_proprietorship') {
                        $fail('For this business structure, select authorized representative.');
                    }
                },
            ],
            'tin' => ['required', 'string', 'max:30'],
            'business_registration_number' => ['nullable', 'string', 'max:100'],
            'payout_account_name' => ['required', 'string', 'max:255'],
            'cuisine' => ['nullable', 'string', 'max:120'],
            'branch_count' => ['required', 'integer', 'min:1', 'max:500'],
            'services' => ['required', 'array', 'min:1'],
            'services.*' => ['required', Rule::in(array_keys(Partners::SERVICES))],
            'website' => ['nullable', 'url:http,https', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            ...collect([...RestaurantEnrollmentDocument::REQUIRED_TYPES, 'authorization_document'])
                ->mapWithKeys(fn ($type) => [$type => [
                    'nullable',
                    'file',
                    'mimes:pdf,jpg,jpeg,png',
                    'max:10240',
                ]])->all(),
        ], [
            'services.required' => 'Choose at least one service the merchant wants to offer.',
            'services.min' => 'Choose at least one service the merchant wants to offer.',
        ]);

        $agentId = $request->user('agent')->getKey();
        $documentTypes = [...RestaurantEnrollmentDocument::REQUIRED_TYPES, 'authorization_document'];
        $applicationAttributes = collect($validated)->except($documentTypes)->all();
        $storedPaths = [];
        $replacedPaths = [];

        try {
            DB::transaction(function () use ($application, $applicationAttributes, $validated, $agentId, $documentTypes, &$storedPaths, &$replacedPaths) {
                $ownedApplication = Partners::query()->lockForUpdate()->findOrFail($application->getKey());

                abort_unless(
                    $ownedApplication->agent_id === $agentId
                    && $ownedApplication->application_source === Partners::APPLICATION_SOURCE_REGISTRATION,
                    404
                );

                $ownedApplication->update([
                    ...$applicationAttributes,
                    'telephone' => $applicationAttributes['telephone'] ?? null,
                    'business_registration_number' => $applicationAttributes['business_registration_number'] ?? null,
                    'cuisine' => $applicationAttributes['cuisine'] ?? null,
                    'website' => $applicationAttributes['website'] ?? null,
                    'description' => $applicationAttributes['description'] ?? null,
                    'search_string' => Str::lower(trim($applicationAttributes['restaurant_name'].' '.$applicationAttributes['city'])),
                    'recruitment_status' => Partners::STATUS_PENDING,
                    'recruitment_review_message' => null,
                    'recruitment_reviewed_by' => null,
                    'recruitment_reviewed_at' => null,
                    'recruitment_submitted_at' => now(),
                ]);

                $ownedApplication->enrollmentDocuments()
                    ->where('status', 'approved')
                    ->update(['status' => 'pending_verification', 'reviewed_at' => null]);

                foreach ($documentTypes as $type) {
                    $file = $validated[$type] ?? null;

                    if (! $file) {
                        continue;
                    }

                    if ($type === 'authorization_document' && $applicationAttributes['enrolling_as'] !== 'authorized_representative') {
                        continue;
                    }

                    $path = $file->store('restaurant-enrollment/'.$ownedApplication->getKey(), 'local');

                    if (! $path) {
                        throw new RuntimeException('The restaurant enrollment document could not be stored.');
                    }

                    $storedPaths[] = $path;
                    $existing = $ownedApplication->enrollmentDocuments()->where('document_type', $type)->first();

                    if ($existing?->file_path) {
                        $replacedPaths[] = $existing->file_path;
                    }

                    $ownedApplication->enrollmentDocuments()->updateOrCreate(
                        ['document_type' => $type],
                        [
                            'file_path' => $path,
                            'original_name' => $file->getClientOriginalName(),
                            'status' => 'pending_verification',
                            'remarks' => null,
                            'expires_at' => null,
                            'reviewed_at' => null,
                        ]
                    );
                }
            });
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($storedPaths);

            throw $exception;
        }

        Storage::disk('local')->delete($replacedPaths);

        return redirect()
            ->route('agent.merchant-applications.edit', $application->fresh())
            ->with('success', 'The merchant application was updated and submitted as Pending Approval.');
    }

    private function authorizeApplication(Request $request, Partners $application): void
    {
        abort_unless(
            $application->agent_id === $request->user('agent')->getKey()
            && $application->application_source === Partners::APPLICATION_SOURCE_REGISTRATION,
            404
        );
    }
}
