<?php

namespace App\Domains\Customers\Models;

use App\Domains\Customers\Enums\CustomerDocumentCategory;
use App\Domains\Customers\Enums\CustomerDocumentStatus;
use App\Models\User;
use Database\Factories\CustomerDocumentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CustomerDocument extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'customer_id',
        'document_group_uuid',
        'version',
        'is_current',
        'name',
        'category',
        'status',
        'disk',
        'path',
        'original_filename',
        'extension',
        'mime_type',
        'size',
        'expires_at',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected static function booted(): void
    {
        static::creating(function (CustomerDocument $document): void {
            if (blank($document->uuid)) {
                $document->uuid = (string) Str::uuid();
            }

            if (blank($document->document_group_uuid)) {
                $document->document_group_uuid = (string) Str::uuid();
            }
        });
    }

    protected static function newFactory(): CustomerDocumentFactory
    {
        return CustomerDocumentFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category' => CustomerDocumentCategory::class,
            'status' => CustomerDocumentStatus::class,
            'is_current' => 'boolean',
            'version' => 'integer',
            'size' => 'integer',
            'expires_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function url(): ?string
    {
        if (blank($this->path)) {
            return null;
        }

        return Storage::disk($this->disk)->url($this->path);
    }

    public function isPreviewable(): bool
    {
        $mime = strtolower((string) $this->mime_type);
        $extension = strtolower((string) $this->extension);

        return str_starts_with($mime, 'image/')
            || $mime === 'application/pdf'
            || in_array($extension, ['pdf', 'png', 'jpg', 'jpeg', 'gif', 'webp', 'svg'], true);
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
