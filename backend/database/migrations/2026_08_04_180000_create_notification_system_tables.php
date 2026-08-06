<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_templates', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('event_key', 100)->index();
            $table->string('channel', 32)->index();
            $table->string('name');
            $table->string('subject')->nullable();
            $table->longText('body');
            $table->json('available_variables')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('is_system')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['event_key', 'channel', 'name']);
        });

        Schema::create('notification_preferences', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('event_key', 100);
            $table->boolean('email_enabled')->default(true);
            $table->boolean('in_app_enabled')->default(true);
            $table->boolean('sms_enabled')->default(false);
            $table->boolean('push_enabled')->default(false);
            $table->timestamps();

            $table->unique(['user_id', 'event_key']);
            $table->index(['event_key']);
        });

        Schema::create('notification_delivery_logs', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->uuid('laravel_notification_id')->nullable()->index();
            $table->morphs('notifiable');
            $table->string('event_key', 100)->index();
            $table->string('channel', 32)->index();
            $table->string('status', 32)->index();
            $table->string('recipient')->nullable();
            $table->string('subject')->nullable();
            $table->text('body_preview')->nullable();
            $table->text('error_message')->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('queued_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();

            $table->index(['created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_delivery_logs');
        Schema::dropIfExists('notification_preferences');
        Schema::dropIfExists('notification_templates');
    }
};
