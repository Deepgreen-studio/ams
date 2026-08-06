<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('policy_versions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('policy_id')->constrained('policies')->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->string('status', 32)->index();
            $table->string('title');
            $table->longText('body')->nullable();
            $table->json('snapshot')->nullable();
            $table->string('reason', 255)->nullable();
            $table->boolean('is_restore')->default(false);
            $table->unsignedInteger('restored_from_version')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['policy_id', 'version']);
            $table->index(['policy_id', 'created_at']);
            $table->index(['policy_id', 'status']);
            $table->index('created_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('policy_versions');
    }
};
