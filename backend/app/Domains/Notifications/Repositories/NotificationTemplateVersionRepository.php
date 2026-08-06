<?php

namespace App\Domains\Notifications\Repositories;

use App\Domains\Notifications\Models\NotificationTemplateVersion;
use App\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;

class NotificationTemplateVersionRepository extends BaseRepository
{
    public function __construct(NotificationTemplateVersion $model)
    {
        parent::__construct($model);
    }

    public function findByIdentifierOrFail(string $identifier): NotificationTemplateVersion
    {
        /** @var NotificationTemplateVersion|null $version */
        $version = $this->model->newQuery()
            ->where('uuid', $identifier)
            ->when(ctype_digit($identifier), fn ($q) => $q->orWhere('id', (int) $identifier))
            ->first();

        if (! $version) {
            abort(404, 'Notification template version not found.');
        }

        return $version;
    }

    public function findForTemplate(int $templateId, string $identifier): NotificationTemplateVersion
    {
        /** @var NotificationTemplateVersion|null $version */
        $version = $this->model->newQuery()
            ->where('notification_template_id', $templateId)
            ->where(function ($query) use ($identifier): void {
                $query->where('uuid', $identifier);
                if (ctype_digit($identifier)) {
                    $query->orWhere('version', (int) $identifier)->orWhere('id', (int) $identifier);
                }
            })
            ->first();

        if (! $version) {
            abort(404, 'Notification template version not found.');
        }

        return $version;
    }

    /**
     * @return Collection<int, NotificationTemplateVersion>
     */
    public function listForTemplate(int $templateId): Collection
    {
        return $this->model->newQuery()
            ->with('creator:id,uuid,full_name,email')
            ->where('notification_template_id', $templateId)
            ->orderByDesc('version')
            ->get();
    }

    public function nextVersionNumber(int $templateId): int
    {
        return ((int) $this->model->newQuery()
            ->where('notification_template_id', $templateId)
            ->max('version')) + 1;
    }
}
