<?php

use App\Domains\Roles\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->foreignId('created_by')
                ->nullable()
                ->after('is_system')
                ->constrained('users')
                ->nullOnDelete();
        });

        $activityTable = config('activitylog.table_name', 'activity_log');

        if (! Schema::hasTable($activityTable)) {
            return;
        }

        $creators = DB::table($activityTable)
            ->select('subject_id', 'causer_id')
            ->where('subject_type', Role::class)
            ->where('description', 'Role created')
            ->whereNotNull('causer_id')
            ->orderBy('id')
            ->get();

        foreach ($creators as $activity) {
            DB::table('roles')
                ->where('id', $activity->subject_id)
                ->whereNull('created_by')
                ->update(['created_by' => $activity->causer_id]);
        }
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by');
        });
    }
};
