<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('analytics_dashboards', function (Blueprint $table): void {
            $table->foreignId('owner_id')->nullable()->after('company_id')->constrained('users')->nullOnDelete();
            $table->string('visibility', 32)->default('personal')->after('status')->index();
            $table->boolean('is_template')->default(false)->after('is_shared')->index();
            $table->unsignedBigInteger('template_source_id')->nullable()->after('is_template');
            $table->json('settings')->nullable()->after('filters');

            $table->foreign('template_source_id', 'analytics_dashboards_template_source_fk')
                ->references('id')->on('analytics_dashboards')->nullOnDelete();

            $table->index(['visibility', 'owner_id'], 'analytics_dashboards_visibility_owner_idx');
            $table->index(['company_id', 'visibility'], 'analytics_dashboards_company_visibility_idx');
        });

        Schema::create('analytics_dashboard_shares', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('analytics_dashboard_id')
                ->constrained('analytics_dashboards')
                ->cascadeOnDelete();
            $table->string('share_type', 32)->index();
            $table->unsignedBigInteger('share_id')->index();
            $table->boolean('can_edit')->default(false);
            $table->foreignId('shared_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(
                ['analytics_dashboard_id', 'share_type', 'share_id'],
                'analytics_dashboard_shares_unique'
            );
            $table->index(['share_type', 'share_id'], 'analytics_dashboard_shares_target_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_dashboard_shares');

        Schema::table('analytics_dashboards', function (Blueprint $table): void {
            $table->dropForeign(['owner_id']);
            $table->dropForeign('analytics_dashboards_template_source_fk');
            $table->dropIndex('analytics_dashboards_visibility_owner_idx');
            $table->dropIndex('analytics_dashboards_company_visibility_idx');
            $table->dropColumn([
                'owner_id',
                'visibility',
                'is_template',
                'template_source_id',
                'settings',
            ]);
        });
    }
};
