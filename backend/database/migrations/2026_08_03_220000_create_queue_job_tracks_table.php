<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('queue_job_tracks', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('job_uuid')->nullable()->index();
            $table->string('job_class');
            $table->string('display_name')->nullable();
            $table->string('queue', 64)->index();
            $table->string('priority', 20)->default('normal')->index();
            $table->string('type', 40)->index();
            $table->string('status', 32)->default('queued')->index();
            $table->unsignedInteger('attempts')->default(0);
            $table->unsignedInteger('max_tries')->default(1);
            $table->unsignedInteger('delay_seconds')->default(0);
            $table->json('payload')->nullable();
            $table->text('exception')->nullable();
            $table->timestamp('queued_at')->nullable();
            $table->timestamp('available_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->foreignId('triggered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('related_type')->nullable();
            $table->unsignedBigInteger('related_id')->nullable();
            $table->timestamps();

            $table->index(['related_type', 'related_id']);
            $table->index(['status', 'created_at']);
            $table->index(['type', 'status']);
            $table->index(['queue', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('queue_job_tracks');
    }
};
