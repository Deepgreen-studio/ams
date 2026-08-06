<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contents', function (Blueprint $table) {
            $table->string('og_title')->nullable()->after('canonical_url');
            $table->string('og_description', 512)->nullable()->after('og_title');
            $table->string('og_image')->nullable()->after('og_description');
            $table->string('twitter_card', 64)->nullable()->after('og_image');
            $table->string('twitter_title')->nullable()->after('twitter_card');
            $table->string('twitter_description', 512)->nullable()->after('twitter_title');
            $table->string('twitter_image')->nullable()->after('twitter_description');
            $table->string('schema_type', 100)->nullable()->after('twitter_image');
            $table->json('schema_json')->nullable()->after('schema_type');
            $table->unsignedBigInteger('view_count')->default(0)->after('is_featured');
            $table->timestamp('last_viewed_at')->nullable()->after('view_count');

            $table->index('view_count');
            $table->index('last_viewed_at');
        });

        Schema::create('cms_api_keys', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('key_prefix', 32)->index();
            $table->string('key_hash', 64)->unique();
            $table->json('abilities')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->index('created_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cms_api_keys');

        Schema::table('contents', function (Blueprint $table) {
            $table->dropIndex(['view_count']);
            $table->dropIndex(['last_viewed_at']);
            $table->dropColumn([
                'og_title',
                'og_description',
                'og_image',
                'twitter_card',
                'twitter_title',
                'twitter_description',
                'twitter_image',
                'schema_type',
                'schema_json',
                'view_count',
                'last_viewed_at',
            ]);
        });
    }
};
