<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('application_configurations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('application_id')->constrained('applications')->cascadeOnDelete();
            $table->foreignId('environment_id')->nullable()->constrained('application_environments')->nullOnDelete();
            $table->string('type', 64)->index();
            $table->string('name');
            $table->text('description')->nullable();
            $table->longText('payload');
            $table->string('status', 32)->default('draft')->index();
            $table->unsignedInteger('version')->default(1);
            $table->boolean('is_active')->default(true)->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['application_id', 'environment_id', 'type'], 'app_config_app_env_type_unique');
            $table->index(['application_id', 'type']);
            $table->index(['application_id', 'status']);
            $table->index('created_by');
            $table->index('updated_by');
        });

        Schema::create('application_configuration_histories', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('configuration_id')->constrained('application_configurations')->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->longText('payload');
            $table->string('status', 32)->nullable();
            $table->string('change_summary')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['configuration_id', 'version'], 'app_config_history_config_version_unique');
            $table->index(['configuration_id', 'created_at'], 'app_config_history_config_created_idx');
            $table->index('created_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_configuration_histories');
        Schema::dropIfExists('application_configurations');
    }
};
