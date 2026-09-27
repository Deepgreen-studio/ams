<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->foreignId('team_id')->nullable()->after('department_id')->constrained('teams')->nullOnDelete();
            $table->foreignId('location_id')->nullable()->after('team_id')->constrained('company_locations')->nullOnDelete();
        });

        foreach (['applications', 'integrations', 'support_tickets'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->boolean('company_status_hold')->default(false)->index();
                $table->string('held_status', 32)->nullable();
            });
        }

        Schema::table('company_user', function (Blueprint $table): void {
            $table->boolean('company_status_hold')->default(false);
            $table->string('held_status', 32)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('company_user', function (Blueprint $table): void {
            $table->dropColumn(['company_status_hold', 'held_status']);
        });

        foreach (['support_tickets', 'integrations', 'applications'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->dropColumn(['company_status_hold', 'held_status']);
            });
        }

        Schema::table('users', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('location_id');
            $table->dropConstrainedForeignId('team_id');
        });
    }
};
