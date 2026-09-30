<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('integrations', function (Blueprint $table): void {
            $table->string('status', 32)->default('active')->change();
        });
    }

    public function down(): void
    {
        Schema::table('integrations', function (Blueprint $table): void {
            $table->string('status', 32)->default('draft')->change();
        });
    }
};
