<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('executive_analytics_snapshots', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->date('snapshot_date');
            $table->decimal('mrr', 14, 2)->default(0);
            $table->decimal('revenue_period', 14, 2)->default(0);
            $table->unsignedInteger('customers_total')->default(0);
            $table->unsignedInteger('customers_active')->default(0);
            $table->unsignedInteger('customers_new')->default(0);
            $table->unsignedInteger('applications_total')->default(0);
            $table->unsignedInteger('subscriptions_active')->default(0);
            $table->unsignedInteger('support_tickets_open')->default(0);
            $table->unsignedInteger('support_sla_on_track')->default(0);
            $table->unsignedInteger('support_sla_breached')->default(0);
            $table->unsignedInteger('compliance_cases_open')->default(0);
            $table->unsignedTinyInteger('compliance_risk_score')->default(0);
            $table->unsignedTinyInteger('system_health_score')->default(0);
            $table->decimal('system_uptime_percent', 5, 2)->default(0);
            $table->unsignedTinyInteger('security_risk_score')->default(0);
            $table->unsignedTinyInteger('business_score')->default(0);
            $table->json('scorecards')->nullable();
            $table->json('metrics')->nullable();
            $table->timestamp('computed_at')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'snapshot_date'], 'exec_analytics_company_date_uq');
            $table->index(['snapshot_date', 'mrr'], 'exec_analytics_date_mrr_idx');
            $table->index(['snapshot_date', 'business_score'], 'exec_analytics_date_score_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('executive_analytics_snapshots');
    }
};
