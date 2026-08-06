<?php

namespace App\Domains\Ai\Enums;

enum AiFeature: string
{
    case Suggestions = 'suggestions';
    case AutoCategorization = 'auto_categorization';
    case SmartTicketRouting = 'smart_ticket_routing';
    case ContentSuggestions = 'content_suggestions';
    case AutoTranslation = 'auto_translation';
    case DocumentSummarization = 'document_summarization';
    case AiSearch = 'ai_search';
    case KnowledgeAssistant = 'knowledge_assistant';
    case ChatAssistant = 'chat_assistant';

    public function label(): string
    {
        return match ($this) {
            self::Suggestions => 'AI Suggestions',
            self::AutoCategorization => 'Auto Categorization',
            self::SmartTicketRouting => 'Smart Ticket Routing',
            self::ContentSuggestions => 'Content Suggestions',
            self::AutoTranslation => 'Auto Translation',
            self::DocumentSummarization => 'Document Summarization',
            self::AiSearch => 'AI Search',
            self::KnowledgeAssistant => 'Knowledge Assistant',
            self::ChatAssistant => 'Chat Assistant',
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
