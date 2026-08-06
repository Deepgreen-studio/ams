<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('risk_register', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('dpia_assessment_id')->nullable()->constrained('dpia_assessments')->nullOnDelete();
            $table->string('risk_number', 64);
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('category', 64)->default('privacy')->index();
            $table->string('status', 32)->default('identified')->index();
            $table->unsignedTinyInteger('likelihood')->nullable();
            $table->unsignedTinyInteger('impact')->nullable();
            $table->unsignedTinyInteger('risk_score')->nullable()->index();
            $table->string('risk_level', 32)->nullable()->index();
            $table->unsignedTinyInteger('residual_likelihood')->nullable();
            $table->unsignedTinyInteger('residual_impact')->nullable();
            $table->unsignedTinyInteger('residual_score')->nullable();
            $table->string('residual_level', 32)->nullable();
            $table->text('mitigation_plan')->nullable();
            $table->date('review_due_at')->nullable()->index();
            $table->timestamp('identified_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['company_id', 'risk_number']);
            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'category']);
            $table->index(['company_id', 'risk_level']);
            $table->index(['dpia_assessment_id', 'status']);
            $table->index('owner_id');
            $table->index('created_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('risk_register');
    }
};
