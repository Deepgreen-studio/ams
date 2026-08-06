<?php

namespace App\Domains\Customers\Models;

use App\Domains\Customers\Enums\CustomerNoteStatus;
use App\Domains\Customers\Enums\CustomerNoteType;
use App\Models\User;
use Database\Factories\CustomerNoteFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class CustomerNote extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'customer_id',
        'note_type',
        'title',
        'body',
        'is_pinned',
        'status',
        'occurred_at',
        'created_by',
        'updated_by',
    ];

    protected static function booted(): void
    {
        static::creating(function (CustomerNote $note): void {
            if (blank($note->uuid)) {
                $note->uuid = (string) Str::uuid();
            }
        });
    }

    protected static function newFactory(): CustomerNoteFactory
    {
        return CustomerNoteFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'note_type' => CustomerNoteType::class,
            'status' => CustomerNoteStatus::class,
            'is_pinned' => 'boolean',
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
