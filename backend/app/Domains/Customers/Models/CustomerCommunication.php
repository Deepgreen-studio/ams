<?php

namespace App\Domains\Customers\Models;

use App\Domains\Customers\Enums\CustomerCommunicationDirection;
use App\Domains\Customers\Enums\CustomerCommunicationStatus;
use App\Domains\Customers\Enums\CustomerCommunicationType;
use App\Models\User;
use Database\Factories\CustomerCommunicationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class CustomerCommunication extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'customer_id',
        'type',
        'direction',
        'subject',
        'body',
        'status',
        'channel_reference',
        'participants',
        'duration_seconds',
        'occurred_at',
        'created_by',
        'updated_by',
    ];

    protected static function booted(): void
    {
        static::creating(function (CustomerCommunication $communication): void {
            if (blank($communication->uuid)) {
                $communication->uuid = (string) Str::uuid();
            }
        });
    }

    protected static function newFactory(): CustomerCommunicationFactory
    {
        return CustomerCommunicationFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => CustomerCommunicationType::class,
            'direction' => CustomerCommunicationDirection::class,
            'status' => CustomerCommunicationStatus::class,
            'participants' => 'array',
            'duration_seconds' => 'integer',
            'occurred_at' => 'datetime',
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

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
