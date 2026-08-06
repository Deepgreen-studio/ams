<?php

namespace App\Domains\Compliance\Models;

use App\Domains\Compliance\Enums\BreachActionStatus;
use App\Domains\Compliance\Enums\BreachActionType;
use App\Models\User;
use Database\Factories\BreachActionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class BreachAction extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'data_breach_id',
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
        static::creating(function (BreachAction $action): void {
            if (blank($action->uuid)) {
                $action->uuid = (string) Str::uuid();
            }
        });
    }

    protected static function newFactory(): BreachActionFactory
    {
        return BreachActionFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'action_type' => BreachActionType::class,
            'status' => BreachActionStatus::class,
            'due_at' => 'datetime',
            'completed_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function dataBreach(): BelongsTo
    {
        return $this->belongsTo(DataBreach::class);
    }

    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}
