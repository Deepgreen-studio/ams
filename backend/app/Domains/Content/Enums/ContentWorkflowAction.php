<?php

namespace App\Domains\Content\Enums;

enum ContentWorkflowAction: string
{
    case Submit = 'submit';
    case Review = 'review';
    case Approve = 'approve';
    case Reject = 'reject';
    case Publish = 'publish';
    case Archive = 'archive';
    case ReturnToDraft = 'return_to_draft';

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
            self::Submit => 'Submitted for review',
            self::Review => 'Reviewed',
            self::Approve => 'Approved',
            self::Reject => 'Rejected',
            self::Publish => 'Published',
            self::Archive => 'Archived',
            self::ReturnToDraft => 'Returned to draft',
        };
    }
}
