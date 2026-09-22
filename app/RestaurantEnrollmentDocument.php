<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RestaurantEnrollmentDocument extends Model
{
    public const LEGACY_LABELS = [
        'business_permit' => 'Mayor’s / Business Permit',
        'bir_registration' => 'BIR Certificate of Registration',
        'sanitary_permit' => 'Sanitary Permit',
        'payout_account' => 'Proof of payout account',
    ];

    public const LABELS = [
        'government_id' => 'Valid government-issued ID',
        'business_registration' => 'Business registration certificate',
        'authorization_document' => 'Authorization document',
    ];

    public const REQUIRED_TYPES = [
        'government_id',
        'business_registration',
    ];

    public static function label(string $type): string
    {
        return self::LABELS[$type]
            ?? self::LEGACY_LABELS[$type]
            ?? str($type)->replace('_', ' ')->title()->toString();
    }

    protected $fillable = ['partner_id', 'document_type', 'file_path', 'original_name', 'status', 'remarks', 'expires_at', 'reviewed_at'];

    protected $hidden = ['file_path'];

    protected function casts(): array
    {
        return ['expires_at' => 'date', 'reviewed_at' => 'datetime'];
    }

    public function currentStatus(): string
    {
        if ($this->status === 'rejected') {
            return 'rejected';
        }

        return $this->expires_at && $this->expires_at->lt(today()) ? 'expired' : $this->status;
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Partners::class, 'partner_id');
    }
}
