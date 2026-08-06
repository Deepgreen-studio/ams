<?php

namespace App\Domains\Audit\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SystemEvent extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'event',
        'module',
        'level',
        'payload',
    ];

    protected static function booted(): void
    {
        static::creating(function (SystemEvent $event): void {
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
            'payload' => 'array',
        ];
    }
}
