<?php

namespace App\Domains\Content\Enums;

enum ContentStatusSlug: string
{
    case Draft = 'draft';
    case PendingReview = 'pending_review';
    case Reviewed = 'reviewed';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Published = 'published';
    case Scheduled = 'scheduled';
    case Archived = 'archived';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::PendingReview => 'Pending Review',
            self::Reviewed => 'Reviewed',
            self::Approved => 'Approved',
            self::Rejected => 'Rejected',
            self::Published => 'Published',
            self::Scheduled => 'Scheduled',
            self::Archived => 'Archived',
        };
    }
}
