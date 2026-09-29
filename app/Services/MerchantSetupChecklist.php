<?php

namespace App\Services;

use App\PartnerPromotion;
use App\Partners;
use Illuminate\Support\Facades\Storage;

class MerchantSetupChecklist
{
    public function for(Partners $merchant): array
    {
        $hasSubmittedPromotion = $merchant->promotions()
            ->where('approval_status', '!=', PartnerPromotion::APPROVAL_REJECTED)
            ->exists();
        $rejectedPromotion = $hasSubmittedPromotion
            ? null
            : $merchant->promotions()
                ->where('approval_status', PartnerPromotion::APPROVAL_REJECTED)
                ->latest()
                ->first(['id', 'approval_status']);

        $tasks = [
            [
                'key' => 'logo',
                'title' => 'Upload your logo',
                'description' => 'Add the square logo customers will use to recognize your store.',
                'icon' => 'fas fa-store',
                'url' => route('merchant.dashboard.settings').'#profile-image',
                'complete' => filled($merchant->img),
            ],
            [
                'key' => 'banner',
                'title' => 'Upload your main banner',
                'description' => 'Add the wide storefront image shown at the top of your store.',
                'icon' => 'fas fa-image',
                'url' => route('merchant.dashboard.settings').'#profile-banner',
                'complete' => filled($merchant->banner),
            ],
            [
                'key' => 'promotion',
                'title' => $rejectedPromotion
                    ? 'Replace your promotional banner'
                    : 'Add a promotional banner',
                'description' => $rejectedPromotion
                    ? 'Your last banner needs changes. Update it and submit it for approval again.'
                    : 'Highlight an offer with a banner and submit it for approval.',
                'icon' => 'fas fa-bullhorn',
                'url' => $rejectedPromotion
                    ? route('merchant.dashboard.promotions.edit', $rejectedPromotion)
                    : route('merchant.dashboard.promotions.create'),
                'complete' => $hasSubmittedPromotion,
            ],
            [
                'key' => 'products',
                'title' => 'Add your first products',
                'description' => 'Build your menu so customers can start placing orders.',
                'icon' => 'fas fa-utensils',
                'url' => route('merchant.dashboard.product', ['setup' => 'add-product']),
                'complete' => $merchant->products()->exists(),
            ],
        ];

        if ($merchant->agent_id) {
            $tasks[] = $this->applicationTask($merchant);
        }

        $completed = collect($tasks)->where('complete', true)->count();
        $total = count($tasks);

        return [
            'tasks' => $tasks,
            'completed' => $completed,
            'total' => $total,
            'percentage' => $total > 0 ? (int) round(($completed / $total) * 100) : 100,
            'is_complete' => $completed === $total,
        ];
    }

    private function applicationTask(Partners $merchant): array
    {
        $requiredFields = [
            'restaurant_name',
            'registered_business_name',
            'tin',
            'payout_account_name',
            'email',
            'mobile',
            'address',
            'city',
            'business_structure',
            'enrolling_as',
        ];

        $detailsComplete = collect($requiredFields)->every(fn ($field) => filled($merchant->$field));

        if ($merchant->business_structure && $merchant->business_structure !== 'sole_proprietorship'
            && $merchant->enrolling_as !== 'authorized_representative') {
            $detailsComplete = false;
        }

        $documents = $merchant->enrollmentDocuments()->get()->keyBy('document_type');
        $documentsComplete = collect($merchant->requiredEnrollmentDocumentTypes())->every(function ($type) use ($documents) {
            $document = $documents->get($type);

            return $document
                && Storage::disk('local')->exists($document->file_path)
                && ! in_array($document->currentStatus(), ['rejected', 'expired'], true);
        });

        $complete = filled($merchant->application_status)
            && $merchant->application_status !== 'declined'
            && $detailsComplete
            && $documentsComplete;

        $description = match (true) {
            $merchant->application_status === 'declined' => 'Review the feedback, update your application, and replace any required documents.',
            ! $detailsComplete && ! $documentsComplete => 'Complete your business information and upload all required documents for review.',
            ! $detailsComplete => 'Complete the missing business information and submit it for review.',
            ! $documentsComplete => 'Upload or replace every required document and submit it for review.',
            blank($merchant->application_status) => 'Review your business information and submit your application for review.',
            $merchant->application_status === 'approved' => 'Your application and required documents are approved.',
            default => 'Your application and documents were submitted and are awaiting review.',
        };

        return [
            'key' => 'application',
            'title' => 'Complete your application and documents',
            'description' => $description,
            'icon' => 'fas fa-file-alt',
            'url' => route('merchant.application.show'),
            'complete' => $complete,
        ];
    }
}
