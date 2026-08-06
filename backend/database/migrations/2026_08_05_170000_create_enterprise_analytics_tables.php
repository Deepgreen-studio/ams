<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analytics_events', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('application_id')->nullable()->constrained('applications')->nullOnDelete();
            $table->unsignedBigInteger('customer_id')->nullable()->index();
            $table->string('category', 32)->index();
            $table->string('event_name', 120)->index();
            $table->string('event_source', 120)->nullable()->index();
            $table->string('subject_type', 120)->nullable()->index();
            $table->string('subject_id', 64)->nullable()->index();
            $table->json('properties')->nullable();
            $table->json('metrics')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('occurred_at')->index();
            $table->timestamps();

            $table->foreign('customer_id', 'analytics_events_customer_fk')
                ->references('id')->on('customers')->nullOnDelete();

            $table->index(['company_id', 'category', 'occurred_at'], 'analytics_events_company_cat_occ_idx');
            $table->index(['category', 'event_name', 'occurred_at'], 'analytics_events_cat_name_occ_idx');
            $table->index(['subject_type', 'subject_id'], 'analytics_events_subject_idx');
        });

        Schema::create('analytics_dashboards', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->string('name');
            $table->string('slug', 120);
            $table->text('description')->nullable();
            $table->string('kind', 32)->default('dashboard')->index();
            $table->string('category', 32)->default('business')->index();
            $table->string('status', 32)->default('draft')->index();
            $table->json('layout')->nullable();
            $table->json('filters')->nullable();
            $table->boolean('is_default')->default(false)->index();
            $table->boolean('is_system')->default(false)->index();
            $table->boolean('is_shared')->default(false)->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['company_id', 'slug'], 'analytics_dashboards_company_slug_uq');
            $table->index(['kind', 'status'], 'analytics_dashboards_kind_status_idx');
            $table->index(['category', 'status'], 'analytics_dashboards_cat_status_idx');
        });

        Schema::create('analytics_widgets', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('analytics_dashboard_id')
                ->constrained('analytics_dashboards')
                ->cascadeOnDelete();
            $table->string('name');
            $table->string('key', 100);
            $table->string('type', 32)->index();
            $table->string('category', 32)->nullable()->index();
            $table->string('data_source', 120)->nullable()->index();
            $table->json('query_config')->nullable();
            $table->json('visualization_config')->nullable();
            $table->unsignedSmallInteger('position_x')->default(0);
            $table->unsignedSmallInteger('position_y')->default(0);
            $table->unsignedSmallInteger('width')->default(4);
            $table->unsignedSmallInteger('height')->default(2);
            $table->unsignedInteger('sort_order')->default(0);
            $table->unsignedInteger('refresh_interval_seconds')->nullable();
            $table->boolean('is_visible')->default(true)->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['analytics_dashboard_id', 'key'], 'analytics_widgets_dashboard_key_uq');
            $table->index(['analytics_dashboard_id', 'sort_order'], 'analytics_widgets_dash_sort_idx');
        });

        Schema::create('analytics_reports', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->string('name');
            $table->string('slug', 120);
            $table->text('description')->nullable();
            $table->string('category', 32)->default('business')->index();
            $table->string('report_type', 64)->default('custom')->index();
            $table->string('status', 32)->default('draft')->index();
            $table->json('query_config')->nullable();
            $table->json('schedule_config')->nullable();
            $table->json('format_defaults')->nullable();
            $table->timestamp('last_run_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['company_id', 'slug'], 'analytics_reports_company_slug_uq');
            $table->index(['category', 'status'], 'analytics_reports_cat_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_reports');
        Schema::dropIfExists('analytics_widgets');
        Schema::dropIfExists('analytics_dashboards');
        Schema::dropIfExists('analytics_events');
    }
};
