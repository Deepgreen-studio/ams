<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_folders', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('parent_id')->nullable()->constrained('media_folders')->nullOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->string('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['parent_id', 'slug']);
            $table->index(['parent_id', 'sort_order']);
        });

        Schema::create('media_library', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->uuid('media_group_uuid')->index();
            $table->foreignId('folder_id')->nullable()->constrained('media_folders')->nullOnDelete();
            $table->unsignedInteger('version')->default(1);
            $table->boolean('is_current')->default(true)->index();
            $table->string('name');
            $table->string('original_name');
            $table->string('filename');
            $table->string('extension', 32)->index();
            $table->string('mime_type', 127)->index();
            $table->string('type', 32)->index();
            $table->unsignedBigInteger('size')->default(0);
            $table->string('disk', 64)->default('public');
            $table->string('path');
            $table->string('url', 1000)->nullable();
            $table->string('alt_text')->nullable();
            $table->string('caption')->nullable();
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->string('checksum', 64)->nullable()->index();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['media_group_uuid', 'version']);
            $table->index(['folder_id', 'is_current']);
            $table->index(['type', 'is_current']);
            $table->index(['name', 'original_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_library');
        Schema::dropIfExists('media_folders');
    }
};
