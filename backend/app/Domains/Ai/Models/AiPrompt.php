<?php

namespace App\Domains\Ai\Models;

use App\Domains\Ai\Enums\AiFeature;
use App\Domains\Ai\Enums\AiPromptStatus;
use App\Domains\Companies\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class AiPrompt extends Model
{
    use LogsActivity;
    use SoftDeletes;

    protected $table = 'ai_prompts';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'uuid', 'company_id', 'key', 'name', 'feature', 'system_prompt', 'user_template',
        'model_override', 'temperature', 'max_tokens', 'output_schema', 'version',
        'status', 'metadata', 'created_by', 'updated_by',
    ];

    protected static function booted(): void
    {
        static::creating(function (AiPrompt $prompt): void {
            if (blank($prompt->uuid)) {
                $prompt->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'feature' => AiFeature::class,
            'status' => AiPromptStatus::class,
            'temperature' => 'float',
            'output_schema' => 'array',
            'metadata' => 'array',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->dontSubmitEmptyLogs();
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
