<?php

namespace App\Domains\Compliance\Enums;

final class CompliancePermission
{
    public const VIEW = 'compliance.view';

    public const CREATE = 'compliance.create';

    public const UPDATE = 'compliance.update';

    public const DELETE = 'compliance.delete';

    public const MANAGE = 'compliance.manage';

    public const CASES = 'compliance.cases';

    public const PRIVACY = 'compliance.privacy';

    public const CONSENTS = 'compliance.consents';

    public const BREACHES = 'compliance.breaches';

    public const DPIA = 'compliance.dpia';

    public const POLICIES = 'compliance.policies';

    public const REPORTS = 'compliance.reports';

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
            self::MANAGE,
            self::CASES,
            self::PRIVACY,
            self::CONSENTS,
            self::BREACHES,
            self::DPIA,
            self::POLICIES,
            self::REPORTS,
        ];
    }
}
