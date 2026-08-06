<?php

namespace App\Domains\Content\Enums;

final class ContentPermission
{
    public const VIEW = 'content.view';

    public const CREATE = 'content.create';

    public const UPDATE = 'content.update';

    public const DELETE = 'content.delete';

    public const PUBLISH = 'content.publish';

    public const SUBMIT = 'content.submit';

    public const REVIEW = 'content.review';

    public const APPROVE = 'content.approve';

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::VIEW,
            self::CREATE,
            self::UPDATE,
            self::DELETE,
            self::PUBLISH,
            self::SUBMIT,
            self::REVIEW,
            self::APPROVE,
        ];
    }
}
