<?php

namespace App\Shared\Helpers;

use App\Domains\Audit\Services\ActivityLogService;
use App\Domains\Audit\Services\AuditTrailService;
use App\Domains\Audit\Services\SystemEventService;
use App\Domains\Settings\Models\ConfigurationLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * Cross-domain audit integration helpers for future modules.
 */
class AuditHelper
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public static function configurationChange(
        string $settingKey,
        mixed $oldValue,
        mixed $newValue,
        ?User $actor = null,
        ?string $group = null,
        ?string $ip = null
    ): ConfigurationLog {
        return ConfigurationLog::query()->create([
            'setting_key' => $settingKey,
            'group' => $group,
            'old_value' => is_scalar($oldValue) || $oldValue === null ? $oldValue : json_encode($oldValue),
            'new_value' => is_scalar($newValue) || $newValue === null ? $newValue : json_encode($newValue),
            'changed_by' => $actor?->id,
            'ip_address' => $ip,
            'created_at' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $properties
     */
    public static function activity(
        string $module,
        string $description,
        ?User $actor = null,
        ?Model $subject = null,
        array $properties = [],
        ?string $event = null
    ): void {
        app(ActivityLogService::class)->record($module, $description, $actor, $subject, $properties, $event);
    }

    /**
     * @param  array<string, mixed>|null  $before
     * @param  array<string, mixed>|null  $after
     */
    public static function trail(
        string $module,
        string $action,
        ?User $actor = null,
        ?Model $subject = null,
        ?array $before = null,
        ?array $after = null,
        ?string $reason = null,
        ?Request $request = null,
        ?int $companyId = null
    ): void {
        app(AuditTrailService::class)->record(
            $module,
            $action,
            $actor,
            $subject,
            $before,
            $after,
            $reason,
            $request,
            $companyId
        );
    }

    /**
     * @param  array<string, mixed>|null  $payload
     */
    public static function systemEvent(string $event, string $module = 'system', ?array $payload = null, string $level = 'info'): void
    {
        app(SystemEventService::class)->record($event, $module, $payload, $level);
    }
}
