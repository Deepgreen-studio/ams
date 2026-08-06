<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('notification_templates')) {
            Schema::table('notification_templates', function (Blueprint $table): void {
                if (! Schema::hasColumn('notification_templates', 'locale')) {
                    $table->string('locale', 16)->default('en')->after('channel')->index();
                }
                if (! Schema::hasColumn('notification_templates', 'workflow_status')) {
                    $table->string('workflow_status', 32)->default('draft')->after('priority')->index();
                }
                if (! Schema::hasColumn('notification_templates', 'current_version')) {
                    $table->unsignedInteger('current_version')->default(1)->after('workflow_status');
                }
                if (! Schema::hasColumn('notification_templates', 'change_summary')) {
                    $table->string('change_summary')->nullable()->after('current_version');
                }
                if (! Schema::hasColumn('notification_templates', 'published_at')) {
                    $table->timestamp('published_at')->nullable()->after('change_summary');
                }
            });

            // Backfill existing templates as published.
            DB::table('notification_templates')->update([
                'workflow_status' => 'published',
                'published_at' => now(),
                'locale' => DB::raw("COALESCE(locale, 'en')"),
                'current_version' => DB::raw('COALESCE(NULLIF(current_version, 0), 1)'),
            ]);

            $this->replaceUniqueIndex();
        }

        if (! Schema::hasTable('notification_template_versions')) {
            Schema::create('notification_template_versions', function (Blueprint $table): void {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('notification_template_id')->constrained('notification_templates')->cascadeOnDelete();
                $table->unsignedInteger('version');
                $table->string('status', 32)->index();
                $table->string('name');
                $table->string('channel', 32);
                $table->string('locale', 16)->default('en');
                $table->string('event_key', 100)->nullable();
                $table->string('subject')->nullable();
                $table->longText('body');
                $table->json('available_variables')->nullable();
                $table->string('priority', 32)->default('normal');
                $table->json('snapshot')->nullable();
                $table->string('reason')->nullable();
                $table->boolean('is_restore')->default(false);
                $table->unsignedInteger('restored_from_version')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('created_at')->useCurrent();

                $table->unique(['notification_template_id', 'version'], 'ntv_template_version_unique');
                $table->index(['notification_template_id', 'created_at'], 'ntv_template_created_index');
            });
        } elseif (Schema::hasTable('notification_template_versions')) {
            Schema::table('notification_template_versions', function (Blueprint $table): void {
                $sm = Schema::getConnection()->getSchemaBuilder();
                $indexes = collect($sm->getIndexes('notification_template_versions'))->pluck('name')->all();
                if (! in_array('ntv_template_created_index', $indexes, true)) {
                    $table->index(['notification_template_id', 'created_at'], 'ntv_template_created_index');
                }
                if (! in_array('ntv_template_version_unique', $indexes, true)) {
                    $table->unique(['notification_template_id', 'version'], 'ntv_template_version_unique');
                }
            });
        }

        if (Schema::hasTable('notification_template_approvals')) {
            // Recover from a previous failed run that created the table without short FK names.
            $foreignKeys = collect(Schema::getForeignKeys('notification_template_approvals'))
                ->pluck('name')
                ->all();
            if (! in_array('nta_template_fk', $foreignKeys, true)) {
                Schema::drop('notification_template_approvals');
            }
        }

        if (! Schema::hasTable('notification_template_approvals')) {
            Schema::create('notification_template_approvals', function (Blueprint $table): void {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->unsignedBigInteger('notification_template_id');
                $table->unsignedBigInteger('notification_template_version_id');
                $table->string('status', 32)->index();
                $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->text('comments')->nullable();
                $table->timestamp('requested_at')->nullable();
                $table->timestamp('decided_at')->nullable();
                $table->timestamps();

                $table->foreign('notification_template_id', 'nta_template_fk')
                    ->references('id')->on('notification_templates')->cascadeOnDelete();
                $table->foreign('notification_template_version_id', 'nta_version_fk')
                    ->references('id')->on('notification_template_versions')->cascadeOnDelete();
                $table->index(['notification_template_id', 'status'], 'nta_template_status_index');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_template_approvals');
        Schema::dropIfExists('notification_template_versions');

        if (Schema::hasTable('notification_templates')) {
            Schema::table('notification_templates', function (Blueprint $table): void {
                foreach (['published_at', 'change_summary', 'current_version', 'workflow_status', 'locale'] as $column) {
                    if (Schema::hasColumn('notification_templates', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }

    private function replaceUniqueIndex(): void
    {
        try {
            Schema::table('notification_templates', function (Blueprint $table): void {
                $table->dropUnique(['event_key', 'channel', 'name']);
            });
        } catch (\Throwable) {
            // Index may already have been altered.
        }

        try {
            Schema::table('notification_templates', function (Blueprint $table): void {
                $table->unique(['event_key', 'channel', 'locale', 'name'], 'notification_templates_event_channel_locale_name_unique');
            });
        } catch (\Throwable) {
            // Unique already exists.
        }
    }
};
