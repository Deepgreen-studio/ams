<?php

use App\Domains\Customers\Support\IndustryCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('industries', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('parent_id')->nullable()->constrained('industries')->nullOnDelete();
            $table->string('code', 64)->unique();
            $table->string('name');
            $table->boolean('is_other')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['parent_id', 'is_active']);
        });

        IndustryCatalog::sync();

        Schema::table('customers', function (Blueprint $table) {
            $table->string('customer_number', 32)->nullable()->unique()->after('uuid');
            $table->string('reference', 64)->nullable()->after('customer_number');
            $table->string('legal_name')->nullable()->after('company_name');
            $table->string('registration_number', 64)->nullable()->after('legal_name');
            $table->foreignId('industry_id')->nullable()->after('industry')->constrained('industries')->nullOnDelete();
            $table->foreignId('sub_industry_id')->nullable()->after('industry_id')->constrained('industries')->nullOnDelete();
            $table->string('industry_other', 120)->nullable()->after('sub_industry_id');
            $table->string('primary_contact_name')->nullable()->after('phone');
            $table->string('primary_contact_email')->nullable()->after('primary_contact_name');
            $table->string('primary_contact_phone', 30)->nullable()->after('primary_contact_email');
            $table->string('primary_contact_title', 120)->nullable()->after('primary_contact_phone');
            $table->string('legal_basis', 64)->nullable()->after('language');
            $table->string('processing_purpose', 500)->nullable()->after('legal_basis');
            $table->date('retention_until')->nullable()->after('processing_purpose');
            $table->timestamp('anonymized_at')->nullable()->after('retention_until');
            $table->unique(['company_id', 'reference'], 'customers_company_reference_unique');
        });

        Schema::table('customer_contacts', function (Blueprint $table) {
            $table->json('responsibilities')->nullable()->after('contact_type');
        });

        Schema::table('customer_applications', function (Blueprint $table) {
            $table->string('assignment_number', 32)->nullable()->unique()->after('uuid');
            $table->string('platform', 32)->nullable()->after('application_id');
            $table->foreignId('application_version_id')->nullable()->after('application_environment_id')->constrained('application_versions')->nullOnDelete();
            $table->string('build_label', 64)->nullable()->after('application_version_id');
            $table->foreignId('application_release_id')->nullable()->after('build_label')->constrained('application_releases')->nullOnDelete();
            $table->foreignId('support_sla_policy_id')->nullable()->after('integration_id')->constrained('support_sla_policies')->nullOnDelete();
        });

        Schema::table('support_tickets', function (Blueprint $table) {
            $table->foreignId('customer_application_id')->nullable()->after('application_id')->constrained('customer_applications')->nullOnDelete();
            $table->index(['customer_id', 'customer_application_id'], 'support_tickets_customer_assignment_idx');
        });

        DB::table('customers')->orderBy('id')->chunkById(200, function ($rows): void {
            foreach ($rows as $row) {
                DB::table('customers')->where('id', $row->id)->update([
                    'customer_number' => sprintf('CUS-%08d', $row->id),
                ]);
            }
        });

        DB::table('customer_applications')->orderBy('id')->chunkById(200, function ($rows): void {
            foreach ($rows as $row) {
                DB::table('customer_applications')->where('id', $row->id)->update([
                    'assignment_number' => sprintf('ASN-%08d', $row->id),
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('support_tickets', function (Blueprint $table) {
            $table->dropIndex('support_tickets_customer_assignment_idx');
            $table->dropConstrainedForeignId('customer_application_id');
        });

        Schema::table('customer_applications', function (Blueprint $table) {
            $table->dropConstrainedForeignId('support_sla_policy_id');
            $table->dropConstrainedForeignId('application_release_id');
            $table->dropColumn('build_label');
            $table->dropConstrainedForeignId('application_version_id');
            $table->dropColumn('platform');
            $table->dropUnique(['assignment_number']);
            $table->dropColumn('assignment_number');
        });

        Schema::table('customer_contacts', function (Blueprint $table) {
            $table->dropColumn('responsibilities');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropUnique('customers_company_reference_unique');
            $table->dropConstrainedForeignId('sub_industry_id');
            $table->dropConstrainedForeignId('industry_id');
            $table->dropColumn([
                'customer_number',
                'reference',
                'legal_name',
                'registration_number',
                'industry_other',
                'primary_contact_name',
                'primary_contact_email',
                'primary_contact_phone',
                'primary_contact_title',
                'legal_basis',
                'processing_purpose',
                'retention_until',
                'anonymized_at',
            ]);
        });

        Schema::dropIfExists('industries');
    }
};
