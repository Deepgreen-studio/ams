<?php

namespace App\Domains\Analytics\Models;

use App\Domains\Analytics\Enums\AnalyticsDashboardShareType;
use App\Domains\Analytics\Models\AnalyticsDashboard;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class AnalyticsDashboardShare extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'analytics_dashboard_id',
        'share_type',
        'share_id',
        'can_edit',
        'shared_by',
    ];

    protected static function booted(): void
    {
        static::creating(function (AnalyticsDashboardShare $share): void {
            if (blank($share->uuid)) {
                $share->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'share_type' => AnalyticsDashboardShareType::class,
            'can_edit' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function dashboard(): BelongsTo
    {
        return $this->belongsTo(AnalyticsDashboard::class, 'analytics_dashboard_id');
    }

    public function sharer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'shared_by');
    }
}
