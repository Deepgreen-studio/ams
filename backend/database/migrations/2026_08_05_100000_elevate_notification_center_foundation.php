<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('notifications') && ! Schema::hasTable('database_notifications')) {
            Schema::rename('notifications', 'database_notifications');
        }

        if (! Schema::hasTable('notifications')) {
            Schema::create('notifications', function (Blueprint $table): void {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('channel', 32)->index();
                $table->string('template')->nullable();
                $table->string('event_key', 100)->nullable()->index();
                $table->string('title');
                $table->text('message')->nullable();
                $table->string('status', 32)->default('queued')->index();
                $table->string('priority', 32)->default('normal')->index();
                $table->uuid('laravel_notification_id')->nullable()->index();
                $table->foreignId('template_id')->nullable()->constrained('notification_templates')->nullOnDelete();
                $table->json('data')->nullable();
                $table->timestamp('scheduled_at')->nullable()->index();
                $table->timestamp('sent_at')->nullable()->index();
                $table->timestamp('read_at')->nullable()->index();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->softDeletes();
                $table->timestamps();

                $table->index(['user_id', 'status']);
                $table->index(['user_id', 'read_at']);
                $table->index(['company_id', 'channel']);
                $table->index(['created_at']);
            });
        }

        if (! Schema::hasTable('notification_channels')) {
            Schema::create('notification_channels', function (Blueprint $table): void {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->string('key', 32)->unique();
                $table->string('name');
                $table->text('description')->nullable();
                $table->boolean('is_enabled')->default(false)->index();
                $table->boolean('is_implemented')->default(false)->index();
                $table->boolean('is_system')->default(true);
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->json('config')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (Schema::hasTable('notification_delivery_logs') && ! Schema::hasTable('notification_logs')) {
            Schema::rename('notification_delivery_logs', 'notification_logs');
        }

        if (Schema::hasTable('notification_logs')) {
            Schema::table('notification_logs', function (Blueprint $table): void {
                if (! Schema::hasColumn('notification_logs', 'notification_id')) {
                    $table->foreignId('notification_id')->nullable()->after('id')->constrained('notifications')->nullOnDelete();
                }
                if (! Schema::hasColumn('notification_logs', 'company_id')) {
                    $table->foreignId('company_id')->nullable()->after('notification_id')->constrained('companies')->nullOnDelete();
                }
            });
        }

        if (Schema::hasTable('notification_preferences')) {
            Schema::table('notification_preferences', function (Blueprint $table): void {
                if (! Schema::hasColumn('notification_preferences', 'company_id')) {
                    $table->foreignId('company_id')->nullable()->after('uuid')->constrained('companies')->nullOnDelete();
                }
                if (! Schema::hasColumn('notification_preferences', 'whatsapp_enabled')) {
                    $table->boolean('whatsapp_enabled')->default(false)->after('push_enabled');
                }
                if (! Schema::hasColumn('notification_preferences', 'slack_enabled')) {
                    $table->boolean('slack_enabled')->default(false)->after('whatsapp_enabled');
                }
                if (! Schema::hasColumn('notification_preferences', 'teams_enabled')) {
                    $table->boolean('teams_enabled')->default(false)->after('slack_enabled');
                }
                if (! Schema::hasColumn('notification_preferences', 'webhook_enabled')) {
                    $table->boolean('webhook_enabled')->default(false)->after('teams_enabled');
                }
            });
        }

        if (Schema::hasTable('notification_templates')) {
            Schema::table('notification_templates', function (Blueprint $table): void {
                if (! Schema::hasColumn('notification_templates', 'company_id')) {
                    $table->foreignId('company_id')->nullable()->after('uuid')->constrained('companies')->nullOnDelete();
                }
                if (! Schema::hasColumn('notification_templates', 'priority')) {
                    $table->string('priority', 32)->default('normal')->after('is_system');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('notification_templates')) {
            Schema::table('notification_templates', function (Blueprint $table): void {
                if (Schema::hasColumn('notification_templates', 'priority')) {
                    $table->dropColumn('priority');
                }
                if (Schema::hasColumn('notification_templates', 'company_id')) {
                    $table->dropConstrainedForeignId('company_id');
                }
            });
        }

        if (Schema::hasTable('notification_preferences')) {
            Schema::table('notification_preferences', function (Blueprint $table): void {
                foreach (['webhook_enabled', 'teams_enabled', 'slack_enabled', 'whatsapp_enabled'] as $column) {
                    if (Schema::hasColumn('notification_preferences', $column)) {
                        $table->dropColumn($column);
                    }
                }
                if (Schema::hasColumn('notification_preferences', 'company_id')) {
                    $table->dropConstrainedForeignId('company_id');
                }
            });
        }

        if (Schema::hasTable('notification_logs')) {
            Schema::table('notification_logs', function (Blueprint $table): void {
                if (Schema::hasColumn('notification_logs', 'company_id')) {
                    $table->dropConstrainedForeignId('company_id');
                }
                if (Schema::hasColumn('notification_logs', 'notification_id')) {
                    $table->dropConstrainedForeignId('notification_id');
                }
            });

            if (! Schema::hasTable('notification_delivery_logs')) {
                Schema::rename('notification_logs', 'notification_delivery_logs');
            }
        }

        Schema::dropIfExists('notification_channels');
        Schema::dropIfExists('notifications');

        if (Schema::hasTable('database_notifications') && ! Schema::hasTable('notifications')) {
            Schema::rename('database_notifications', 'notifications');
        }
    }
};
