<?php

namespace App\Domains\Users\Services;

use App\Domains\Users\Events\AvatarUpdated;
use App\Domains\Users\Events\UserCreated;
use App\Domains\Users\Events\UserDeleted;
use App\Domains\Users\Events\UserRestored;
use App\Domains\Users\Events\UserUpdated;
use App\Domains\Users\Notifications\UserWelcomeNotification;
use App\Domains\Users\Repositories\UserRepository;
use App\Models\User;
use App\Shared\Exceptions\ApiException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UserService
{
    public function __construct(
        private readonly UserRepository $userRepository
    ) {}

    /**
     * @param  array<string, mixed>  $filters
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
        $user->load(['creator:id,uuid,full_name,email', 'updater:id,uuid,full_name,email']);

        return [
            'user' => $user,
            'activity_summary' => $this->userRepository->activitySummary($user),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $actor): User
    {
        return DB::transaction(function () use ($data, $actor): User {
            $payload = $this->prepareWritablePayload($data);
            $payload['created_by'] = $actor->id;
            $payload['updated_by'] = $actor->id;

            $user = $this->userRepository->createUser($payload);

            event(new UserCreated($user, $actor));

            if (! empty($data['send_welcome_notification'])) {
                $user->notify(new UserWelcomeNotification);
            }

            return $user->load(['creator', 'updater']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(string $identifier, array $data, User $actor): User
    {
        return DB::transaction(function () use ($identifier, $data, $actor): User {
            $user = $this->userRepository->findByIdentifierOrFail($identifier);
            $payload = $this->prepareWritablePayload($data, isUpdate: true);
            $payload['updated_by'] = $actor->id;

            $updated = $this->userRepository->updateUser($user, $payload);

            event(new UserUpdated($updated, $actor, 'user_updated'));

            return $updated;
        });
    }

    public function delete(string $identifier, User $actor): void
    {
        DB::transaction(function () use ($identifier, $actor): void {
            $user = $this->userRepository->findByIdentifierOrFail($identifier);

            if ($user->id === $actor->id) {
                throw new ApiException('You cannot delete your own account.', 422);
            }

            $this->userRepository->updateUser($user, ['updated_by' => $actor->id]);
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
            $restored = $this->userRepository->updateUser($restored, ['updated_by' => $actor->id]);

            event(new UserRestored($restored, $actor));

            return $restored;
        });
    }

    public function forceDelete(string $identifier, User $actor): void
    {
        DB::transaction(function () use ($identifier, $actor): void {
            $user = $this->userRepository->findByIdentifierOrFail($identifier, withTrashed: true);

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
        return $user->load(['creator:id,uuid,full_name,email', 'updater:id,uuid,full_name,email']);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateProfile(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data): User {
            $payload = $this->prepareWritablePayload($data, isUpdate: true, isProfile: true);
            $payload['updated_by'] = $user->id;

            $updated = $this->userRepository->updateUser($user, $payload);

            event(new UserUpdated($updated, $user, 'profile_updated'));

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
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function prepareWritablePayload(array $data, bool $isUpdate = false, bool $isProfile = false): array
    {
        $allowed = [
            'first_name',
            'last_name',
            'email',
            'phone',
            'gender',
            'date_of_birth',
            'timezone',
            'language',
            'password',
            'status',
            'email_verified_at',
        ];

        if ($isProfile) {
            $allowed = array_values(array_diff($allowed, ['password', 'status', 'email_verified_at']));
        }

        $payload = array_intersect_key($data, array_flip($allowed));

        if (array_key_exists('phone', $payload) && blank($payload['phone'])) {
            $payload['phone'] = null;
        }

        if (array_key_exists('gender', $payload) && blank($payload['gender'])) {
            $payload['gender'] = null;
        }

        if (! $isUpdate && empty($payload['status'])) {
            $payload['status'] = 'active';
        }

        return $payload;
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
