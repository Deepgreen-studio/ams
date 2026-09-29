<?php

namespace App\Domains\Authentication\Services;

use App\Domains\Authentication\Events\EmailVerified;
use App\Domains\Authentication\Events\PasswordChanged;
use App\Domains\Authentication\Events\PasswordResetCompleted;
use App\Domains\Authentication\Events\PasswordResetRequested;
use App\Domains\Authentication\Events\UserLoggedIn;
use App\Domains\Authentication\Events\UserLoggedOut;
use App\Domains\Authentication\Repositories\AuthenticationRepository;
use App\Domains\Audit\Services\LoginHistoryService;
use App\Domains\Users\Enums\InvitationStatus;
use App\Domains\Users\Enums\UserStatus;
use App\Domains\Users\Repositories\UserRepository;
use App\Models\User;
use App\Shared\Exceptions\ApiException;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthenticationService
{
    public function __construct(
        private readonly AuthenticationRepository $repository,
        private readonly LoginHistoryService $loginHistoryService,
        private readonly TwoFactorService $twoFactorService,
        private readonly UserRepository $userRepository,
        private readonly AssignedCompanyAccess $assignedCompanyAccess,
    ) {}

    /**
     * @return array{user: User, token: string}
     */
    public function login(string $email, string $password, bool $remember, Request $request): array
    {
        $user = $this->repository->findByEmail($email);

        if (! $user || ! $this->repository->credentialsAreValid($user, $password)) {
            $this->loginHistoryService->recordFailedLogin($user, $request);

            throw ValidationException::withMessages([
                'email' => ['Invalid credentials.'],
            ]);
        }

        $this->userRepository->expireStaleInvitations();
        $user->refresh();

        if (! $user->isAccountActive()) {
            $this->loginHistoryService->recordFailedLogin($user, $request);

            throw new ApiException($this->inactiveAccountMessage($user), 403);
        }

        $this->assertAssignedCompanyIsActive($user, $request);

        if ($user->hasConfirmedTwoFactor()) {
            $challenge = Str::random(64);
            Cache::put($this->challengeKey($challenge), [
                'user_id' => $user->id,
                'remember' => $remember,
            ], now()->addMinutes(5));

            return [
                'mfa_required' => true,
                'challenge' => $challenge,
                'user' => $user,
                'token' => null,
            ];
        }

        return $this->issueSession($user, $remember, $request);
    }

    /**
     * @return array{user: User, token: string, mfa_required: bool}
     */
    public function verifyTwoFactor(string $challenge, string $code, Request $request): array
    {
        $payload = Cache::get($this->challengeKey($challenge));

        if (! is_array($payload) || empty($payload['user_id'])) {
            throw ValidationException::withMessages([
                'code' => ['This sign-in challenge has expired. Sign in again.'],
            ]);
        }

        $user = User::query()->find($payload['user_id']);

        if (! $user || ! $user->hasConfirmedTwoFactor() || ! $this->twoFactorService->verifyLoginCode($user, $code)) {
            throw ValidationException::withMessages([
                'code' => ['The authentication code is invalid.'],
            ]);
        }

        Cache::forget($this->challengeKey($challenge));

        if (! $user->isAccountActive()) {
            throw new ApiException($this->inactiveAccountMessage($user), 403);
        }

        $this->assertAssignedCompanyIsActive($user, $request);

        return $this->issueSession($user, (bool) ($payload['remember'] ?? false), $request);
    }

    /**
     * @return array{user: User, token: string, mfa_required: bool}
     */
    protected function issueSession(User $user, bool $remember, Request $request): array
    {
        Auth::guard('web')->login($user, $remember);

        if ($request->hasSession()) {
            $request->session()->regenerate();
        }

        $this->repository->updateLastLogin($user, $request->ip());
        $this->repository->revokeAllTokens($user);
        $token = $this->repository->createAccessToken($user, 'web');

        event(new UserLoggedIn($user, $request));

        return [
            'mfa_required' => false,
            'challenge' => null,
            'user' => $this->repository->loadAuthRelations($user),
            'token' => $token,
        ];
    }

    public function logout(Request $request): void
    {
        /** @var User|null $user */
        $user = $request->user();

        if ($user) {
            event(new UserLoggedOut($user, $request));
            $this->repository->revokeCurrentToken($user);
        }

        Auth::guard('web')->logout();

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }
    }

    /**
     * Prepare architecture for force logout across all devices/sessions.
     */
    public function logoutAllDevices(Request $request): void
    {
        /** @var User $user */
        $user = $request->user();

        $this->repository->revokeAllTokens($user);

        Auth::guard('web')->logout();

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        event(new UserLoggedOut($user, $request));
    }

    public function currentUser(Request $request): User
    {
        /** @var User $user */
        $user = $request->user();

        return $this->repository->loadAuthRelations($user);
    }

    /**
     * Refresh authenticated session metadata and optionally rotate API token.
     *
     * @return array{user: User, token: string|null}
     */
    public function refreshSession(Request $request): array
    {
        /** @var User $user */
        $user = $request->user();

        if ($request->hasSession()) {
            $request->session()->regenerate();
        }

        $token = null;

        if ($request->boolean('rotate_token')) {
            $this->repository->revokeCurrentToken($user);
            $token = $this->repository->createAccessToken($user, 'web');
        }

        return [
            'user' => $this->repository->loadAuthRelations($user->fresh()),
            'token' => $token,
        ];
    }

    public function forgotPassword(string $email): void
    {
        $account = User::query()->where('email', $email)->first();
        if ($account?->isProtectedAccount()) {
            return;
        }

        event(new PasswordResetRequested($email));

        // Always attempt send; response stays generic to prevent user enumeration.
        Password::broker()->sendResetLink(['email' => $email]);
    }

    /**
     * @param array{email: string, password: string, password_confirmation: string, token: string} $data
     */
    public function resetPassword(array $data): void
    {
        $account = User::query()->where('email', $data['email'])->first();
        if ($account?->isProtectedAccount()) {
            throw ValidationException::withMessages([
                'email' => ['This account password cannot be changed.'],
            ]);
        }

        $status = Password::broker()->reset(
            $data,
            function (User $user, string $password): void {
                $this->repository->updatePassword($user, $password);
                $this->repository->markEmailAsVerified($user);
                $fill = ['remember_token' => Str::random(60)];

                if ($user->invitation_status !== InvitationStatus::Accepted) {
                    $fill['invitation_status'] = InvitationStatus::Accepted->value;
                    $fill['status'] = UserStatus::Active->value;
                    $fill['is_active'] = true;
                    $fill['invitation_expires_at'] = null;
                }

                $user->forceFill($fill)->save();
                $this->repository->revokeAllTokens($user);

                event(new PasswordResetCompleted($user));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => ['Unable to reset password with the provided details.'],
            ]);
        }
    }

    public function changePassword(User $user, string $currentPassword, string $newPassword): User
    {
        if ($user->isProtectedAccount()) {
            throw new ApiException('This account password cannot be changed.', 422);
        }

        if (! $this->repository->credentialsAreValid($user, $currentPassword)) {
            throw ValidationException::withMessages([
                'current_password' => ['The current password is incorrect.'],
            ]);
        }

        $this->repository->updatePassword($user, $newPassword);
        $this->repository->revokeAllTokens($user);

        event(new PasswordChanged($user));

        return $this->repository->loadAuthRelations($user->fresh());
    }

    public function sendEmailVerification(User $user): void
    {
        if ($user->hasVerifiedEmail()) {
            throw new ApiException('Email address is already verified.', 422);
        }

        $user->sendEmailVerificationNotification();
    }

    public function verifyEmail(User $user, string $hash): User
    {
        if (! hash_equals($hash, sha1($user->getEmailForVerification()))) {
            throw new ApiException('Invalid email verification link.', 403);
        }

        if (! $user->hasVerifiedEmail()) {
            $this->repository->markEmailAsVerified($user);
            event(new Verified($user));
            event(new EmailVerified($user));
        }

        return $this->repository->loadAuthRelations($user->fresh());
    }

    protected function assertAssignedCompanyIsActive(User $user, Request $request): void
    {
        $message = $this->assignedCompanyAccess->blockMessage($user);

        if ($message === null) {
            return;
        }

        $this->loginHistoryService->recordFailedLogin($user, $request);

        throw new ApiException($message, 403, null, null, AssignedCompanyAccess::BLOCK_CODE);
    }

    protected function inactiveAccountMessage(User $user): string
    {
        $invitation = $user->invitation_status;

        return match ($invitation) {
            InvitationStatus::Pending => 'Accept your invitation and set a password before signing in.',
            InvitationStatus::Expired => 'Your invitation has expired. Ask an administrator to send a new one.',
            default => 'Your account is inactive.',
        };
    }

    protected function challengeKey(string $challenge): string
    {
        return 'mfa-challenge:'.$challenge;
    }
}
