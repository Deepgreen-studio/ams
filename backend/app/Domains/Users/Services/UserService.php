<?php

namespace App\Domains\Users\Services;

use App\Domains\Companies\Models\Company;
use App\Domains\Companies\Models\CompanyLocation;
use App\Domains\Companies\Models\Department;
use App\Domains\Companies\Models\Team;
use App\Domains\Companies\Repositories\CompanyRepository;
use App\Domains\Roles\Services\RoleService;
use App\Domains\Users\Enums\InvitationStatus;
use App\Domains\Users\Enums\UserPermission;
use App\Domains\Users\Enums\UserStatus;
use App\Domains\Users\Events\AvatarUpdated;
use App\Domains\Users\Events\UserCreated;
use App\Domains\Users\Events\UserDeleted;
use App\Domains\Users\Events\UserRestored;
use App\Domains\Users\Events\UserUpdated;
use App\Domains\Users\Notifications\UserPasswordSetupNotification;
use App\Domains\Users\Repositories\UserRepository;
use App\Domains\Users\Support\UserLifecycleAuditor;
use App\Models\User;
use App\Shared\Exceptions\ApiException;
use App\Shared\Support\PhoneNumber;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UserService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly RoleService $roleService
    ) {}

    /**
     * @param array<string, mixed> $filters
     * @return array{users: LengthAwarePaginator, statistics: array<string, int>}
     */
    public function list(array $filters = []): array
    {
        return [
            'users' => $this->userRepository->paginateFiltered($filters),
            'statistics' => $this->userRepository->statistics(),
        ];
    }

    /**
     * @return array{user: User, activity_summary: array<string, mixed>}
     */
    public function show(string $identifier): array
    {
        $user = $this->userRepository->findByIdentifierOrFail($identifier);
        $user->load([
            'creator:id,uuid,full_name,email',
            'updater:id,uuid,full_name,email',
            'deleter:id,uuid,full_name,email',
            'roles',
            'companies',
            'department.teams:id,uuid,department_id,name',
            'team',
            'location',
        ]);

        return [
            'user' => $user,
            'activity_summary' => $this->userRepository->activitySummary($user),
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data, User $actor): User
    {
        return DB::transaction(function () use ($data, $actor): User {
            $payload = $this->prepareWritablePayload($data);
            $payload['created_by'] = $actor->id;
            $payload['updated_by'] = $actor->id;
            $payload['status'] = UserStatus::Inactive->value;
            $payload['password'] = Str::password(64);
            $payload['invitation_status'] = InvitationStatus::Pending->value;
            $payload['invitation_sent_at'] = now();
            $payload['invitation_expires_at'] = now()->addMinutes($this->invitationTtlMinutes());
            $payload['email_verified_at'] = null;

            $user = $this->userRepository->createUser($payload);

            if (! empty($data['roles']) && is_array($data['roles'])) {
                $this->syncUserRoles($user, $data['roles'], $actor);
            }

            if (array_key_exists('company_id', $data)) {
                $this->syncPrimaryCompany($user, $data['company_id']);
                $this->inheritCompanyLocale($user, $data);
            }

            if (array_key_exists('department_id', $data)) {
                $this->syncDepartment($user, $data['department_id'], $data['company_id'] ?? null);
            }

            $this->syncAssignment($user, $data);

            $created = $user->load(['creator', 'updater', 'deleter', 'roles', 'companies', 'department.teams:id,uuid,department_id,name', 'team', 'location']);

            DB::afterCommit(function () use ($created): void {
                $this->sendPasswordSetupEmail($created);
            });

            event(new UserCreated($created->load(['roles']), $actor));

            return $created;
        });
    }

    protected function sendPasswordSetupEmail(User $user): void
    {
        $token = Password::broker()->createToken($user);
        $user->notify(new UserPasswordSetupNotification($token));
    }

    public function resendInvitation(string $identifier, User $actor): User
    {
        $user = $this->userRepository->findByIdentifierOrFail($identifier);
        $invitation = $user->invitation_status;

        if ($user->isProtectedAccount()) {
            throw new ApiException('The system administrator account cannot receive an invitation.', 422);
        }

        if ($invitation === InvitationStatus::Accepted) {
            throw new ApiException('This user has already accepted the invitation.', 422);
        }

        $user = $this->userRepository->updateUser($user, [
            'status' => UserStatus::Inactive->value,
            'invitation_status' => InvitationStatus::Pending->value,
            'invitation_sent_at' => now(),
            'invitation_expires_at' => now()->addMinutes($this->invitationTtlMinutes()),
            'updated_by' => $actor->id,
        ]);

        $this->sendPasswordSetupEmail($user);

        return $user->load(['creator', 'updater', 'roles', 'companies', 'department.teams:id,uuid,department_id,name', 'team', 'location']);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function loginHistory(User $user): array
    {
        return $user->loginHistories()
            ->latest('logged_in_at')
            ->limit(10)
            ->get()
            ->map(static fn ($entry): array => [
                'uuid' => $entry->uuid,
                'status' => $entry->status,
                'ip_address' => $entry->ip_address,
                'browser' => $entry->browser,
                'platform' => $entry->platform,
                'device' => $entry->device,
                'logged_in_at' => $entry->logged_in_at,
                'logout_at' => $entry->logout_at,
            ])
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function sessions(User $user, ?User $actor = null): array
    {
        $currentId = $actor?->is($user) ? $actor->currentAccessToken()?->id : null;

        return $user->tokens()
            ->latest('id')
            ->get()
            ->map(static fn ($token): array => [
                'id' => $token->id,
                'name' => $token->name,
                'last_used_at' => $token->last_used_at,
                'created_at' => $token->created_at,
                'current' => $currentId !== null && (int) $token->id === (int) $currentId,
            ])
            ->all();
    }

    public function revokeSession(string $identifier, int $sessionId, User $actor): void
    {
        $user = $this->userRepository->findByIdentifierOrFail($identifier);
        $deleted = $user->tokens()->whereKey($sessionId)->delete();

        if ($deleted === 0) {
            throw new ApiException('Session not found.', 404);
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(string $identifier, array $data, User $actor): User
    {
        return DB::transaction(function () use ($identifier, $data, $actor): User {
            $user = $this->userRepository->findByIdentifierOrFail($identifier);
            $before = UserLifecycleAuditor::snapshot($user);
            $payload = $this->prepareWritablePayload($data, isUpdate: true);
            $payload['updated_by'] = $actor->id;
            $this->guardProtectedAccount($user, $payload);
            $this->guardAccountActivation($user, $payload);

            $updated = $this->userRepository->updateUser($user, $payload);

            if (array_key_exists('roles', $data)) {
                $this->syncUserRoles($updated, $data['roles'] ?? [], $actor);
            }

            if (array_key_exists('company_id', $data)) {
                $this->syncPrimaryCompany($updated, $data['company_id']);
                $this->inheritCompanyLocale($updated, $data);
            }

            if (array_key_exists('department_id', $data)) {
                $this->syncDepartment($updated, $data['department_id'], $data['company_id'] ?? null);
            }

            $this->syncAssignment($updated, $data);

            $updated = $updated->load(['roles', 'companies', 'department.teams:id,uuid,department_id,name', 'team', 'location']);
            event(new UserUpdated(
                $updated,
                $actor,
                'user_updated',
                $before,
                UserLifecycleAuditor::snapshot($updated)
            ));

            return $updated;
        });
    }

    public function delete(string $identifier, User $actor): void
    {
        DB::transaction(function () use ($identifier, $actor): void {
            $user = $this->userRepository->findByIdentifierOrFail($identifier);

            if ($user->isProtectedAccount()) {
                throw new ApiException('The system administrator account cannot be removed.', 422);
            }

            if ($user->id === $actor->id) {
                throw new ApiException('You cannot delete your own account.', 422);
            }

            $this->userRepository->updateUser($user, [
                'updated_by' => $actor->id,
                'deleted_by' => $actor->id,
            ]);
            $this->userRepository->softDeleteUser($user);

            event(new UserDeleted($user, $actor, false));
        });
    }

    public function restore(string $identifier, User $actor): User
    {
        return DB::transaction(function () use ($identifier, $actor): User {
            $user = $this->userRepository->findByIdentifierOrFail($identifier, withTrashed: true);

            if (! $user->trashed()) {
                throw new ApiException('User is not deleted.', 422);
            }

            $restored = $this->userRepository->restoreUser($user);
            $restored = $this->userRepository->updateUser($restored, [
                'updated_by' => $actor->id,
                'deleted_by' => null,
            ]);

            event(new UserRestored($restored, $actor));

            return $restored;
        });
    }

    public function forceDelete(string $identifier, User $actor): void
    {
        DB::transaction(function () use ($identifier, $actor): void {
            $user = $this->userRepository->findByIdentifierOrFail($identifier, withTrashed: true);

            if ($user->isProtectedAccount()) {
                throw new ApiException('The system administrator account cannot be removed.', 422);
            }

            if ($user->id === $actor->id) {
                throw new ApiException('You cannot permanently delete your own account.', 422);
            }

            $this->deleteAvatarFile($user->avatar);
            $this->userRepository->forceDeleteUser($user);

            event(new UserDeleted($user, $actor, true));
        });
    }

    public function profile(User $user): User
    {
        return $user->load([
            'creator:id,uuid,full_name,email',
            'updater:id,uuid,full_name,email',
            'deleter:id,uuid,full_name,email',
            'companies',
            'department.teams:id,uuid,department_id,name',
            'team',
        ]);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updateProfile(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data): User {
            $payload = $this->prepareWritablePayload($data, isUpdate: true, isProfile: true);
            $payload['updated_by'] = $user->id;
            $before = UserLifecycleAuditor::snapshot($user);

            $updated = $this->userRepository->updateUser($user, $payload);

            event(new UserUpdated(
                $updated,
                $user,
                'profile_updated',
                $before,
                UserLifecycleAuditor::snapshot($updated)
            ));

            return $updated;
        });
    }

    public function uploadAvatar(User $user, UploadedFile $file, ?User $actor = null): User
    {
        return DB::transaction(function () use ($user, $file, $actor): User {
            $disk = config('filesystems.avatar_disk', 'public');
            $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'jpg');
            $filename = sprintf('%s.%s', Str::uuid()->toString(), $extension);
            $path = $file->storeAs('avatars', $filename, $disk);

            if (! $path) {
                throw new ApiException('Unable to store avatar image.', 500);
            }

            $previous = $user->avatar;
            $updated = $this->userRepository->updateUser($user, [
                'avatar' => $path,
                'updated_by' => ($actor ?? $user)->id,
            ]);

            $this->deleteAvatarFile($previous);

            event(new AvatarUpdated($updated, $actor ?? $user, $previous, $path));

            return $updated;
        });
    }

    /**
     * @return array<string, int>
     */
    public function statistics(): array
    {
        return $this->userRepository->statistics();
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    protected function prepareWritablePayload(array $data, bool $isUpdate = false, bool $isProfile = false): array
    {
        $allowed = [
            'first_name',
            'last_name',
            'email',
            'phone',
            'timezone',
            'language',
            'status',
        ];

        if ($isProfile) {
            $allowed = array_values(array_diff($allowed, ['status']));
        }

        $payload = array_intersect_key($data, array_flip($allowed));

        if (array_key_exists('phone', $payload)) {
            $payload['phone'] = PhoneNumber::store($payload['phone']);
        }

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    /**
     * @param  array<string, mixed>  $payload
     */
    protected function guardProtectedAccount(User $user, array &$payload): void
    {
        if (! $user->isProtectedAccount()) {
            return;
        }

        if (array_key_exists('status', $payload) && $payload['status'] !== UserStatus::Active->value) {
            throw new ApiException('The system administrator account must stay active.', 422);
        }

        unset($payload['status']);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function guardAccountActivation(User $user, array $payload): void
    {
        if (($payload['status'] ?? null) !== UserStatus::Active->value) {
            return;
        }

        $invitation = $user->invitation_status;

        if ($invitation === InvitationStatus::Pending || $invitation === InvitationStatus::Expired) {
            throw new ApiException('The user must accept the invitation before the account can be activated.', 422);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function inheritCompanyLocale(User $user, array $data): void
    {
        $company = $user->companies()->first();

        if (! $company instanceof Company) {
            return;
        }

        $changes = [];

        if ((! array_key_exists('timezone', $data) || blank($data['timezone'])) && filled($company->timezone)) {
            $changes['timezone'] = $company->timezone;
        }

        if ((! array_key_exists('language', $data) || blank($data['language'])) && filled($company->language)) {
            $changes['language'] = $company->language;
        }

        if ($changes !== []) {
            $user->forceFill($changes)->save();
        }
    }

    protected function invitationTtlMinutes(): int
    {
        $expire = config('auth.passwords.'.config('auth.defaults.passwords').'.expire', 60);

        return max(1, (int) $expire);
    }

    protected function syncPrimaryCompany(User $user, mixed $companyUuid): void
    {
        if (blank($companyUuid)) {
            $user->companies()->detach();

            return;
        }

        $company = app(CompanyRepository::class)->findByIdentifierOrFail((string) $companyUuid);
        $user->companies()->sync([
            $company->id => [
                'is_primary' => true,
                'status' => 'active',
            ],
        ]);
    }

    protected function syncDepartment(User $user, mixed $departmentUuid, mixed $companyUuid): void
    {
        if (blank($departmentUuid)) {
            $user->forceFill(['department_id' => null])->save();

            return;
        }

        $department = Department::query()->where('uuid', (string) $departmentUuid)->first();
        if (! $department) {
            throw new ApiException('Department not found.', 422);
        }

        if (filled($companyUuid)) {
            $company = app(CompanyRepository::class)->findByIdentifierOrFail((string) $companyUuid);
            if ($department->company_id !== $company->id) {
                throw new ApiException('The selected department does not belong to the selected company.', 422);
            }
        }

        $user->forceFill(['department_id' => $department->id])->save();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function syncAssignment(User $user, array $data): void
    {
        $changes = [];

        if (array_key_exists('team_id', $data)) {
            $changes['team_id'] = $this->assignmentId(Team::class, $data['team_id'], $user, 'team');
        }

        if (array_key_exists('location_id', $data)) {
            $changes['location_id'] = $this->assignmentId(CompanyLocation::class, $data['location_id'], $user, 'location');
        }

        if ($changes !== []) {
            $user->forceFill($changes)->save();
        }
    }

    /**
     * @param  class-string<Team|CompanyLocation>  $model
     */
    protected function assignmentId(string $model, mixed $uuid, User $user, string $label): ?int
    {
        if (blank($uuid)) {
            return null;
        }

        $record = $model::query()->where('uuid', (string) $uuid)->first();
        if (! $record) {
            throw new ApiException(ucfirst($label).' not found.', 422);
        }

        $companyId = $user->companies()->value('companies.id');
        if ($companyId && (int) $record->company_id !== (int) $companyId) {
            throw new ApiException('The selected '.$label.' does not belong to the selected company.', 422);
        }

        if ($label === 'team' && $user->department_id && (int) $record->department_id !== (int) $user->department_id) {
            throw new ApiException('The selected team does not belong to the selected department.', 422);
        }

        return $record->id;
    }

    /**
     * @param list<string> $roleIdentifiers
     */
    protected function syncUserRoles(User $user, array $roleIdentifiers, User $actor): void
    {
        if (! $actor->can(UserPermission::ASSIGN_ROLES)) {
            throw new ApiException('You are not allowed to assign roles to users.', 403);
        }

        $this->roleService->assignRolesToUser($user->uuid, $roleIdentifiers, $actor);
    }

    protected function deleteAvatarFile(?string $path): void
    {
        if (blank($path) || Str::startsWith($path, ['http://', 'https://'])) {
            return;
        }

        $disk = config('filesystems.avatar_disk', 'public');

        if (Storage::disk($disk)->exists($path)) {
            Storage::disk($disk)->delete($path);
        }
    }
}
