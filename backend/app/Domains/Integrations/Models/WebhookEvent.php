<?php

namespace App\Domains\Integrations\Models;

use App\Domains\Companies\Models\Company;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class WebhookEvent extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'name',
        'label',
        'description',
        'source_module',
        'payload_schema',
        'is_system',
        'status',
    ];

    protected static function booted(): void
    {
        static::creating(function (WebhookEvent $event): void {
            if (blank($event->uuid)) {
                $event->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payload_schema' => 'array',
            'is_system' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function logs(): HasMany
    {
        return $this->hasMany(WebhookLog::class);
    }
}
