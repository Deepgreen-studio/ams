<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dpia_assessments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('assessment_number', 64);
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('template_code', 64)->default('standard')->index();
            $table->string('status', 32)->default('draft')->index();
            $table->unsignedTinyInteger('wizard_step')->default(1);
            $table->json('wizard_payload')->nullable();
            $table->text('processing_purpose')->nullable();
            $table->json('data_categories')->nullable();
            $table->json('data_subjects')->nullable();
            $table->text('processing_operations')->nullable();
            $table->text('necessity_proportionality')->nullable();
            $table->text('consultation_notes')->nullable();
            $table->unsignedTinyInteger('overall_risk_score')->nullable()->index();
            $table->string('overall_risk_level', 32)->nullable()->index();
            $table->unsignedTinyInteger('residual_risk_score')->nullable();
            $table->string('residual_risk_level', 32)->nullable();
            $table->text('mitigation_summary')->nullable();
            $table->date('review_due_at')->nullable()->index();
            $table->date('next_review_at')->nullable()->index();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('approval_notes')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('rejection_notes')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['company_id', 'assessment_number']);
            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'template_code']);
            $table->index(['company_id', 'review_due_at']);
            $table->index('assigned_to');
            $table->index('created_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dpia_assessments');
    }
};
