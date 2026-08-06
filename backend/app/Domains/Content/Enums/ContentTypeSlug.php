<?php

namespace App\Domains\Content\Enums;

enum ContentTypeSlug: string
{
    case Page = 'page';
    case Blog = 'blog';
    case News = 'news';
    case Faq = 'faq';
    case Terms = 'terms';
    case Privacy = 'privacy';
    case About = 'about';
    case Help = 'help';
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
            self::Page => 'Pages',
            self::Blog => 'Blogs',
            self::News => 'News',
            self::Faq => 'FAQs',
            self::Terms => 'Terms & Conditions',
            self::Privacy => 'Privacy Policy',
            self::About => 'About Us',
            self::Help => 'Help Center',
            self::Custom => 'Custom Content',
        };
    }
}
