<?php

namespace App\Domains\Analytics\Enums;

enum AnalyticsReportFormat: string
{
    case Csv = 'csv';
    case Excel = 'excel';
    case Pdf = 'pdf';
    case Print = 'print';
    case Json = 'json';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
