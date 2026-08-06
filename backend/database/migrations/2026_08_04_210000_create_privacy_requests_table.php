<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('privacy_requests', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('request_number', 64);
            $table->string('request_type', 64)->index();
            $table->string('requester_name');
            $table->string('requester_email');
            $table->string('requester_phone', 64)->nullable();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->text('description')->nullable();
            $table->string('identity_verification_status', 32)->default('pending')->index();
            $table->timestamp('identity_verified_at')->nullable();
            $table->foreignId('identity_verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('identity_verification_notes')->nullable();
            $table->string('status', 32)->default('submitted')->index();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->date('due_date')->nullable()->index();
            $table->timestamp('completed_at')->nullable()->index();
            $table->string('decision', 32)->nullable()->index();
            $table->text('decision_notes')->nullable();
            $table->timestamp('decision_at')->nullable();
            $table->foreignId('decision_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('export_payload')->nullable();
            $table->string('export_file_path')->nullable();
            $table->timestamp('export_generated_at')->nullable();
            $table->timestamp('deletion_confirmed_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['company_id', 'request_number']);
            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'request_type']);
            $table->index(['company_id', 'due_date']);
            $table->index(['company_id', 'identity_verification_status']);
            $table->index('assigned_to');
            $table->index('requester_email');
            $table->index('created_by');
            $table->index('updated_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('privacy_requests');
    }
};
