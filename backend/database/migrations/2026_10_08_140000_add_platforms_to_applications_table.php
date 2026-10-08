<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table): void {
            $table->json('platforms')->nullable()->after('platform');
        });

        DB::table('applications')
            ->select(['id', 'platform'])
            ->orderBy('id')
            ->chunkById(200, function ($rows): void {
                foreach ($rows as $row) {
                    if (! filled($row->platform)) {
                        continue;
                    }

                    DB::table('applications')
                        ->where('id', $row->id)
                        ->update([
                            'platforms' => json_encode([(string) $row->platform]),
                        ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table): void {
            $table->dropColumn('platforms');
        });
    }
};
