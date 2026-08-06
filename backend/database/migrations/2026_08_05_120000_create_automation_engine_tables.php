<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('automation_rules', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('trigger_type', 32)->index();
            $table->string('event_key', 100)->nullable()->index();
            $table->string('schedule_cron', 64)->nullable();
            $table->string('schedule_timezone', 64)->nullable()->default('UTC');
            $table->unsignedInteger('delay_minutes')->nullable();
            $table->string('condition_logic', 16)->default('and');
            $table->boolean('is_enabled')->default(true)->index();
            $table->unsignedSmallInteger('priority')->default(100)->index();
            $table->timestamp('last_triggered_at')->nullable();
            $table->timestamp('next_run_at')->nullable()->index();
            $table->json('metadata')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['trigger_type', 'is_enabled']);
            $table->index(['event_key', 'is_enabled']);
        });

        Schema::create('automation_conditions', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('automation_rule_id');
            $table->string('field', 100);
            $table->string('operator', 32);
            $table->text('value')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('automation_rule_id', 'ac_rule_fk')
                ->references('id')->on('automation_rules')->cascadeOnDelete();
            $table->index(['automation_rule_id', 'sort_order'], 'ac_rule_sort_idx');
        });

        Schema::create('automation_actions', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('automation_rule_id');
            $table->string('action_type', 64)->index();
            $table->json('config')->nullable();
            $table->boolean('is_enabled')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('automation_rule_id', 'aa_rule_fk')
                ->references('id')->on('automation_rules')->cascadeOnDelete();
            $table->index(['automation_rule_id', 'sort_order'], 'aa_rule_sort_idx');
        });

        Schema::create('automation_logs', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('automation_rule_id')->nullable();
            $table->string('status', 32)->index();
            $table->string('trigger_type', 32)->nullable()->index();
            $table->string('event_key', 100)->nullable()->index();
            $table->json('context')->nullable();
            $table->json('actions_result')->nullable();
            $table->text('message')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->foreign('automation_rule_id', 'al_rule_fk')
                ->references('id')->on('automation_rules')->nullOnDelete();
            $table->index(['created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('automation_logs');
        Schema::dropIfExists('automation_actions');
        Schema::dropIfExists('automation_conditions');
        Schema::dropIfExists('automation_rules');
    }
};
