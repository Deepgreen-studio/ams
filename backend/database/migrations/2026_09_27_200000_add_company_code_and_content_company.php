<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table): void {
            $table->string('company_code', 100)->nullable()->after('company_name');
            $table->index('company_code');
            $table->index('email');
            $table->index('registration_number');
        });

        $seen = [];
        foreach (DB::table('companies')->orderBy('id')->get() as $company) {
            $base = strtoupper(trim((string) ($company->registration_number ?: 'CO-'.$company->id)));
            $base = $base !== '' ? $base : 'CO-'.$company->id;
            $code = isset($seen[$base]) ? $base.'-'.$company->id : $base;
            $seen[$code] = true;
            DB::table('companies')->where('id', $company->id)->update(['company_code' => $code]);
        }

        Schema::table('contents', function (Blueprint $table): void {
            $table->foreignId('company_id')->nullable()->after('uuid')->constrained('companies')->nullOnDelete();
            $table->index(['company_id', 'content_status_id']);
        });
    }

    public function down(): void
    {
        Schema::table('contents', function (Blueprint $table): void {
            $table->dropIndex(['company_id', 'content_status_id']);
            $table->dropConstrainedForeignId('company_id');
        });

        Schema::table('companies', function (Blueprint $table): void {
            $table->dropIndex(['company_code']);
            $table->dropIndex(['email']);
            $table->dropIndex(['registration_number']);
            $table->dropColumn('company_code');
        });
    }
};
