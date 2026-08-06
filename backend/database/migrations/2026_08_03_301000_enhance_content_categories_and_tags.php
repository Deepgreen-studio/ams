<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('content_categories', function (Blueprint $table) {
            $table->string('seo_title')->nullable()->after('description');
            $table->string('seo_description', 512)->nullable()->after('seo_title');
        });

        Schema::table('content_tags', function (Blueprint $table) {
            $table->text('description')->nullable()->after('slug');
            $table->string('seo_title')->nullable()->after('description');
            $table->string('seo_description', 512)->nullable()->after('seo_title');
            $table->boolean('is_active')->default(true)->after('seo_description')->index();
            $table->unsignedInteger('sort_order')->default(0)->after('is_active')->index();
        });

        Schema::create('content_category', function (Blueprint $table) {
            $table->id();
            $table->foreignId('content_id')->constrained('contents')->cascadeOnDelete();
            $table->foreignId('content_category_id')->constrained('content_categories')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['content_id', 'content_category_id']);
            $table->index('content_category_id');
        });

        if (Schema::hasTable('content_content_tag') && ! Schema::hasTable('content_tag')) {
            Schema::rename('content_content_tag', 'content_tag');
        }

        $rows = DB::table('contents')
            ->whereNotNull('content_category_id')
            ->select(['id', 'content_category_id', 'created_at', 'updated_at'])
            ->get();

        foreach ($rows as $row) {
            DB::table('content_category')->updateOrInsert(
                [
                    'content_id' => $row->id,
                    'content_category_id' => $row->content_category_id,
                ],
                [
                    'created_at' => $row->created_at ?? now(),
                    'updated_at' => $row->updated_at ?? now(),
                ]
            );
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('content_tag') && ! Schema::hasTable('content_content_tag')) {
            Schema::rename('content_tag', 'content_content_tag');
        }

        Schema::dropIfExists('content_category');

        Schema::table('content_tags', function (Blueprint $table) {
            $table->dropColumn([
                'description',
                'seo_title',
                'seo_description',
                'is_active',
                'sort_order',
            ]);
        });

        Schema::table('content_categories', function (Blueprint $table) {
            $table->dropColumn(['seo_title', 'seo_description']);
        });
    }
};
