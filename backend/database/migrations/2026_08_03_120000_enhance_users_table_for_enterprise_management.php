<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->after('id');
            $table->string('first_name')->nullable()->after('uuid');
            $table->string('last_name')->nullable()->after('first_name');
            $table->string('full_name')->nullable()->after('last_name');
            $table->string('phone', 30)->nullable()->after('email');
            $table->string('avatar')->nullable()->after('phone');
            $table->string('gender', 32)->nullable()->after('avatar');
            $table->date('date_of_birth')->nullable()->after('gender');
            $table->string('timezone', 64)->default('UTC')->after('date_of_birth');
            $table->string('language', 16)->default('en')->after('timezone');
            $table->string('status', 32)->default('active')->after('language');
            $table->unsignedBigInteger('created_by')->nullable()->after('last_login_ip');
            $table->unsignedBigInteger('updated_by')->nullable()->after('created_by');

            $table->unique('uuid');
            $table->unique('phone');
            $table->index('status');
            $table->index('created_by');
            $table->index('updated_by');
            $table->index(['status', 'created_at']);
            $table->index('full_name');

            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
        });

        $this->backfillExistingUsers();
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropForeign(['updated_by']);
            $table->dropUnique(['uuid']);
            $table->dropUnique(['phone']);
            $table->dropIndex(['status']);
            $table->dropIndex(['created_by']);
            $table->dropIndex(['updated_by']);
            $table->dropIndex(['status', 'created_at']);
            $table->dropIndex(['full_name']);
            $table->dropColumn([
                'uuid',
                'first_name',
                'last_name',
                'full_name',
                'phone',
                'avatar',
                'gender',
                'date_of_birth',
                'timezone',
                'language',
                'status',
                'created_by',
                'updated_by',
            ]);
        });
    }

    private function backfillExistingUsers(): void
    {
        DB::table('users')->orderBy('id')->chunkById(100, function ($users): void {
            foreach ($users as $user) {
                $parts = preg_split('/\s+/', trim((string) $user->name), 2) ?: [];
                $firstName = $parts[0] !== '' ? $parts[0] : 'User';
                $lastName = $parts[1] ?? 'Account';
                $fullName = trim($firstName.' '.$lastName);
                $status = ((bool) ($user->is_active ?? true)) ? 'active' : 'inactive';

                DB::table('users')->where('id', $user->id)->update([
                    'uuid' => (string) Str::uuid(),
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'full_name' => $fullName !== '' ? $fullName : 'User Account',
                    'status' => $status,
                    'timezone' => 'UTC',
                    'language' => 'en',
                ]);
            }
        });
    }
};
