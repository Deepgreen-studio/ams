<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->unique()->after('id');
            $table->string('display_name')->nullable()->after('name');
            $table->text('description')->nullable()->after('display_name');
            $table->boolean('is_system')->default(false)->after('description');
            $table->softDeletes();
            $table->index('is_system');
        });

        \Illuminate\Support\Facades\DB::table('roles')->orderBy('id')->chunkById(100, function ($roles): void {
            foreach ($roles as $role) {
                \Illuminate\Support\Facades\DB::table('roles')->where('id', $role->id)->update([
                    'uuid' => (string) \Illuminate\Support\Str::uuid(),
                    'display_name' => $role->display_name ?: \Illuminate\Support\Str::of($role->name)->replace(['-', '_'], ' ')->title()->toString(),
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropUnique(['uuid']);
            $table->dropIndex(['is_system']);
            $table->dropColumn(['uuid', 'display_name', 'description', 'is_system']);
        });
    }
};
