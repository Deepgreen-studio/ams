<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('application_environments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('application_id')->constrained('applications')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->string('type', 32)->index();
            $table->string('api_url')->nullable();
            $table->string('web_url')->nullable();
            $table->string('status', 32)->default('active')->index();
            $table->string('health_status', 32)->default('unknown')->index();
            $table->timestamp('last_health_check')->nullable();
            $table->text('variables')->nullable();
            $table->boolean('is_current')->default(false)->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['application_id', 'slug']);
            $table->unique(['application_id', 'type']);
            $table->index(['application_id', 'status']);
            $table->index(['application_id', 'is_current']);
            $table->index('created_by');
            $table->index('updated_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_environments');
    }
};
