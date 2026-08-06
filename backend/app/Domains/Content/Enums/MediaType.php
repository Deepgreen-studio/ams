<?php

namespace App\Domains\Content\Enums;

enum MediaType: string
{
    case Image = 'image';
    case Video = 'video';
    case Document = 'document';
    case Archive = 'archive';
    case Other = 'other';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function fromExtension(string $extension): self
    {
        $extension = strtolower($extension);

        return match (true) {
            in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp', 'ico'], true) => self::Image,
            in_array($extension, ['mp4', 'webm', 'mov', 'avi', 'mkv', 'm4v'], true) => self::Video,
            in_array($extension, ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv', 'rtf', 'odt', 'ods'], true) => self::Document,
            in_array($extension, ['zip', 'rar', '7z', 'tar', 'gz'], true) => self::Archive,
            default => self::Other,
        };
    }

    /**
     * @return list<string>
     */
    public static function allowedExtensions(): array
    {
        return [
            'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp',
            'mp4', 'webm', 'mov', 'avi', 'mkv',
            'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv',
            'zip', 'rar', '7z',
        ];
    }
}
