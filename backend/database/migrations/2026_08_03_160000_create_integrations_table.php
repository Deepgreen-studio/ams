<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integrations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->string('type', 32)->index();
            $table->string('status', 32)->default('draft')->index();
            $table->string('authentication_type', 32)->index();
            $table->string('base_url')->nullable();
            $table->string('api_version', 32)->nullable();
            $table->unsignedInteger('timeout')->default(30);
            $table->unsignedTinyInteger('retry_attempts')->default(3);
            $table->string('health_status', 32)->default('unknown')->index();
            $table->timestamp('last_health_check')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['company_id', 'slug']);
            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'type']);
            $table->index(['company_id', 'name']);
            $table->index('created_by');
            $table->index('updated_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integrations');
    }
};
