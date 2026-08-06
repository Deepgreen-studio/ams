<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('support_tickets', function (Blueprint $table): void {
            if (! Schema::hasColumn('support_tickets', 'involves_personal_data')) {
                $table->boolean('involves_personal_data')->default(false)->after('source');
            }
            if (! Schema::hasColumn('support_tickets', 'compliance_routed_at')) {
                $table->timestamp('compliance_routed_at')->nullable()->after('involves_personal_data');
            }
            if (! Schema::hasColumn('support_tickets', 'privacy_request_id')) {
                $table->foreignId('privacy_request_id')
                    ->nullable()
                    ->after('compliance_routed_at')
                    ->constrained('privacy_requests')
                    ->nullOnDelete();
            }
        });

        Schema::table('privacy_requests', function (Blueprint $table): void {
            if (! Schema::hasColumn('privacy_requests', 'support_ticket_id')) {
                $table->foreignId('support_ticket_id')
                    ->nullable()
                    ->after('customer_id')
                    ->constrained('support_tickets')
                    ->nullOnDelete();
            }
        });

        Schema::table('support_tickets', function (Blueprint $table): void {
            $table->index(['company_id', 'involves_personal_data'], 'support_tickets_company_personal_data_idx');
        });
    }

    public function down(): void
    {
        Schema::table('support_tickets', function (Blueprint $table): void {
            if (Schema::hasColumn('support_tickets', 'privacy_request_id')) {
                $table->dropConstrainedForeignId('privacy_request_id');
            }
            if (Schema::hasColumn('support_tickets', 'compliance_routed_at')) {
                $table->dropColumn('compliance_routed_at');
            }
            if (Schema::hasColumn('support_tickets', 'involves_personal_data')) {
                $table->dropColumn('involves_personal_data');
            }
        });

        Schema::table('privacy_requests', function (Blueprint $table): void {
            if (Schema::hasColumn('privacy_requests', 'support_ticket_id')) {
                $table->dropConstrainedForeignId('support_ticket_id');
            }
        });
    }
};
