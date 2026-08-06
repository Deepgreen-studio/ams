<?php

namespace App\Domains\Applications\Enums;

enum ApplicationReleaseStatus: string
{
    case Planned = 'planned';
    case Scheduled = 'scheduled';
    case PendingApproval = 'pending_approval';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Deploying = 'deploying';
    case Deployed = 'deployed';
    case Failed = 'failed';
    case RolledBack = 'rolled_back';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Planned => 'Planned',
            self::Scheduled => 'Scheduled',
            self::PendingApproval => 'Pending Approval',
            self::Approved => 'Approved',
            self::Rejected => 'Rejected',
            self::Deploying => 'Deploying',
            self::Deployed => 'Deployed',
            self::Failed => 'Failed',
            self::RolledBack => 'Rolled Back',
            self::Cancelled => 'Cancelled',
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [
            self::Deployed,
            self::Failed,
            self::RolledBack,
            self::Cancelled,
        ], true);
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
