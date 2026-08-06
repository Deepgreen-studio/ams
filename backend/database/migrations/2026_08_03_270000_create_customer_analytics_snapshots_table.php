<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_analytics_snapshots', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->date('snapshot_date');
            $table->unsignedInteger('applications_total')->default(0);
            $table->unsignedInteger('applications_active')->default(0);
            $table->unsignedInteger('integrations_total')->default(0);
            $table->unsignedInteger('api_usage_count')->default(0);
            $table->unsignedInteger('login_activity_count')->default(0);
            $table->unsignedInteger('support_tickets_open')->default(0);
            $table->unsignedInteger('support_tickets_total')->default(0);
            $table->string('subscription_status', 50)->nullable();
            $table->boolean('subscription_active')->default(false);
            $table->unsignedTinyInteger('health_score')->default(0);
            $table->unsignedTinyInteger('activity_score')->default(0);
            $table->string('risk_level', 50)->default('low');
            $table->json('metrics')->nullable();
            $table->timestamp('computed_at')->nullable();
            $table->timestamps();

            $table->unique(['customer_id', 'snapshot_date']);
            $table->index(['snapshot_date', 'health_score']);
            $table->index(['customer_id', 'risk_level']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_analytics_snapshots');
    }
};
