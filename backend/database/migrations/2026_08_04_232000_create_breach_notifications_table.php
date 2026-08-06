<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('breach_notifications', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('data_breach_id')->constrained('data_breaches')->cascadeOnDelete();
            $table->string('notification_type', 64)->index();
            $table->string('channel', 32)->default('email')->index();
            $table->string('recipient');
            $table->string('subject')->nullable();
            $table->text('message')->nullable();
            $table->string('status', 32)->default('draft')->index();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('acknowledged_at')->nullable();
            $table->foreignId('sent_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['data_breach_id', 'created_at']);
            $table->index(['data_breach_id', 'notification_type']);
            $table->index(['status', 'created_at']);
            $table->index('sent_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('breach_notifications');
    }
};
