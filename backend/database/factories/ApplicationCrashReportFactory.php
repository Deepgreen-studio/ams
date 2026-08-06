<?php

namespace Database\Factories;

use App\Domains\Applications\Enums\ApplicationCrashSeverity;
use App\Domains\Applications\Enums\ApplicationCrashStatus;
use App\Domains\Applications\Enums\ApplicationCrashType;
use App\Domains\Applications\Models\Application;
use App\Domains\Applications\Models\ApplicationCrashReport;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<ApplicationCrashReport> */
class ApplicationCrashReportFactory extends Factory
{
    protected $model = ApplicationCrashReport::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'type' => ApplicationCrashType::Crash->value,
            'severity' => ApplicationCrashSeverity::Error->value,
            'status' => ApplicationCrashStatus::Open->value,
            'title' => 'NullPointerException in CheckoutActivity',
            'message' => fake()->sentence(),
            'stack_trace' => "java.lang.NullPointerException\n\tat com.example.CheckoutActivity.onCreate",
            'crash_log' => fake()->paragraph(),
            'fingerprint' => Str::limit(sha1('checkout-npe'), 32, ''),
            'occurrence_count' => 1,
            'device_model' => 'Pixel 8',
            'device_manufacturer' => 'Google',
            'device_os' => 'Android',
            'device_os_version' => '14',
            'occurred_at' => now(),
            'memory_usage_mb' => 256.5,
            'battery_level' => 72.0,
        ];
    }

    public function forApplication(Application $application): static
    {
        return $this->state(fn (): array => [
            'application_id' => $application->id,
        ]);
    }
}
