<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('application_versions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('application_id')->constrained('applications')->cascadeOnDelete();
            $table->string('version_number', 64);
            $table->unsignedInteger('major');
            $table->unsignedInteger('minor');
            $table->unsignedInteger('patch');
            $table->string('build_number', 64)->nullable();
            $table->string('status', 32)->default('draft')->index();
            $table->timestamp('release_date')->nullable()->index();
            $table->string('minimum_supported_version', 64)->nullable();
            $table->longText('release_notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['application_id', 'version_number']);
            $table->index(['application_id', 'status']);
            $table->index(['application_id', 'major', 'minor', 'patch'], 'application_versions_semver_index');
            $table->index('created_by');
            $table->index('updated_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_versions');
    }
};
