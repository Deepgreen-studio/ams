<?php

namespace App\Domains\Roles\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Models\Role as SpatieRole;
use Spatie\Permission\PermissionRegistrar;

class Role extends SpatieRole
{
    use LogsActivity;
    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'name',
        'display_name',
        'description',
        'guard_name',
        'is_system',
    ];

    protected static function booted(): void
    {
        static::creating(function (Role $role): void {
            if (blank($role->uuid)) {
                $role->uuid = (string) Str::uuid();
            }

            if (blank($role->display_name)) {
                $role->display_name = Str::of((string) $role->name)
                    ->replace(['-', '_'], ' ')
                    ->title()
                    ->toString();
            }

            if (blank($role->guard_name)) {
                $role->guard_name = config('auth.defaults.guard', 'web');
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'display_name', 'description', 'is_system', 'guard_name'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function users(): BelongsToMany
    {
        $registrar = app(PermissionRegistrar::class);

        return $this->morphedByMany(
            User::class,
            'model',
            config('permission.table_names.model_has_roles'),
            $registrar->pivotRole,
            config('permission.column_names.model_morph_key')
        );
    }

    public function permissionRelations(): HasMany
    {
        return $this->hasMany(config('permission.models.permission'));
    }
}
