<?php

namespace App\Domains\Support\Enums;

enum KnowledgeArticleType: string
{
    case Article = 'article';
    case Guide = 'guide';
    case Faq = 'faq';
    case Tutorial = 'tutorial';
    case Video = 'video';
    case ReleaseNotes = 'release_notes';

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
            self::Article => 'Articles',
            self::Guide => 'Guides',
            self::Faq => 'FAQs',
            self::Tutorial => 'Tutorials',
            self::Video => 'Videos',
            self::ReleaseNotes => 'Release Notes',
        };
    }

    public function preferredCmsType(): ?string
    {
        return match ($this) {
            self::Faq => 'faq',
            self::Guide, self::Tutorial, self::Article => 'help',
            self::ReleaseNotes => 'news',
            self::Video => 'help',
        };
    }
}
