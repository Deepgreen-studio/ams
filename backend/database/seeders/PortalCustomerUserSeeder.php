<?php

namespace Database\Seeders;

use App\Domains\Companies\Models\Company;
use App\Domains\Customers\Enums\CustomerStatus;
use App\Domains\Customers\Enums\CustomerType;
use App\Domains\Customers\Models\Customer;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class PortalCustomerUserSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::query()->firstOrCreate(
            ['company_name' => 'Portal Demo Company'],
            [
                'status' => 'active',
                'timezone' => 'UTC',
                'language' => 'en',
                'currency' => 'USD',
            ]
        );

        $customer = Customer::query()->firstOrCreate(
            ['email' => 'portal.customer@example.com'],
            [
                'company_id' => $company->id,
                'customer_type' => CustomerType::Individual->value,
                'first_name' => 'Portal',
                'last_name' => 'Customer',
                'status' => CustomerStatus::Active->value,
                'timezone' => 'UTC',
                'language' => 'en',
            ]
        );

        $user = User::query()->updateOrCreate(
            ['email' => 'portal.customer@example.com'],
            [
                'first_name' => 'Portal',
                'last_name' => 'Customer',
                'password' => Hash::make('Password123!'),
                'is_active' => true,
                'customer_id' => $customer->id,
                'email_verified_at' => now(),
            ]
        );

        $user->syncRoles(['customer']);
    }
}
