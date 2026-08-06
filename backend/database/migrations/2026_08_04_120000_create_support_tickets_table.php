<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_tickets', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('application_id')->nullable()->constrained('applications')->nullOnDelete();
            $table->string('ticket_number', 40)->unique();
            $table->string('subject');
            $table->longText('description');
            $table->string('priority', 32)->default('medium')->index();
            $table->string('category', 48)->index();
            $table->string('status', 32)->default('open')->index();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('source', 32)->default('portal')->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('closed_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'priority']);
            $table->index(['company_id', 'category']);
            $table->index(['company_id', 'customer_id']);
            $table->index(['company_id', 'application_id']);
            $table->index(['assigned_to', 'status']);
            $table->index(['status', 'created_at']);
            $table->index('created_by');
            $table->index('updated_by');
            $table->index('closed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_tickets');
    }
};
