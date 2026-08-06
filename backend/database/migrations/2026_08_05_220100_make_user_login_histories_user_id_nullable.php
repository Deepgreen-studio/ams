<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Allow failed logins for unknown emails (null user_id).
 * Fresh installs already use nullable user_id from the create migration;
 * this alters existing MySQL databases that still require user_id.
 */
return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            return;
        }

        $column = collect(DB::select("SHOW COLUMNS FROM user_login_histories LIKE 'user_id'"))->first();
        if (! $column || strtoupper((string) ($column->Null ?? '')) === 'YES') {
            return;
        }

        Schema::table('user_login_histories', function ($table): void {
            $table->dropForeign(['user_id']);
        });

        DB::statement('ALTER TABLE user_login_histories MODIFY user_id BIGINT UNSIGNED NULL');

        Schema::table('user_login_histories', function ($table): void {
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        // Intentionally left blank — do not force NOT NULL on historical failed-login rows.
    }
};
