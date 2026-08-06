<?php

namespace Database\Factories;

use App\Domains\Users\Enums\UserGender;
use App\Domains\Users\Enums\UserStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $firstName = fake()->firstName();
        $lastName = fake()->lastName();
        $fullName = trim($firstName.' '.$lastName);

        return [
            'uuid' => (string) Str::uuid(),
            'first_name' => $firstName,
            'last_name' => $lastName,
            'full_name' => $fullName,
            'name' => $fullName,
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->unique()->numerify('+1555#######'),
            'avatar' => null,
            'gender' => fake()->randomElement(UserGender::values()),
            'date_of_birth' => fake()->optional()->date(),
            'timezone' => 'UTC',
            'language' => 'en',
            'status' => UserStatus::Active->value,
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'is_active' => true,
            'created_by' => null,
            'updated_by' => null,
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => UserStatus::Inactive->value,
            'is_active' => false,
        ]);
    }

    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => UserStatus::Suspended->value,
            'is_active' => false,
        ]);
    }
}
