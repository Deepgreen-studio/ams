<?php

namespace App\Domains\Customers\Models;

use App\Domains\Customers\Enums\CustomerTaskPriority;
use App\Domains\Customers\Enums\CustomerTaskStatus;
use App\Models\User;
use Database\Factories\CustomerTaskFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class CustomerTask extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'customer_id',
        'title',
        'description',
        'status',
        'priority',
        'due_at',
        'remind_at',
        'completed_at',
        'assigned_to',
        'created_by',
        'updated_by',
    ];

    protected static function booted(): void
    {
        static::creating(function (CustomerTask $task): void {
            if (blank($task->uuid)) {
                $task->uuid = (string) Str::uuid();
            }
        });
    }

    protected static function newFactory(): CustomerTaskFactory
    {
        return CustomerTaskFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => CustomerTaskStatus::class,
            'priority' => CustomerTaskPriority::class,
            'due_at' => 'datetime',
            'remind_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
