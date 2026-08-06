<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('policies', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('policy_number', 64);
            $table->string('title');
            $table->string('slug', 191);
            $table->string('policy_type', 64)->index();
            $table->text('description')->nullable();
            $table->longText('body')->nullable();
            $table->string('status', 32)->default('draft')->index();
            $table->unsignedInteger('current_version')->default(1);
            $table->foreignId('content_id')->nullable()->constrained('contents')->nullOnDelete();
            $table->timestamp('effective_at')->nullable()->index();
            $table->timestamp('expires_at')->nullable();
            $table->date('review_due_at')->nullable()->index();
            $table->timestamp('published_at')->nullable()->index();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['company_id', 'policy_number']);
            $table->unique(['company_id', 'slug']);
            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'policy_type']);
            $table->index(['company_id', 'review_due_at']);
            $table->index('content_id');
            $table->index('assigned_to');
            $table->index('created_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('policies');
    }
};
