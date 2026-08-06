<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_breaches', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('breach_number', 64);
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('breach_type', 64)->index();
            $table->string('status', 32)->default('reported')->index();
            $table->string('severity', 32)->default('medium')->index();
            $table->timestamp('discovered_at')->nullable()->index();
            $table->timestamp('occurred_at')->nullable();
            $table->unsignedInteger('affected_user_count')->default(0);
            $table->json('affected_users')->nullable();
            $table->json('affected_data_categories')->nullable();
            $table->boolean('personal_data_involved')->default(true);
            $table->boolean('special_category_data')->default(false);
            $table->unsignedTinyInteger('risk_likelihood')->nullable();
            $table->unsignedTinyInteger('risk_impact')->nullable();
            $table->unsignedTinyInteger('risk_score')->nullable()->index();
            $table->string('risk_level', 32)->nullable()->index();
            $table->text('risk_assessment_notes')->nullable();
            $table->timestamp('risk_assessed_at')->nullable();
            $table->foreignId('risk_assessed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('impact_analysis')->nullable();
            $table->text('containment_summary')->nullable();
            $table->timestamp('contained_at')->nullable();
            $table->text('recovery_summary')->nullable();
            $table->timestamp('recovered_at')->nullable();
            $table->text('root_cause')->nullable();
            $table->timestamp('root_cause_at')->nullable();
            $table->text('lessons_learned')->nullable();
            $table->timestamp('lessons_learned_at')->nullable();
            $table->boolean('regulator_notification_required')->default(false);
            $table->timestamp('regulator_deadline_at')->nullable()->index();
            $table->timestamp('regulator_notified_at')->nullable();
            $table->string('regulator_reference', 128)->nullable();
            $table->boolean('customer_notification_required')->default(false);
            $table->timestamp('customer_notified_at')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('closed_at')->nullable()->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['company_id', 'breach_number']);
            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'severity']);
            $table->index(['company_id', 'breach_type']);
            $table->index(['company_id', 'discovered_at']);
            $table->index('assigned_to');
            $table->index('created_by');
            $table->index('updated_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_breaches');
    }
};
