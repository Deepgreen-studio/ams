<?php

namespace App\Domains\Customers\Enums;

enum CustomerDocumentCategory: string
{
    case Contracts = 'contracts';
    case Nda = 'nda';
    case Invoices = 'invoices';
    case Certificates = 'certificates';
    case Attachments = 'attachments';
    case Custom = 'custom';

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
            self::Contracts => 'Contracts',
            self::Nda => 'NDA',
            self::Invoices => 'Invoices',
            self::Certificates => 'Certificates',
            self::Attachments => 'Attachments',
            self::Custom => 'Custom Documents',
        };
    }
}
