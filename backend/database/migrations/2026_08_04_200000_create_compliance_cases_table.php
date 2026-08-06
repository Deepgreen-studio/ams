<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('compliance_cases', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('case_number', 64);
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('case_type', 64)->index();
            $table->string('priority', 32)->default('medium')->index();
            $table->string('status', 32)->default('open')->index();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->date('due_date')->nullable()->index();
            $table->timestamp('completed_at')->nullable()->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['company_id', 'case_number']);
            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'case_type']);
            $table->index(['company_id', 'priority']);
            $table->index(['company_id', 'due_date']);
            $table->index('assigned_to');
            $table->index('created_by');
            $table->index('updated_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compliance_cases');
    }
};
