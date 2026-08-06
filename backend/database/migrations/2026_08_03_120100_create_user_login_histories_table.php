<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Architecture-ready login history storage.
 * Populated by future Authentication/session tracking enhancements.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_login_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('device', 120)->nullable();
            $table->string('platform', 120)->nullable();
            $table->string('browser', 120)->nullable();
            $table->string('location', 255)->nullable();
            $table->string('status', 32)->default('success');
            $table->timestamp('logged_in_at');
            $table->timestamps();

            $table->index(['user_id', 'logged_in_at']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_login_histories');
    }
};
