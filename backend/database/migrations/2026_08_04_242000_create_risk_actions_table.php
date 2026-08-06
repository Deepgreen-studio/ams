<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('risk_actions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('risk_register_id')->constrained('risk_register')->cascadeOnDelete();
            $table->string('action_type', 64)->index();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status', 32)->default('planned')->index();
            $table->string('from_status', 32)->nullable();
            $table->string('to_status', 32)->nullable();
            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('due_at')->nullable()->index();
            $table->timestamp('completed_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['risk_register_id', 'created_at']);
            $table->index(['risk_register_id', 'action_type']);
            $table->index(['risk_register_id', 'status']);
            $table->index('performed_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('risk_actions');
    }
};
