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
use App\Models\User;
use App\Shared\Exceptions\ApiException;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthenticationService
{
    public function __construct(
        private readonly AuthenticationRepository $repository,
        private readonly LoginHistoryService $loginHistoryService,
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

        if (! $user->isAccountActive()) {
            $this->loginHistoryService->recordFailedLogin($user, $request);

            throw new ApiException('Your account is inactive.', 403);
        }

        Auth::guard('web')->login($user, $remember);

        if ($request->hasSession()) {
            $request->session()->regenerate();
        }

        $this->repository->updateLastLogin($user, $request->ip());
        $this->repository->revokeAllTokens($user);
        $token = $this->repository->createAccessToken($user, 'web');

        event(new UserLoggedIn($user, $request));

        return [
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
        event(new PasswordResetRequested($email));

        // Always attempt send; response stays generic to prevent user enumeration.
        Password::broker()->sendResetLink(['email' => $email]);
    }

    /**
     * @param array{email: string, password: string, password_confirmation: string, token: string} $data
     */
    public function resetPassword(array $data): void
    {
        $status = Password::broker()->reset(
            $data,
            function (User $user, string $password): void {
                $this->repository->updatePassword($user, $password);
                $user->forceFill(['remember_token' => Str::random(60)])->save();
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
}
