<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('security_analytics_snapshots', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->date('snapshot_date');
            $table->unsignedInteger('logins_success')->default(0);
            $table->unsignedInteger('logins_failed')->default(0);
            $table->unsignedInteger('permission_changes')->default(0);
            $table->unsignedInteger('role_changes')->default(0);
            $table->unsignedInteger('data_exports')->default(0);
            $table->unsignedInteger('data_deletions')->default(0);
            $table->unsignedInteger('gdpr_requests')->default(0);
            $table->unsignedInteger('security_events')->default(0);
            $table->unsignedInteger('api_key_uses')->default(0);
            $table->unsignedInteger('api_errors')->default(0);
            $table->unsignedTinyInteger('risk_score')->default(0);
            $table->json('metrics')->nullable();
            $table->timestamp('computed_at')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'snapshot_date'], 'sec_analytics_company_date_uq');
            $table->index(['snapshot_date', 'risk_score'], 'sec_analytics_date_risk_idx');
            $table->index(['snapshot_date', 'logins_failed'], 'sec_analytics_date_failed_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('security_analytics_snapshots');
    }
};
