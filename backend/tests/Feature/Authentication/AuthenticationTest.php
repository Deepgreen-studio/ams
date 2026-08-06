<?php

namespace Tests\Feature\Authentication;

use App\Domains\Authentication\Notifications\PasswordResetNotification;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->withHeaders([
            'Origin' => 'http://localhost:5173',
            'Referer' => 'http://localhost:5173/',
        ]);
    }

    public function test_user_can_login_and_receives_token(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => Hash::make('Password@123'),
            'is_active' => true,
        ]);
        $user->assignRole('super-admin');

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'admin@example.com',
            'password' => 'Password@123',
            'remember' => true,
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Login successful.')
            ->assertJsonPath('data.user.email', 'admin@example.com')
            ->assertJsonStructure(['data' => ['token']]);

        $this->assertNotEmpty($response->json('data.token'));
        $this->assertAuthenticated('web');
    }

    public function test_login_fails_with_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'admin@example.com',
            'password' => Hash::make('Password@123'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'admin@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);

        $this->assertGuest('web');
    }

    public function test_login_validation_requires_email_and_password(): void
    {
        $response = $this->postJson('/api/v1/auth/login', []);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Validation Failed')
            ->assertJsonStructure(['errors' => ['email', 'password']]);
    }

    public function test_inactive_user_cannot_login(): void
    {
        User::factory()->create([
            'email' => 'inactive@example.com',
            'password' => Hash::make('Password@123'),
            'status' => 'inactive',
            'is_active' => false,
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'inactive@example.com',
            'password' => 'Password@123',
        ]);

        $response->assertStatus(403)
            ->assertJsonPath('success', false);
    }

    public function test_authenticated_user_can_fetch_profile(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('admin');

        $this->actingAs($user);

        $response = $this->getJson('/api/v1/auth/me');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.email', $user->email);
    }

    public function test_user_can_logout(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
            'password' => Hash::make('Password@123'),
        ]);

        $login = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'Password@123',
        ]);

        $login->assertOk();

        $response = $this->postJson('/api/v1/auth/logout');

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertGuest('web');
    }

    public function test_user_can_refresh_session(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $this->actingAs($user);

        $response = $this->postJson('/api/v1/auth/refresh', [
            'rotate_token' => false,
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.email', $user->email);
    }

    public function test_forgot_password_does_not_enumerate_users(): void
    {
        Notification::fake();

        $response = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'missing@example.com',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_user_can_request_password_reset(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $response = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => $user->email,
        ]);

        $response->assertOk();

        Notification::assertSentTo($user, PasswordResetNotification::class);
    }

    public function test_user_can_reset_password(): void
    {
        $user = User::factory()->create();
        $token = Password::broker()->createToken($user);

        $response = $this->postJson('/api/v1/auth/reset-password', [
            'email' => $user->email,
            'token' => $token,
            'password' => 'NewPass@123',
            'password_confirmation' => 'NewPass@123',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertTrue(Hash::check('NewPass@123', $user->fresh()->password));
    }

    public function test_user_can_change_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('Password@123'),
        ]);

        $this->actingAs($user);

        $response = $this->postJson('/api/v1/auth/change-password', [
            'current_password' => 'Password@123',
            'password' => 'Changed@1234',
            'password_confirmation' => 'Changed@1234',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertTrue(Hash::check('Changed@1234', $user->fresh()->password));
    }

    public function test_change_password_rejects_invalid_current_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('Password@123'),
        ]);

        $this->actingAs($user);

        $response = $this->postJson('/api/v1/auth/change-password', [
            'current_password' => 'Wrong@123',
            'password' => 'Changed@1234',
            'password_confirmation' => 'Changed@1234',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_user_can_verify_email_with_signed_url(): void
    {
        $user = User::factory()->unverified()->create();

        $url = URL::temporarySignedRoute(
            'auth.verification.verify',
            now()->addMinutes(60),
            [
                'id' => $user->id,
                'hash' => sha1($user->email),
            ]
        );

        $response = $this->getJson($url);

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }

    public function test_user_can_request_verification_email(): void
    {
        Notification::fake();

        $user = User::factory()->unverified()->create();
        $this->actingAs($user);

        $response = $this->postJson('/api/v1/auth/email/verification-notification');

        $response->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_logout_all_devices_revokes_tokens(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('Password@123'),
        ]);

        $login = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'Password@123',
        ]);

        $login->assertOk();
        $this->assertDatabaseCount('personal_access_tokens', 1);

        $response = $this->postJson('/api/v1/auth/logout-all');

        $response->assertOk();
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}
