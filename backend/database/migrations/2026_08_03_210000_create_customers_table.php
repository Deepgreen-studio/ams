<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('customer_type', 32)->index();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('company_name')->nullable();
            $table->string('email');
            $table->string('phone', 30)->nullable();
            $table->string('website')->nullable();
            $table->string('industry', 120)->nullable();
            $table->string('country', 100)->nullable();
            $table->string('timezone', 64)->default('UTC');
            $table->string('language', 16)->default('en');
            $table->string('status', 32)->default('active')->index();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['company_id', 'email']);
            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'customer_type']);
            $table->index(['company_id', 'last_name']);
            $table->index(['company_id', 'company_name']);
            $table->index(['status', 'created_at']);
            $table->index('created_by');
            $table->index('updated_by');
            $table->index('country');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
