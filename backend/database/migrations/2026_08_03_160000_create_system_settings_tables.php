<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('setting_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->string('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('group', 64)->index();
            $table->string('key', 128);
            $table->longText('value')->nullable();
            $table->string('type', 32)->default('string');
            $table->string('description')->nullable();
            $table->boolean('is_public')->default(false)->index();
            $table->boolean('is_encrypted')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['group', 'key']);
            $table->index(['group', 'key']);
        });

        Schema::create('file_folders', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('parent_id')->nullable()->constrained('file_folders')->nullOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['parent_id', 'name']);
            $table->unique(['parent_id', 'slug']);
        });

        Schema::create('media_files', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('folder_id')->nullable()->constrained('file_folders')->nullOnDelete();
            $table->string('filename');
            $table->string('original_name');
            $table->string('extension', 32)->nullable();
            $table->string('mime_type', 128)->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->string('disk', 64)->default('public');
            $table->string('path');
            $table->string('url')->nullable();
            $table->json('meta')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['folder_id', 'created_at']);
            $table->index('mime_type');
            $table->index('original_name');
            $table->index('uploaded_by');
        });

        Schema::create('configuration_logs', function (Blueprint $table) {
            $table->id();
            $table->string('setting_key');
            $table->string('group', 64)->nullable()->index();
            $table->longText('old_value')->nullable();
            $table->longText('new_value')->nullable();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index('setting_key');
            $table->index('changed_by');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('configuration_logs');
        Schema::dropIfExists('media_files');
        Schema::dropIfExists('file_folders');
        Schema::dropIfExists('system_settings');
        Schema::dropIfExists('setting_groups');
    }
};
