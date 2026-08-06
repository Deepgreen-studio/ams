<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_applications', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('application_id')->constrained('applications')->cascadeOnDelete();
            $table->foreignId('application_environment_id')->nullable()->constrained('application_environments')->nullOnDelete();
            $table->foreignId('integration_id')->nullable()->constrained('integrations')->nullOnDelete();
            $table->foreignId('owner_contact_id')->nullable()->constrained('customer_contacts')->nullOnDelete();
            $table->string('ownership_type', 32)->default('customer_owned')->index();
            $table->string('status', 32)->default('pending')->index();
            $table->timestamp('activated_at')->nullable()->index();
            $table->timestamp('expires_at')->nullable()->index();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['customer_id', 'application_id']);
            $table->index(['customer_id', 'status']);
            $table->index(['application_id', 'status']);
            $table->index(['ownership_type', 'status']);
            $table->index('created_by');
            $table->index('updated_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_applications');
    }
};
