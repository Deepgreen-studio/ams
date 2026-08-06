<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contents', function (Blueprint $table) {
            $table->text('summary')->nullable()->after('slug');
            $table->string('canonical_url', 500)->nullable()->after('seo_keywords');
            $table->string('body_format', 32)->default('rich')->after('body')->index();
            $table->longText('editor_json')->nullable()->after('body_format');
            $table->timestamp('last_autosaved_at')->nullable()->after('updated_by');
        });
    }

    public function down(): void
    {
        Schema::table('contents', function (Blueprint $table) {
            $table->dropColumn([
                'summary',
                'canonical_url',
                'body_format',
                'editor_json',
                'last_autosaved_at',
            ]);
        });
    }
};
