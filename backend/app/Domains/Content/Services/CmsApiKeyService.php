<?php

namespace App\Domains\Content\Services;

use App\Domains\Content\Models\CmsApiKey;
use App\Domains\Content\Repositories\CmsApiKeyRepository;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class CmsApiKeyService
{
    public function __construct(
        private readonly CmsApiKeyRepository $cmsApiKeyRepository
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function list(array $filters = []): LengthAwarePaginator
    {
        return $this->cmsApiKeyRepository->paginateFiltered($filters);
    }

    /**
     * @param  array{name: string, abilities?: list<string>|null, expires_at?: string|null}  $data
     * @return array{key: CmsApiKey, plain_text: string}
     */
    public function create(array $data, User $actor): array
    {
        $prefix = (string) config('cms.api_key_prefix', 'cms_');
        $random = Str::random(40);
        $plainText = $prefix.$random;
        $keyPrefix = substr($plainText, 0, 12);

        $key = $this->cmsApiKeyRepository->createKey([
            'name' => $data['name'],
            'key_prefix' => $keyPrefix,
            'key_hash' => hash('sha256', $plainText),
            'abilities' => $data['abilities'] ?? ['cms.read'],
            'is_active' => true,
            'expires_at' => $data['expires_at'] ?? null,
            'created_by' => $actor->id,
        ]);

        return [
            'key' => $key->load('creator:id,uuid,full_name,email'),
            'plain_text' => $plainText,
        ];
    }

    public function revoke(string $identifier): CmsApiKey
    {
        $key = $this->cmsApiKeyRepository->findByIdentifierOrFail($identifier);
        $key->is_active = false;
        $key->save();
        $key->delete();

        return $key;
    }

    public function findValidByPlainText(?string $plainText): ?CmsApiKey
    {
        if (! filled($plainText)) {
            return null;
        }

        $hash = hash('sha256', $plainText);
        $key = $this->cmsApiKeyRepository->findByHash($hash);

        if (! $key || ! $key->isUsable()) {
            return null;
        }

        $key->forceFill(['last_used_at' => now()])->saveQuietly();

        return $key;
    }
}
