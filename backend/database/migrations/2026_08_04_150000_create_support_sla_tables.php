<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_sla_calendars', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->nullable()->constrained('companies')->cascadeOnDelete();
            $table->string('name');
            $table->string('timezone', 64)->default('UTC');
            $table->json('business_hours');
            $table->boolean('is_default')->default(false)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['company_id', 'is_default'], 'sla_cal_company_default_idx');
        });

        Schema::create('support_sla_holidays', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->nullable()->constrained('companies')->cascadeOnDelete();
            $table->foreignId('support_sla_calendar_id')->nullable()->constrained('support_sla_calendars')->nullOnDelete();
            $table->string('name');
            $table->date('holiday_date');
            $table->boolean('is_recurring')->default(false);
            $table->boolean('is_active')->default(true)->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['company_id', 'holiday_date'], 'sla_hol_company_date_idx');
            $table->index(['support_sla_calendar_id', 'holiday_date'], 'sla_hol_calendar_date_idx');
        });

        Schema::create('support_sla_policies', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->nullable()->constrained('companies')->cascadeOnDelete();
            $table->foreignId('support_sla_calendar_id')->nullable()->constrained('support_sla_calendars')->nullOnDelete();
            $table->string('name');
            $table->string('code', 64)->nullable();
            $table->string('priority', 32)->nullable()->index();
            $table->string('category', 64)->nullable()->index();
            $table->unsignedInteger('response_target_minutes');
            $table->unsignedInteger('resolution_target_minutes');
            $table->unsignedTinyInteger('at_risk_percent')->default(80);
            $table->boolean('business_hours_only')->default(true);
            $table->boolean('is_default')->default(false)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->text('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['company_id', 'priority', 'is_active'], 'sla_pol_company_prio_idx');
            $table->unique(['company_id', 'code'], 'sla_pol_company_code_uq');
        });

        Schema::create('support_sla_escalation_rules', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('support_sla_policy_id')->constrained('support_sla_policies')->cascadeOnDelete();
            $table->string('level', 32)->index();
            $table->string('trigger', 64)->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('notify_role', 64)->nullable();
            $table->foreignId('notify_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('reassign_to_manager')->default(false);
            $table->boolean('is_active')->default(true)->index();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['support_sla_policy_id', 'level', 'trigger'], 'sla_esc_rule_policy_idx');
        });

        Schema::create('support_sla_escalations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('support_ticket_id')->constrained('support_tickets')->cascadeOnDelete();
            $table->foreignId('support_sla_policy_id')->nullable()->constrained('support_sla_policies')->nullOnDelete();
            $table->foreignId('support_sla_escalation_rule_id')->nullable()->constrained('support_sla_escalation_rules')->nullOnDelete();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('level', 32)->index();
            $table->string('trigger', 64)->index();
            $table->string('metric', 32)->index();
            $table->string('status', 32)->default('pending')->index();
            $table->timestamp('triggered_at')->nullable();
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('acknowledged_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['support_ticket_id', 'level', 'trigger'], 'sla_esc_ticket_level_idx');
            $table->index(['company_id', 'status', 'triggered_at'], 'sla_esc_company_status_idx');
        });

        Schema::table('support_tickets', function (Blueprint $table) {
            if (! Schema::hasColumn('support_tickets', 'support_sla_policy_id')) {
                $table->foreignId('support_sla_policy_id')->nullable()->after('closed_at')
                    ->constrained('support_sla_policies')->nullOnDelete();
            }
            if (! Schema::hasColumn('support_tickets', 'sla_status')) {
                $table->string('sla_status', 32)->default('not_applicable')->after('support_sla_policy_id')->index();
            }
            if (! Schema::hasColumn('support_tickets', 'escalation_level')) {
                $table->string('escalation_level', 32)->nullable()->after('sla_status')->index();
            }
            if (! Schema::hasColumn('support_tickets', 'first_response_due_at')) {
                $table->timestamp('first_response_due_at')->nullable()->after('escalation_level');
            }
            if (! Schema::hasColumn('support_tickets', 'resolution_due_at')) {
                $table->timestamp('resolution_due_at')->nullable()->after('first_response_due_at');
            }
            if (! Schema::hasColumn('support_tickets', 'first_response_at')) {
                $table->timestamp('first_response_at')->nullable()->after('resolution_due_at');
            }
            if (! Schema::hasColumn('support_tickets', 'resolved_at')) {
                $table->timestamp('resolved_at')->nullable()->after('first_response_at');
            }
            if (! Schema::hasColumn('support_tickets', 'response_breached_at')) {
                $table->timestamp('response_breached_at')->nullable()->after('resolved_at');
            }
            if (! Schema::hasColumn('support_tickets', 'resolution_breached_at')) {
                $table->timestamp('resolution_breached_at')->nullable()->after('response_breached_at');
            }
            if (! Schema::hasColumn('support_tickets', 'sla_paused_at')) {
                $table->timestamp('sla_paused_at')->nullable()->after('resolution_breached_at');
            }
            if (! Schema::hasColumn('support_tickets', 'sla_paused_seconds')) {
                $table->unsignedInteger('sla_paused_seconds')->default(0)->after('sla_paused_at');
            }

            $table->index(['first_response_due_at', 'sla_status'], 'st_sla_response_due_idx');
            $table->index(['resolution_due_at', 'sla_status'], 'st_sla_resolution_due_idx');
        });
    }

    public function down(): void
    {
        Schema::table('support_tickets', function (Blueprint $table) {
            $columns = [
                'support_sla_policy_id',
                'sla_status',
                'escalation_level',
                'first_response_due_at',
                'resolution_due_at',
                'first_response_at',
                'resolved_at',
                'response_breached_at',
                'resolution_breached_at',
                'sla_paused_at',
                'sla_paused_seconds',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('support_tickets', $column)) {
                    if ($column === 'support_sla_policy_id') {
                        $table->dropConstrainedForeignId('support_sla_policy_id');
                    } else {
                        $table->dropColumn($column);
                    }
                }
            }
        });

        Schema::dropIfExists('support_sla_escalations');
        Schema::dropIfExists('support_sla_escalation_rules');
        Schema::dropIfExists('support_sla_policies');
        Schema::dropIfExists('support_sla_holidays');
        Schema::dropIfExists('support_sla_calendars');
    }
};
