<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contents', function (Blueprint $table) {
            $table->unsignedInteger('version')->default(1)->after('sort_order')->index();
        });

        Schema::create('content_versions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('content_id')->constrained('contents')->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->string('status', 32)->index();
            $table->json('snapshot');
            $table->string('reason')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['content_id', 'version']);
            $table->index(['content_id', 'created_at']);
            $table->index('created_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_versions');

        Schema::table('contents', function (Blueprint $table) {
            $table->dropColumn('version');
        });
    }
};
