<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table): void {
            if (! Schema::hasColumn('notifications', 'clicked_at')) {
                $table->timestamp('clicked_at')->nullable()->after('read_at');
                $table->unsignedInteger('click_count')->default(0)->after('clicked_at');
                $table->index('clicked_at', 'notifications_clicked_at_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table): void {
            if (Schema::hasColumn('notifications', 'clicked_at')) {
                $table->dropIndex('notifications_clicked_at_idx');
                $table->dropColumn(['clicked_at', 'click_count']);
            }
        });
    }
};
