<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_analytics_snapshots', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->date('snapshot_date');
            $table->unsignedInteger('customers_total')->default(0);
            $table->unsignedInteger('customers_new')->default(0);
            $table->unsignedInteger('customers_active')->default(0);
            $table->unsignedInteger('subscriptions_total')->default(0);
            $table->unsignedInteger('subscriptions_active')->default(0);
            $table->unsignedInteger('subscriptions_new')->default(0);
            $table->decimal('mrr', 14, 2)->default(0);
            $table->decimal('revenue_period', 14, 2)->default(0);
            $table->unsignedBigInteger('application_sessions')->default(0);
            $table->unsignedInteger('application_active_users')->default(0);
            $table->unsignedInteger('feature_usage_count')->default(0);
            $table->unsignedInteger('support_tickets_open')->default(0);
            $table->unsignedInteger('support_tickets_new')->default(0);
            $table->unsignedTinyInteger('avg_health_score')->default(0);
            $table->unsignedInteger('at_risk_customers')->default(0);
            $table->json('metrics')->nullable();
            $table->timestamp('computed_at')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'snapshot_date'], 'biz_analytics_company_date_uq');
            $table->index(['snapshot_date', 'mrr'], 'biz_analytics_date_mrr_idx');
            $table->index(['snapshot_date', 'customers_active'], 'biz_analytics_date_active_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_analytics_snapshots');
    }
};
