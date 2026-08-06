<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('support_tickets', 'department_id')) {
            Schema::table('support_tickets', function (Blueprint $table) {
                $table->foreignId('department_id')
                    ->nullable()
                    ->after('application_id')
                    ->constrained('departments')
                    ->nullOnDelete();
                $table->foreignId('team_id')
                    ->nullable()
                    ->after('department_id')
                    ->constrained('teams')
                    ->nullOnDelete();
                $table->string('assignment_type', 32)
                    ->nullable()
                    ->after('assigned_to');
                $table->timestamp('assigned_at')
                    ->nullable()
                    ->after('assignment_type');
            });
        }

        $this->ensureTicketIndexes();

        DB::table('support_tickets')
            ->where('priority', 'urgent')
            ->update(['priority' => 'critical']);

        if (! Schema::hasTable('support_ticket_status_histories')) {
            Schema::create('support_ticket_status_histories', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('support_ticket_id')->constrained('support_tickets')->cascadeOnDelete();
                $table->string('from_status', 32)->nullable();
                $table->string('to_status', 32)->nullable();
                $table->string('action', 48);
                $table->foreignId('acted_by')->nullable()->constrained('users')->nullOnDelete();
                $table->text('comments')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamp('created_at')->useCurrent();

                $table->index('action', 'st_hist_action_idx');
                $table->index(['support_ticket_id', 'created_at'], 'st_hist_ticket_created_idx');
                $table->index(['to_status', 'created_at'], 'st_hist_status_created_idx');
            });

            return;
        }

        $this->ensureHistoryIndexes();
    }

    public function down(): void
    {
        Schema::dropIfExists('support_ticket_status_histories');

        if (! Schema::hasColumn('support_tickets', 'department_id')) {
            return;
        }

        Schema::table('support_tickets', function (Blueprint $table) {
            $names = $this->indexNames('support_tickets');

            if (in_array('st_company_department_idx', $names, true)) {
                $table->dropIndex('st_company_department_idx');
            }
            if (in_array('st_company_team_idx', $names, true)) {
                $table->dropIndex('st_company_team_idx');
            }
            if (in_array('st_assignment_type_at_idx', $names, true)) {
                $table->dropIndex('st_assignment_type_at_idx');
            }

            $table->dropConstrainedForeignId('department_id');
            $table->dropConstrainedForeignId('team_id');
            $table->dropColumn(['assignment_type', 'assigned_at']);
        });
    }

    protected function ensureTicketIndexes(): void
    {
        $names = $this->indexNames('support_tickets');

        Schema::table('support_tickets', function (Blueprint $table) use ($names): void {
            if (! in_array('st_company_department_idx', $names, true) && Schema::hasColumn('support_tickets', 'department_id')) {
                $table->index(['company_id', 'department_id'], 'st_company_department_idx');
            }
            if (! in_array('st_company_team_idx', $names, true) && Schema::hasColumn('support_tickets', 'team_id')) {
                $table->index(['company_id', 'team_id'], 'st_company_team_idx');
            }
            if (! in_array('st_assignment_type_at_idx', $names, true) && Schema::hasColumn('support_tickets', 'assignment_type')) {
                $table->index(['assignment_type', 'assigned_at'], 'st_assignment_type_at_idx');
            }
        });
    }

    protected function ensureHistoryIndexes(): void
    {
        $names = $this->indexNames('support_ticket_status_histories');

        Schema::table('support_ticket_status_histories', function (Blueprint $table) use ($names): void {
            if (! in_array('st_hist_action_idx', $names, true)) {
                $table->index('action', 'st_hist_action_idx');
            }
            if (! in_array('st_hist_ticket_created_idx', $names, true)) {
                $table->index(['support_ticket_id', 'created_at'], 'st_hist_ticket_created_idx');
            }
            if (! in_array('st_hist_status_created_idx', $names, true)) {
                $table->index(['to_status', 'created_at'], 'st_hist_status_created_idx');
            }
        });
    }

    /**
     * @return list<string>
     */
    protected function indexNames(string $table): array
    {
        return array_map(
            static fn (array $index): string => (string) $index['name'],
            Schema::getIndexes($table)
        );
    }
};
