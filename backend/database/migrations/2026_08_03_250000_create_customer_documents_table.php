<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_documents', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->uuid('document_group_uuid')->index();
            $table->unsignedInteger('version')->default(1);
            $table->boolean('is_current')->default(true);
            $table->string('name');
            $table->string('category', 50);
            $table->string('status', 50)->default('active');
            $table->string('disk', 50)->default('public');
            $table->string('path');
            $table->string('original_filename');
            $table->string('extension', 20)->nullable();
            $table->string('mime_type', 120)->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->timestamp('expires_at')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['document_group_uuid', 'version']);
            $table->index(['customer_id', 'category', 'is_current']);
            $table->index(['customer_id', 'status']);
            $table->index(['customer_id', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_documents');
    }
};
