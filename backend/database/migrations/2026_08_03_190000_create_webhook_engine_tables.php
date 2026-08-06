<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhook_events', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name')->unique();
            $table->string('label');
            $table->text('description')->nullable();
            $table->string('source_module', 64)->index();
            $table->json('payload_schema')->nullable();
            $table->boolean('is_system')->default(true);
            $table->string('status', 32)->default('active')->index();
            $table->timestamps();
        });

        Schema::create('webhooks', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('integration_id')->nullable()->constrained('integrations')->nullOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->string('direction', 20)->index();
            $table->string('status', 32)->default('inactive')->index();
            $table->string('url')->nullable();
            $table->text('secret')->nullable();
            $table->string('signature_algorithm', 32)->default('hmac_sha256');
            $table->string('signature_header', 100)->default('X-AMS-Signature');
            $table->json('subscribed_events')->nullable();
            $table->json('headers')->nullable();
            $table->unsignedInteger('timeout')->default(30);
            $table->unsignedTinyInteger('retry_attempts')->default(3);
            $table->unsignedInteger('retry_delay_seconds')->default(60);
            $table->boolean('verify_ssl')->default(true);
            $table->timestamp('last_triggered_at')->nullable();
            $table->timestamp('last_success_at')->nullable();
            $table->timestamp('last_failure_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['company_id', 'slug']);
            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'direction']);
            $table->index(['integration_id', 'status']);
        });

        Schema::create('webhook_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('webhook_id')->constrained('webhooks')->cascadeOnDelete();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('webhook_event_id')->nullable()->constrained('webhook_events')->nullOnDelete();
            $table->string('direction', 20)->index();
            $table->string('event_name')->nullable()->index();
            $table->string('status', 32)->default('pending')->index();
            $table->string('method', 10)->default('POST');
            $table->text('url')->nullable();
            $table->json('request_headers')->nullable();
            $table->longText('request_body')->nullable();
            $table->unsignedSmallInteger('response_status')->nullable()->index();
            $table->json('response_headers')->nullable();
            $table->longText('response_body')->nullable();
            $table->unsignedInteger('duration_ms')->default(0);
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->unsignedTinyInteger('max_attempts')->default(3);
            $table->timestamp('next_retry_at')->nullable()->index();
            $table->text('error_message')->nullable();
            $table->boolean('is_test')->default(false)->index();
            $table->foreignId('triggered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['webhook_id', 'created_at']);
            $table->index(['webhook_id', 'status']);
            $table->index(['company_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_logs');
        Schema::dropIfExists('webhooks');
        Schema::dropIfExists('webhook_events');
    }
};
