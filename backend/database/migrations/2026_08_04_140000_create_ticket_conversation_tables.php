<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_messages', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('support_ticket_id')->constrained('support_tickets')->cascadeOnDelete();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('author_type', 32)->default('agent')->index();
            $table->string('visibility', 32)->default('public')->index();
            $table->longText('body');
            $table->string('body_format', 16)->default('html');
            $table->boolean('is_system')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['support_ticket_id', 'created_at'], 'tm_ticket_created_idx');
            $table->index(['company_id', 'created_at'], 'tm_company_created_idx');
            $table->index(['support_ticket_id', 'visibility'], 'tm_ticket_visibility_idx');
        });

        Schema::create('ticket_attachments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('support_ticket_id')->constrained('support_tickets')->cascadeOnDelete();
            $table->foreignId('ticket_message_id')->nullable()->constrained('ticket_messages')->cascadeOnDelete();
            $table->string('attachment_type', 32)->default('file')->index();
            $table->string('disk', 64)->default('local');
            $table->string('path');
            $table->string('original_filename');
            $table->string('extension', 32)->nullable();
            $table->string('mime_type', 127)->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['support_ticket_id', 'created_at'], 'ta_ticket_created_idx');
            $table->index('ticket_message_id', 'ta_message_idx');
        });

        Schema::create('ticket_message_reads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_message_id')->constrained('ticket_messages')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('read_at')->useCurrent();
            $table->timestamps();

            $table->unique(['ticket_message_id', 'user_id'], 'tmr_message_user_uq');
            $table->index(['user_id', 'read_at'], 'tmr_user_read_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_message_reads');
        Schema::dropIfExists('ticket_attachments');
        Schema::dropIfExists('ticket_messages');
    }
};
