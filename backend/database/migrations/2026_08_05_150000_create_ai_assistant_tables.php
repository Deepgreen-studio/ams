<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_providers', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->string('name');
            $table->string('slug', 64);
            $table->string('driver', 64)->index();
            $table->string('status', 32)->default('inactive')->index();
            $table->string('base_url')->nullable();
            $table->string('default_model', 120)->nullable();
            $table->string('embedding_model', 120)->nullable();
            $table->string('authentication_type', 32)->default('api_key');
            $table->text('credentials')->nullable();
            $table->json('config')->nullable();
            $table->string('health_status', 32)->nullable();
            $table->timestamp('last_health_check_at')->nullable();
            $table->unsignedInteger('timeout_seconds')->default(30);
            $table->unsignedTinyInteger('retry_attempts')->default(2);
            $table->boolean('is_default')->default(false)->index();
            $table->boolean('is_enabled')->default(true)->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['company_id', 'slug'], 'ai_providers_company_slug_uq');
            $table->index(['driver', 'is_enabled'], 'ai_providers_driver_enabled_idx');
        });

        Schema::create('ai_prompts', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->string('key', 100);
            $table->string('name');
            $table->string('feature', 64)->index();
            $table->text('system_prompt')->nullable();
            $table->text('user_template')->nullable();
            $table->string('model_override', 120)->nullable();
            $table->decimal('temperature', 3, 2)->nullable();
            $table->unsignedInteger('max_tokens')->nullable();
            $table->json('output_schema')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->string('status', 32)->default('draft')->index();
            $table->json('metadata')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['company_id', 'key'], 'ai_prompts_company_key_uq');
            $table->index(['feature', 'status'], 'ai_prompts_feature_status_idx');
        });

        Schema::create('ai_conversations', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedBigInteger('ai_provider_id')->nullable();
            $table->unsignedBigInteger('ai_prompt_id')->nullable();
            $table->string('feature', 64)->index();
            $table->string('context_type', 120)->nullable()->index();
            $table->string('context_id', 64)->nullable()->index();
            $table->string('title')->nullable();
            $table->string('status', 32)->default('active')->index();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('ai_provider_id', 'ai_conv_provider_fk')
                ->references('id')->on('ai_providers')->nullOnDelete();
            $table->foreign('ai_prompt_id', 'ai_conv_prompt_fk')
                ->references('id')->on('ai_prompts')->nullOnDelete();
            $table->index(['user_id', 'status'], 'ai_conv_user_status_idx');
            $table->index(['context_type', 'context_id'], 'ai_conv_context_idx');
        });

        Schema::create('ai_messages', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('ai_conversation_id');
            $table->string('role', 32)->index();
            $table->longText('content');
            $table->unsignedInteger('token_input')->nullable();
            $table->unsignedInteger('token_output')->nullable();
            $table->string('model', 120)->nullable();
            $table->string('finish_reason', 64)->nullable();
            $table->json('tool_calls')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('ai_conversation_id', 'ai_msg_conversation_fk')
                ->references('id')->on('ai_conversations')->cascadeOnDelete();
            $table->index(['ai_conversation_id', 'id'], 'ai_msg_conversation_id_idx');
        });

        Schema::create('ai_usage_logs', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('ai_provider_id')->nullable();
            $table->unsignedBigInteger('ai_conversation_id')->nullable();
            $table->unsignedBigInteger('ai_message_id')->nullable();
            $table->string('feature', 64)->index();
            $table->string('operation', 64)->index();
            $table->string('driver', 64)->nullable()->index();
            $table->string('model', 120)->nullable();
            $table->unsignedInteger('tokens_in')->default(0);
            $table->unsignedInteger('tokens_out')->default(0);
            $table->unsignedInteger('latency_ms')->nullable();
            $table->string('status', 32)->index();
            $table->text('error_message')->nullable();
            $table->decimal('cost_estimate', 12, 6)->nullable();
            $table->string('request_id', 120)->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->foreign('ai_provider_id', 'ai_usage_provider_fk')
                ->references('id')->on('ai_providers')->nullOnDelete();
            $table->foreign('ai_conversation_id', 'ai_usage_conversation_fk')
                ->references('id')->on('ai_conversations')->nullOnDelete();
            $table->foreign('ai_message_id', 'ai_usage_message_fk')
                ->references('id')->on('ai_messages')->nullOnDelete();
            $table->index(['company_id', 'created_at'], 'ai_usage_company_created_idx');
            $table->index(['feature', 'created_at'], 'ai_usage_feature_created_idx');
        });

        Schema::create('ai_settings', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->string('group', 64)->default('general')->index();
            $table->string('key', 100);
            $table->json('value')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'key'], 'ai_settings_company_key_uq');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_settings');
        Schema::dropIfExists('ai_usage_logs');
        Schema::dropIfExists('ai_messages');
        Schema::dropIfExists('ai_conversations');
        Schema::dropIfExists('ai_prompts');
        Schema::dropIfExists('ai_providers');
    }
};
