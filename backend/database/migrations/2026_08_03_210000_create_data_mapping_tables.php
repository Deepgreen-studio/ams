<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_mappings', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('integration_id')->constrained('integrations')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->string('direction', 32)->default('inbound')->index();
            $table->string('status', 32)->default('draft')->index();
            $table->string('source_entity', 150)->index();
            $table->string('target_entity', 150)->nullable()->index();
            $table->unsignedInteger('version')->default(1);
            $table->boolean('is_active')->default(true)->index();
            $table->json('external_schema')->nullable();
            $table->json('sample_payload')->nullable();
            $table->json('options')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['company_id', 'slug']);
            $table->index(['integration_id', 'is_active']);
            $table->index(['company_id', 'source_entity']);
        });

        Schema::create('data_mapping_fields', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('data_mapping_id')->constrained('data_mappings')->cascadeOnDelete();
            $table->string('external_field', 255);
            $table->string('internal_field', 255);
            $table->string('transform_type', 50)->default('none');
            $table->json('transform_config')->nullable();
            $table->boolean('is_required')->default(false)->index();
            $table->text('default_value')->nullable();
            $table->json('custom_rules')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_enabled')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['data_mapping_id', 'sort_order']);
            $table->unique(['data_mapping_id', 'external_field', 'internal_field'], 'data_mapping_fields_unique_pair');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_mapping_fields');
        Schema::dropIfExists('data_mappings');
    }
};
