# AI Assistant Domain

## Overview

Phase 8.6 delivers AI-ready architecture for AMS with a provider abstraction layer. Domain services never hardcode OpenAI, Azure, Gemini, Claude, or custom vendors — drivers are resolved from `config/ai.php` via `AiProviderManager`.

## Responsibilities

- Manage AI providers and credentials
- Prompt templates per feature
- Chat / knowledge conversations
- Feature assistants (categorize, route, summarize, translate, search, suggestions)
- Usage analytics and AI logs
- Company-scoped AI settings

## Folder Structure

```
backend/app/Domains/Ai/
  Contracts/
  Controllers/
  DTOs/
  Enums/
  Events/
  Listeners/
  Models/
  Policies/
  Providers/
  Repositories/
  Requests/
  Resources/
  Routes/
  Services/
```

## Database Tables

- `ai_providers`
- `ai_prompts`
- `ai_conversations`
- `ai_messages`
- `ai_usage_logs`
- `ai_settings`

## Provider Abstraction

Registered drivers (`config/ai.php`):

| Driver key | Class |
|---|---|
| `openai` | `OpenAiProvider` |
| `azure_openai` | `AzureOpenAiProvider` |
| `gemini` | `GeminiProvider` |
| `claude` | `ClaudeProvider` |
| `null` | `NullAiProvider` (local stub) |
| `custom` | `CustomHttpAiProvider` |

Resolution flow:

1. Service requests driver via `AiProviderManager`
2. Manager loads DB provider row (optional)
3. Manager instantiates class from `config('ai.drivers')[$driver]`
4. Provider is configured and used through `AiProviderInterface`

## Features

- AI Suggestions
- Auto Categorization
- Smart Ticket Routing
- Content Suggestions
- Auto Translation
- Document Summarization
- AI Search
- Knowledge Assistant
- Chat Assistant

## API Endpoints (`/api/v1/ai`)

| Method | Path | Permission |
|---|---|---|
| GET | `/dashboard` | `ai.view` |
| GET | `/catalog` | `ai.view` |
| GET | `/analytics` | `ai.view` |
| GET/PUT | `/settings` | `ai.manage` |
| CRUD | `/providers` | `ai.*` |
| POST | `/providers/{id}/test` | `ai.manage` |
| CRUD | `/prompts` | `ai.*` |
| POST | `/prompts/{id}/publish` | `ai.manage` |
| GET | `/conversations` | `ai.view` |
| POST | `/chat` | `ai.chat` |
| POST | `/features/*` | `ai.chat` |
| GET | `/logs` | `ai.view` |

## Permissions

- `ai.view`
- `ai.create`
- `ai.update`
- `ai.delete`
- `ai.manage`
- `ai.chat`

## Frontend

`frontend/src/modules/ai/`

- AI Dashboard
- AI Settings (providers)
- Prompt Manager
- Conversation History / Chat
- Usage Analytics
- AI Logs

## Testing Notes

Feature tests use the seeded `null` provider so no external API keys are required.

```bash
php artisan test --filter=AiAssistantTest
```
