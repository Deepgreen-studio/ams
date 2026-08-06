<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('customer_application_id')->nullable()->constrained('customer_applications')->nullOnDelete();
            $table->string('plan_type', 32)->index();
            $table->string('plan_name');
            $table->string('status', 32)->default('trialing')->index();
            $table->timestamp('starts_at')->nullable()->index();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamp('renews_at')->nullable()->index();
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->json('features')->nullable();
            $table->string('payment_status', 32)->default('not_required')->index();
            $table->string('payment_provider', 32)->nullable()->index();
            $table->string('external_subscription_id')->nullable()->index();
            $table->string('external_customer_id')->nullable()->index();
            $table->string('currency', 8)->default('USD');
            $table->decimal('amount', 12, 2)->nullable();
            $table->unsignedInteger('renewal_reminder_days')->default(14);
            $table->timestamp('last_renewal_reminder_at')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['customer_id', 'status']);
            $table->index(['customer_id', 'plan_type']);
            $table->index(['status', 'expires_at']);
            $table->index(['payment_status', 'renews_at']);
            $table->index('created_by');
            $table->index('updated_by');
        });

        Schema::create('licenses', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('subscription_id')->constrained('subscriptions')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('customer_application_id')->nullable()->constrained('customer_applications')->nullOnDelete();
            $table->string('license_key')->unique();
            $table->string('status', 32)->default('active')->index();
            $table->timestamp('starts_at')->nullable()->index();
            $table->timestamp('expires_at')->nullable()->index();
            $table->json('features')->nullable();
            $table->unsignedInteger('max_activations')->nullable();
            $table->unsignedInteger('activation_count')->default(0);
            $table->timestamp('last_validated_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->string('revoked_reason')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['subscription_id', 'status']);
            $table->index(['customer_id', 'status']);
            $table->index(['status', 'expires_at']);
            $table->index('created_by');
            $table->index('updated_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('licenses');
        Schema::dropIfExists('subscriptions');
    }
};
