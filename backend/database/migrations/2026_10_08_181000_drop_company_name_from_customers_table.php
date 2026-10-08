<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('customers')
            ->where(function ($query): void {
                $query->whereNull('legal_name')->orWhere('legal_name', '');
            })
            ->whereNotNull('company_name')
            ->where('company_name', '!=', '')
            ->update(['legal_name' => DB::raw('company_name')]);

        Schema::table('customers', function (Blueprint $table): void {
            $table->dropIndex(['company_id', 'company_name']);
            $table->dropColumn('company_name');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            $table->string('company_name')->nullable()->after('last_name');
            $table->index(['company_id', 'company_name']);
        });
    }
};
