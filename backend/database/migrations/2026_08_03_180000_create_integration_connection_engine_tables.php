<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('integrations', function (Blueprint $table) {
            $table->json('default_headers')->nullable()->after('retry_attempts');
            $table->json('default_query')->nullable()->after('default_headers');
            $table->unsignedInteger('rate_limit_per_minute')->nullable()->after('default_query');
            $table->string('health_check_path')->nullable()->after('rate_limit_per_minute');
            $table->text('credentials')->nullable()->after('health_check_path');
        });

        Schema::create('integration_connection_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('integration_id')->constrained('integrations')->cascadeOnDelete();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('request_type', 40)->index();
            $table->string('method', 10);
            $table->text('url');
            $table->json('request_headers')->nullable();
            $table->json('request_query')->nullable();
            $table->longText('request_body')->nullable();
            $table->unsignedSmallInteger('response_status')->nullable()->index();
            $table->json('response_headers')->nullable();
            $table->longText('response_body')->nullable();
            $table->unsignedInteger('duration_ms')->default(0);
            $table->unsignedTinyInteger('attempts')->default(1);
            $table->boolean('success')->default(false)->index();
            $table->text('error_message')->nullable();
            $table->foreignId('triggered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['integration_id', 'created_at']);
            $table->index(['company_id', 'created_at']);
            $table->index(['integration_id', 'request_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_connection_logs');

        Schema::table('integrations', function (Blueprint $table) {
            $table->dropColumn([
                'default_headers',
                'default_query',
                'rate_limit_per_minute',
                'health_check_path',
                'credentials',
            ]);
        });
    }
};
