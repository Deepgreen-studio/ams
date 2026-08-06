<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_notes', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->string('note_type', 50);
            $table->string('title')->nullable();
            $table->longText('body');
            $table->boolean('is_pinned')->default(false);
            $table->string('status', 50)->default('active');
            $table->timestamp('occurred_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['customer_id', 'note_type']);
            $table->index(['customer_id', 'is_pinned']);
            $table->index(['customer_id', 'status']);
        });

        Schema::create('customer_tasks', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status', 50)->default('open');
            $table->string('priority', 50)->default('medium');
            $table->timestamp('due_at')->nullable();
            $table->timestamp('remind_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['customer_id', 'status']);
            $table->index(['customer_id', 'due_at']);
            $table->index(['customer_id', 'remind_at']);
            $table->index(['assigned_to', 'status']);
        });

        Schema::create('customer_communications', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->string('type', 50);
            $table->string('direction', 50)->default('outbound');
            $table->string('subject')->nullable();
            $table->longText('body')->nullable();
            $table->string('status', 50)->default('logged');
            $table->string('channel_reference')->nullable();
            $table->json('participants')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->timestamp('occurred_at');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['customer_id', 'type']);
            $table->index(['customer_id', 'occurred_at']);
            $table->index(['customer_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_communications');
        Schema::dropIfExists('customer_tasks');
        Schema::dropIfExists('customer_notes');
    }
};
