<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('knowledge_categories', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('parent_id')->nullable()->constrained('knowledge_categories')->nullOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->string('icon', 64)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['slug']);
            $table->index(['parent_id', 'sort_order'], 'kb_cat_parent_sort_idx');
        });

        Schema::create('knowledge_tags', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('slug')->unique();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('knowledge_articles', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('knowledge_category_id')->nullable()->constrained('knowledge_categories')->nullOnDelete();
            $table->foreignId('content_id')->nullable()->constrained('contents')->nullOnDelete();
            $table->string('type', 32)->default('article')->index();
            $table->string('status', 32)->default('draft')->index();
            $table->string('title');
            $table->string('slug');
            $table->string('summary', 1000)->nullable();
            $table->longText('body')->nullable();
            $table->string('body_format', 16)->default('html');
            $table->string('video_url', 1000)->nullable();
            $table->string('featured_image', 1000)->nullable();
            $table->boolean('sync_from_cms')->default(false);
            $table->boolean('is_featured')->default(false)->index();
            $table->unsignedInteger('view_count')->default(0);
            $table->unsignedInteger('helpful_count')->default(0);
            $table->unsignedInteger('not_helpful_count')->default(0);
            $table->unsignedInteger('version')->default(1);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamp('published_at')->nullable()->index();
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['type', 'slug'], 'kb_art_type_slug_uq');
            $table->index(['knowledge_category_id', 'status'], 'kb_art_cat_status_idx');
            $table->index(['status', 'published_at'], 'kb_art_status_pub_idx');
            $table->index(['content_id'], 'kb_art_content_idx');
        });

        Schema::create('knowledge_article_tag', function (Blueprint $table) {
            $table->id();
            $table->foreignId('knowledge_article_id')->constrained('knowledge_articles')->cascadeOnDelete();
            $table->foreignId('knowledge_tag_id')->constrained('knowledge_tags')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['knowledge_article_id', 'knowledge_tag_id'], 'kb_art_tag_uq');
        });

        Schema::create('knowledge_article_versions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('knowledge_article_id')->constrained('knowledge_articles')->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->string('title');
            $table->longText('body')->nullable();
            $table->string('body_format', 16)->default('html');
            $table->string('summary', 1000)->nullable();
            $table->string('status', 32)->nullable();
            $table->json('snapshot')->nullable();
            $table->string('reason', 500)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['knowledge_article_id', 'version'], 'kb_art_ver_uq');
            $table->index(['knowledge_article_id', 'created_at'], 'kb_art_ver_created_idx');
        });

        Schema::create('knowledge_article_feedback', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('knowledge_article_id')->constrained('knowledge_articles')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_helpful');
            $table->text('comment')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->unique(['knowledge_article_id', 'user_id'], 'kb_feedback_user_uq');
            $table->index(['knowledge_article_id', 'is_helpful'], 'kb_feedback_helpful_idx');
        });

        Schema::create('knowledge_article_relations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('knowledge_article_id')->constrained('knowledge_articles')->cascadeOnDelete();
            $table->foreignId('related_article_id')->constrained('knowledge_articles')->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['knowledge_article_id', 'related_article_id'], 'kb_art_rel_uq');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('knowledge_article_relations');
        Schema::dropIfExists('knowledge_article_feedback');
        Schema::dropIfExists('knowledge_article_versions');
        Schema::dropIfExists('knowledge_article_tag');
        Schema::dropIfExists('knowledge_articles');
        Schema::dropIfExists('knowledge_tags');
        Schema::dropIfExists('knowledge_categories');
    }
};
