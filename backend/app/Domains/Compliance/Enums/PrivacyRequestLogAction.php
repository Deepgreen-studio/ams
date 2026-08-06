<?php

namespace App\Domains\Compliance\Enums;

enum PrivacyRequestLogAction: string
{
    case Created = 'created';
    case Updated = 'updated';
    case StatusChanged = 'status_changed';
    case Assigned = 'assigned';
    case IdentityVerified = 'identity_verified';
    case IdentityFailed = 'identity_failed';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case ExportGenerated = 'export_generated';
    case DataDeleted = 'data_deleted';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case Restored = 'restored';
    case NoteAdded = 'note_added';

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
            self::Created => 'Created',
            self::Updated => 'Updated',
            self::StatusChanged => 'Status Changed',
            self::Assigned => 'Assigned',
            self::IdentityVerified => 'Identity Verified',
            self::IdentityFailed => 'Identity Verification Failed',
            self::Approved => 'Approved',
            self::Rejected => 'Rejected',
            self::ExportGenerated => 'Export Generated',
            self::DataDeleted => 'Data Deleted',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
            self::Restored => 'Restored',
            self::NoteAdded => 'Note Added',
        };
    }
}
