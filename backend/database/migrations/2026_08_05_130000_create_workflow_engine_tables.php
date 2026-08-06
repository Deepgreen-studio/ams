<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflows', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('type', 32)->index();
            $table->string('status', 32)->default('draft')->index();
            $table->unsignedInteger('version')->default(1);
            $table->boolean('is_enabled')->default(true)->index();
            $table->json('metadata')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['type', 'status'], 'wf_type_status_idx');
            $table->index(['company_id', 'is_enabled'], 'wf_company_enabled_idx');
        });

        Schema::create('workflow_steps', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('workflow_id');
            $table->string('name');
            $table->string('step_key', 64);
            $table->string('step_type', 32)->index();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->integer('position_x')->default(0);
            $table->integer('position_y')->default(0);
            $table->json('config')->nullable();
            $table->json('next_step_keys')->nullable();
            $table->string('on_approve_step_key', 64)->nullable();
            $table->string('on_reject_step_key', 64)->nullable();
            $table->boolean('is_required')->default(true);
            $table->timestamps();

            $table->foreign('workflow_id', 'wfs_workflow_fk')
                ->references('id')->on('workflows')->cascadeOnDelete();
            $table->unique(['workflow_id', 'step_key'], 'wfs_workflow_key_uq');
            $table->index(['workflow_id', 'sort_order'], 'wfs_workflow_sort_idx');
        });

        Schema::create('workflow_instances', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('workflow_id');
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->string('subject_type', 120)->nullable()->index();
            $table->string('subject_id', 64)->nullable()->index();
            $table->string('subject_label')->nullable();
            $table->string('status', 32)->index();
            $table->unsignedBigInteger('current_step_id')->nullable();
            $table->json('active_step_keys')->nullable();
            $table->json('pending_approvers')->nullable();
            $table->json('context')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('due_at')->nullable()->index();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('started_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->foreign('workflow_id', 'wfi_workflow_fk')
                ->references('id')->on('workflows')->cascadeOnDelete();
            $table->foreign('current_step_id', 'wfi_current_step_fk')
                ->references('id')->on('workflow_steps')->nullOnDelete();
            $table->index(['workflow_id', 'status'], 'wfi_workflow_status_idx');
            $table->index(['subject_type', 'subject_id'], 'wfi_subject_idx');
        });

        Schema::create('workflow_logs', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('workflow_instance_id');
            $table->unsignedBigInteger('workflow_step_id')->nullable();
            $table->string('action', 32)->index();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('from_status', 32)->nullable();
            $table->string('to_status', 32)->nullable();
            $table->text('comment')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->foreign('workflow_instance_id', 'wfl_instance_fk')
                ->references('id')->on('workflow_instances')->cascadeOnDelete();
            $table->foreign('workflow_step_id', 'wfl_step_fk')
                ->references('id')->on('workflow_steps')->nullOnDelete();
            $table->index(['workflow_instance_id', 'created_at'], 'wfl_instance_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_logs');
        Schema::dropIfExists('workflow_instances');
        Schema::dropIfExists('workflow_steps');
        Schema::dropIfExists('workflows');
    }
};
