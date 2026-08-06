<?php

namespace App\Domains\Roles\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Permission\Models\Permission as SpatiePermission;

class Permission extends SpatiePermission
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'guard_name',
        'display_name',
        'module',
        'description',
        'permission_group_id',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(PermissionGroup::class, 'permission_group_id');
    }
}
