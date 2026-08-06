<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('integration_id')->nullable()->constrained('integrations')->nullOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->string('platform', 32)->index();
            $table->string('category', 64)->nullable()->index();
            $table->string('icon')->nullable();
            $table->string('banner')->nullable();
            $table->string('current_version', 64)->nullable();
            $table->string('minimum_supported_version', 64)->nullable();
            $table->string('status', 32)->default('draft')->index();
            $table->string('visibility', 32)->default('private')->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['company_id', 'slug']);
            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'platform']);
            $table->index(['company_id', 'visibility']);
            $table->index(['company_id', 'name']);
            $table->index('created_by');
            $table->index('updated_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('applications');
    }
};
