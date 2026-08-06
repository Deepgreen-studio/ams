<?php

namespace App\Domains\Roles\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class PermissionGroup extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'name',
        'slug',
        'module',
        'description',
        'sort_order',
        'is_active',
    ];

    protected static function booted(): void
    {
        static::creating(function (PermissionGroup $group): void {
            if (blank($group->uuid)) {
                $group->uuid = (string) Str::uuid();
            }

            if (blank($group->slug)) {
                $group->slug = Str::slug((string) $group->name);
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function permissions(): HasMany
    {
        return $this->hasMany(Permission::class, 'permission_group_id');
    }
}
