<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'deleted_by')) {
            return;
        }

        DB::table('users')
            ->whereNotNull('deleted_at')
            ->whereNull('deleted_by')
            ->whereNotNull('updated_by')
            ->update([
                'deleted_by' => DB::raw('updated_by'),
            ]);

        if (! Schema::hasTable('audit_logs')) {
            return;
        }

        $remaining = DB::table('users')
            ->whereNotNull('deleted_at')
            ->whereNull('deleted_by')
            ->pluck('id');

        foreach ($remaining as $userId) {
            $actorId = DB::table('audit_logs')
                ->where('module', 'users')
                ->where('action', 'deleted')
                ->where('subject_type', User::class)
                ->where('subject_id', $userId)
                ->whereNotNull('user_id')
                ->orderByDesc('id')
                ->value('user_id');

            if ($actorId) {
                DB::table('users')
                    ->where('id', $userId)
                    ->update(['deleted_by' => $actorId]);
            }
        }
    }

    public function down(): void
    {
        // Historical backfill is not reversed.
    }
};
