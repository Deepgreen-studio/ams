<?php

namespace App\Domains\Applications\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ApplicationConfigurationHistory extends Model
{
    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'configuration_id',
        'version',
        'payload',
        'status',
        'change_summary',
        'created_by',
        'created_at',
    ];

    protected static function booted(): void
    {
        static::creating(function (ApplicationConfigurationHistory $history): void {
            if (blank($history->uuid)) {
                $history->uuid = (string) Str::uuid();
            }
            if (blank($history->created_at)) {
                $history->created_at = now();
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payload' => 'encrypted:array',
            'version' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function configuration(): BelongsTo
    {
        return $this->belongsTo(ApplicationConfiguration::class, 'configuration_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
