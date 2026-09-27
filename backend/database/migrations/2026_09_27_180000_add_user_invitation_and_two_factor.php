<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (! Schema::hasColumn('users', 'invitation_status')) {
                $table->string('invitation_status', 32)->default('accepted')->after('status');
            }

            if (! Schema::hasColumn('users', 'invitation_sent_at')) {
                $table->timestamp('invitation_sent_at')->nullable()->after('invitation_status');
            }

            if (! Schema::hasColumn('users', 'invitation_expires_at')) {
                $table->timestamp('invitation_expires_at')->nullable()->after('invitation_sent_at');
            }

            if (! Schema::hasColumn('users', 'two_factor_secret')) {
                $table->text('two_factor_secret')->nullable()->after('invitation_expires_at');
            }

            if (! Schema::hasColumn('users', 'two_factor_recovery_codes')) {
                $table->text('two_factor_recovery_codes')->nullable()->after('two_factor_secret');
            }

            if (! Schema::hasColumn('users', 'two_factor_confirmed_at')) {
                $table->timestamp('two_factor_confirmed_at')->nullable()->after('two_factor_recovery_codes');
            }
        });

        $indexNames = collect(Schema::getIndexes('users'))->pluck('name');

        if (! $indexNames->contains('users_invitation_status_index')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->index('invitation_status');
            });
        }

        DB::table('users')->where('status', 'pending')->update([
            'status' => 'inactive',
            'is_active' => false,
            'invitation_status' => 'pending',
        ]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex(['invitation_status']);
            $table->dropColumn([
                'invitation_status',
                'invitation_sent_at',
                'invitation_expires_at',
                'two_factor_secret',
                'two_factor_recovery_codes',
                'two_factor_confirmed_at',
            ]);
        });
    }
};
