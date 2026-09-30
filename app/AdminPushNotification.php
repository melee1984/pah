<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AdminPushNotification extends Model
{
    public const CATEGORY_PROMOTION = 'promotion';

    public const CATEGORY_ALERT = 'alert';

    public const CATEGORY_ANNOUNCEMENT = 'announcement';

    public const CATEGORY_GENERAL = 'general';

    public const AUDIENCE_ALL_USERS = 'all_users';

    public const AUDIENCE_SELECTED_USERS = 'selected_users';

    public const AUDIENCE_ALL_RIDERS = 'all_riders';

    public const AUDIENCE_SELECTED_RIDERS = 'selected_riders';

    public const AUDIENCE_MERCHANTS = 'merchants';

    public const STATUS_DRAFT = 'draft';

    public const STATUS_SCHEDULED = 'scheduled';

    public const STATUS_QUEUED = 'queued';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_SENT = 'sent';

    public const STATUS_PARTIAL = 'partial';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'reference',
        'created_by',
        'category',
        'audience_type',
        'target_ids',
        'title',
        'message',
        'image_path',
        'deep_link',
        'status',
        'scheduled_at',
        'confirmed_at',
        'sent_at',
        'recipient_count',
        'success_count',
        'failed_count',
        'last_error',
    ];

    protected $casts = [
        'target_ids' => 'array',
        'scheduled_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    public static function categories(): array
    {
        return [
            self::CATEGORY_PROMOTION => 'Promotion',
            self::CATEGORY_ALERT => 'Alert',
            self::CATEGORY_ANNOUNCEMENT => 'Announcement',
            self::CATEGORY_GENERAL => 'General update',
        ];
    }

    public static function audiences(): array
    {
        return [
            self::AUDIENCE_ALL_USERS => 'All users',
            self::AUDIENCE_SELECTED_USERS => 'Selected users',
            self::AUDIENCE_ALL_RIDERS => 'All riders',
            self::AUDIENCE_SELECTED_RIDERS => 'Selected riders',
            self::AUDIENCE_MERCHANTS => 'Merchants',
        ];
    }

    public static function statuses(): array
    {
        return [
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_SCHEDULED => 'Scheduled',
            self::STATUS_QUEUED => 'Queued',
            self::STATUS_PROCESSING => 'Sending',
            self::STATUS_SENT => 'Sent',
            self::STATUS_PARTIAL => 'Partially sent',
            self::STATUS_FAILED => 'Failed',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'reference';
    }

    public function getCategoryLabelAttribute(): string
    {
        return self::categories()[$this->category] ?? ucfirst($this->category);
    }

    public function getAudienceLabelAttribute(): string
    {
        return self::audiences()[$this->audience_type] ?? ucfirst(str_replace('_', ' ', $this->audience_type));
    }

    public function getStatusLabelAttribute(): string
    {
        return self::statuses()[$this->status] ?? ucfirst($this->status);
    }

    public function getStatusCssClassAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_SENT => 'admin-status-active',
            self::STATUS_FAILED => 'admin-status-inactive',
            self::STATUS_PARTIAL => 'push-status-partial',
            default => 'admin-status-pending',
        };
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image_path ? asset($this->image_path) : null;
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(AdminPushNotificationDelivery::class, 'notification_id');
    }
}
