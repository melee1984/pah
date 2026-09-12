<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RiderApplication extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_PENDING = 'pending';

    public const STATUS_REVISIONS_REQUIRED = 'revisions_required';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_SUSPENDED = 'suspended';

    public const STATUS_EXPIRED_DOCUMENTS = 'expired_documents';

    protected $fillable = [
        'reference',
        'access_token_hash',
        'full_name',
        'email',
        'mobile',
        'password',
        'birth_date',
        'home_address',
        'profile_photo_path',
        'emergency_contact_name',
        'emergency_contact_relationship',
        'emergency_contact_mobile',
        'government_id_path',
        'drivers_license_path',
        'vehicle_registration_path',
        'vehicle_type',
        'vehicle_make_model',
        'vehicle_plate_number',
        'vehicle_color',
        'payout_method',
        'payout_account_name',
        'payout_account_number',
        'status',
        'submitted_at',
    ];

    protected $hidden = [
        'access_token_hash',
        'password',
        'payout_account_number',
        'profile_photo_path',
        'government_id_path',
        'drivers_license_path',
        'vehicle_registration_path',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'payout_account_number' => 'encrypted',
            'birth_date' => 'date',
            'submitted_at' => 'datetime',
        ];
    }

    public function documents(): HasMany
    {
        return $this->hasMany(RiderApplicationDocument::class);
    }
}
