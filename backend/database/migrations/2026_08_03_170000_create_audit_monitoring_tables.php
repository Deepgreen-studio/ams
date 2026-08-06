<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_login_histories', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->unique()->after('id');
            $table->timestamp('logout_at')->nullable()->after('logged_in_at');
            $table->string('operating_system', 120)->nullable()->after('platform');
            $table->string('country', 100)->nullable()->after('location');
            $table->string('city', 100)->nullable()->after('country');
            $table->string('session_id', 120)->nullable()->index()->after('status');
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->string('module', 64)->index();
            $table->string('action', 64)->index();
            $table->nullableMorphs('subject');
            $table->json('before_data')->nullable();
            $table->json('after_data')->nullable();
            $table->json('changed_fields')->nullable();
            $table->string('reason')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index(['module', 'action']);
            $table->index(['user_id', 'created_at']);
            $table->index('created_at');
        });

        Schema::create('api_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('endpoint', 500);
            $table->string('method', 16)->index();
            $table->json('request')->nullable();
            $table->json('response')->nullable();
            $table->unsignedSmallInteger('response_code')->nullable()->index();
            $table->unsignedInteger('duration')->default(0);
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index('created_at');
            $table->index(['user_id', 'created_at']);
            $table->index(['endpoint', 'method']);
        });

        Schema::create('system_events', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('event', 120)->index();
            $table->string('module', 64)->index();
            $table->string('level', 32)->default('info')->index();
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->index('created_at');
        });

        Schema::create('error_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('exception', 255)->nullable();
            $table->text('message');
            $table->string('file')->nullable();
            $table->unsignedInteger('line')->nullable();
            $table->longText('stack_trace')->nullable();
            $table->string('url', 500)->nullable();
            $table->string('method', 16)->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->json('context')->nullable();
            $table->timestamps();

            $table->index('created_at');
            $table->index(['exception', 'created_at']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('error_logs');
        Schema::dropIfExists('system_events');
        Schema::dropIfExists('api_logs');
        Schema::dropIfExists('audit_logs');

        Schema::table('user_login_histories', function (Blueprint $table) {
            $table->dropColumn([
                'uuid',
                'logout_at',
                'operating_system',
                'country',
                'city',
                'session_id',
            ]);
        });
    }
};
