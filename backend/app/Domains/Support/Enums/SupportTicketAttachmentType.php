<?php

namespace App\Domains\Support\Enums;

enum SupportTicketAttachmentType: string
{
    case File = 'file';
    case Screenshot = 'screenshot';
    case Video = 'video';
    case Document = 'document';

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
            self::File => 'File',
            self::Screenshot => 'Screenshot',
            self::Video => 'Video',
            self::Document => 'Document',
        };
    }

    public static function fromExtension(string $extension): self
    {
        $extension = strtolower($extension);

        if (in_array($extension, ['png', 'jpg', 'jpeg', 'gif', 'webp', 'bmp', 'svg'], true)) {
            return self::Screenshot;
        }

        if (in_array($extension, ['mp4', 'webm', 'mov', 'avi', 'mkv', 'm4v'], true)) {
            return self::Video;
        }

        if (in_array($extension, ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'csv', 'txt', 'rtf', 'ppt', 'pptx'], true)) {
            return self::Document;
        }

        return self::File;
    }
}
