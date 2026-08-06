<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permission_groups', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('module')->index();
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('permissions', function (Blueprint $table) {
            $table->string('display_name')->nullable()->after('name');
            $table->string('module')->nullable()->after('display_name');
            $table->text('description')->nullable()->after('module');
            $table->foreignId('permission_group_id')
                ->nullable()
                ->after('description')
                ->constrained('permission_groups')
                ->nullOnDelete();

            $table->index('module');
            $table->index('permission_group_id');
        });
    }

    public function down(): void
    {
        Schema::table('permissions', function (Blueprint $table) {
            $table->dropForeign(['permission_group_id']);
            $table->dropIndex(['module']);
            $table->dropIndex(['permission_group_id']);
            $table->dropColumn([
                'display_name',
                'module',
                'description',
                'permission_group_id',
            ]);
        });

        Schema::dropIfExists('permission_groups');
    }
};
