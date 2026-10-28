<?php

namespace App;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class SystemIncident extends Model
{
    public const TYPE_ISSUE = 'issue';

    public const TYPE_MAINTENANCE = 'maintenance';

    public const STATUS_INVESTIGATING = 'investigating';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_RESOLVED = 'resolved';

    protected $fillable = [
        'created_by',
        'title',
        'description',
        'type',
        'status',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'resolved_at' => 'datetime',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', '!=', self::STATUS_RESOLVED);
    }

    public function scopeResolved(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_RESOLVED);
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_IN_PROGRESS => 'Fix in Progress',
            self::STATUS_RESOLVED => 'Resolved',
            default => 'Investigating',
        };
    }

    public function getTypeLabelAttribute(): string
    {
        return $this->type === self::TYPE_MAINTENANCE ? 'Maintenance' : 'Service issue';
    }

    public static function overallStatus(Builder $activeIncidents): string
    {
        if ((clone $activeIncidents)->where('type', self::TYPE_MAINTENANCE)->exists()) {
            return 'maintenance';
        }

        return (clone $activeIncidents)->exists() ? 'issues' : 'operational';
    }
}
