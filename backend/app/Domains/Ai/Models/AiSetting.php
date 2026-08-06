<?php

namespace App\Domains\Ai\Models;

use App\Domains\Companies\Models\Company;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class AiSetting extends Model
{
    protected $table = 'ai_settings';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'uuid', 'company_id', 'group', 'key', 'value',
    ];

    protected static function booted(): void
    {
        static::creating(function (AiSetting $setting): void {
            if (blank($setting->uuid)) {
                $setting->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'value' => 'array',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
