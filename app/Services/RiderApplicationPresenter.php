<?php

namespace App\Services;

use App\RiderApplication;
use App\RiderApplicationDocument;

class RiderApplicationPresenter
{
    /** @return array<string, mixed> */
    public function application(RiderApplication $application): array
    {
        $application->loadMissing('documents');

        return [
            'id' => $application->reference,
            'status' => $application->status,
            'email' => $application->email,
            'personal' => [
                'full_name' => $application->full_name,
                'mobile' => $application->mobile,
                'birth_date' => $application->birth_date?->format('Y-m-d'),
                'home_address' => $application->home_address,
            ],
            'emergency_contact' => [
                'name' => $application->emergency_contact_name,
                'relationship' => $application->emergency_contact_relationship,
                'mobile' => $application->emergency_contact_mobile,
            ],
            'vehicle' => [
                'type' => $application->vehicle_type,
                'make_model' => $application->vehicle_make_model,
                'plate_number' => $application->vehicle_plate_number,
                'color' => $application->vehicle_color,
            ],
            'payout_account' => [
                'method' => $application->payout_method,
                'account_name' => $application->payout_account_name,
                'masked_account_number' => $this->maskAccountNumber($application->payout_account_number),
            ],
            'documents' => $application->documents
                ->map(fn (RiderApplicationDocument $document) => $this->document($document))
                ->values(),
            'review_notes' => $application->review_notes,
            'submitted_at' => $application->submitted_at?->toISOString(),
            'updated_at' => $application->updated_at?->toISOString(),
            'progress' => $this->progress($application),
        ];
    }

    /** @return array<string, mixed> */
    public function document(RiderApplicationDocument $document): array
    {
        return [
            'id' => $document->reference,
            'type' => $document->type,
            'original_name' => $document->original_name,
            'mime_type' => $document->mime_type,
            'size_bytes' => $document->size_bytes,
            'uploaded_at' => $document->created_at?->toISOString(),
        ];
    }

    /** @return array<string, string> */
    public function progress(RiderApplication $application): array
    {
        return [
            'application_submitted' => $application->submitted_at ? 'complete' : 'pending',
            'identity_and_documents_review' => match ($application->status) {
                RiderApplication::STATUS_APPROVED => 'complete',
                RiderApplication::STATUS_REVISIONS_REQUIRED,
                RiderApplication::STATUS_REJECTED,
                RiderApplication::STATUS_SUSPENDED,
                RiderApplication::STATUS_EXPIRED_DOCUMENTS => $application->status,
                default => 'pending',
            },
            'rider_account_activated' => 'pending',
        ];
    }

    private function maskAccountNumber(?string $accountNumber): ?string
    {
        if (! $accountNumber) {
            return null;
        }

        $visibleLength = min(4, strlen($accountNumber));

        return str_repeat('•', max(0, strlen($accountNumber) - $visibleLength))
            .substr($accountNumber, -$visibleLength);
    }
}
