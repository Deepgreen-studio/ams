<?php

namespace App\Domains\Compliance\Models;

use App\Domains\Compliance\Enums\RiskActionStatus;
use App\Domains\Compliance\Enums\RiskActionType;
use App\Models\User;
use Database\Factories\RiskActionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class RiskAction extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'risk_register_id',
        'action_type',
        'title',
        'description',
        'status',
        'from_status',
        'to_status',
        'performed_by',
        'due_at',
        'completed_at',
        'metadata',
    ];

    protected static function booted(): void
    {
        static::creating(function (RiskAction $action): void {
            if (blank($action->uuid)) {
                $action->uuid = (string) Str::uuid();
            }
        });
    }

    protected static function newFactory(): RiskActionFactory
    {
        return RiskActionFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'action_type' => RiskActionType::class,
            'status' => RiskActionStatus::class,
            'due_at' => 'datetime',
            'completed_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function riskRegister(): BelongsTo
    {
        return $this->belongsTo(RiskRegister::class);
    }

    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}
