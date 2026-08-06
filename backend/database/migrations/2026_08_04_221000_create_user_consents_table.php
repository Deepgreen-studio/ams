<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_consents', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('consent_type_id')->constrained('consent_types')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->string('subject_email')->nullable()->index();
            $table->string('subject_name')->nullable();
            $table->string('consent_version', 32);
            $table->string('status', 32)->default('pending')->index();
            $table->boolean('granted')->default(false)->index();
            $table->timestamp('consented_at')->nullable()->index();
            $table->timestamp('withdrawn_at')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->string('device')->nullable();
            $table->text('user_agent')->nullable();
            $table->string('source', 64)->default('admin')->index();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'consent_type_id']);
            $table->index(['company_id', 'granted']);
            $table->index(['user_id', 'consent_type_id']);
            $table->index(['customer_id', 'consent_type_id']);
            $table->index('created_by');
            $table->index('updated_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_consents');
    }
};
