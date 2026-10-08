<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            $table->dropIndex(['country']);
            $table->dropColumn(['country', 'timezone', 'language']);
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            $table->string('country', 100)->nullable();
            $table->string('timezone', 64)->default('UTC');
            $table->string('language', 16)->default('en');
            $table->index('country');
        });
    }
};
