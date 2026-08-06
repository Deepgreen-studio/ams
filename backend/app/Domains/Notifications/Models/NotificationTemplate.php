<?php

namespace App\Domains\Notifications\Models;

use App\Domains\Companies\Models\Company;
use App\Domains\Notifications\Enums\NotificationChannel as NotificationChannelEnum;
use App\Domains\Notifications\Enums\NotificationEventKey;
use App\Domains\Notifications\Enums\NotificationPriority;
use App\Domains\Notifications\Enums\NotificationTemplateStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class NotificationTemplate extends Model
{
    use LogsActivity;
    use SoftDeletes;

    protected $table = 'notification_templates';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'company_id',
        'event_key',
        'channel',
        'locale',
        'name',
        'subject',
        'body',
        'available_variables',
        'is_active',
        'is_system',
        'priority',
        'workflow_status',
        'current_version',
        'change_summary',
        'published_at',
        'created_by',
        'updated_by',
    ];

    protected static function booted(): void
    {
        static::creating(function (NotificationTemplate $template): void {
            if (blank($template->uuid)) {
                $template->uuid = (string) Str::uuid();
            }
            if (blank($template->locale)) {
                $template->locale = 'en';
            }
            if (blank($template->workflow_status)) {
                $template->workflow_status = NotificationTemplateStatus::Draft->value;
            }
            if (blank($template->current_version)) {
                $template->current_version = 1;
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'event_key' => NotificationEventKey::class,
            'channel' => NotificationChannelEnum::class,
            'priority' => NotificationPriority::class,
            'workflow_status' => NotificationTemplateStatus::class,
            'available_variables' => 'array',
            'is_active' => 'boolean',
            'is_system' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(NotificationTemplateVersion::class)->orderByDesc('version');
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(NotificationTemplateApproval::class)->latest('id');
    }
}
