# AI Integration Guide

## Purpose

Integrate AI features without hardcoding vendors. All application code must depend on `AiProviderInterface` and resolve drivers through `AiProviderManager` + `config/ai.php`.

**Domain:** `backend/app/Domains/Ai/`  
**Config:** `backend/config/ai.php`  
**API prefix:** `/api/v1/ai`

## Provider registry

| Driver key | Class |
|------------|-------|
| `openai` | `OpenAiProvider` |
| `azure_openai` | `AzureOpenAiProvider` |
| `gemini` | `GeminiProvider` |
| `claude` | `ClaudeProvider` |
| `null` | `NullAiProvider` (local stub) |
| `custom` | `CustomHttpAiProvider` |

Add future drivers by registering a class in `config('ai.drivers')` — do not change domain services.

## Resolution flow

```
Service → AiProviderManager::forProvider($model) | default()
       → config('ai.drivers')[$driver]
       → AiProviderInterface::configure($provider)
       → chat | complete | embed | summarize | translate | categorize
```

## Features

| Feature key | Endpoint |
|-------------|----------|
| Chat Assistant | `POST /ai/chat` |
| Suggestions | `POST /ai/features/suggest` |
| Auto Categorization | `POST /ai/features/categorize` |
| Smart Ticket Routing | `POST /ai/features/route-ticket` |
| Content Suggestions | `POST /ai/features/content-suggestions` |
| Auto Translation | `POST /ai/features/translate` |
| Document Summarization | `POST /ai/features/summarize` |
| AI Search | `POST /ai/features/search` |
| Knowledge Assistant | `POST /ai/features/knowledge` |

## Credentials

- Stored on `ai_providers.credentials` as `encrypted:array`
- Hidden from serialization; activity log excludes credentials
- Resources expose `has_credentials` only

## Prompts

Published prompts in `ai_prompts` are resolved by feature/key. Use Prompt Manager UI to version and publish.

## Usage logging

Every feature operation writes `ai_usage_logs` (tokens, latency, driver, status). Platform Analytics `/api/v1/analytics/ai` aggregates these rows.

## Local development

1. Seed `AiSeeder` (creates Local Null Provider + default prompts).
2. Keep `AI_DEFAULT_DRIVER=null` when no API keys exist.
3. Feature tests always use the null driver.

## Production checklist

- [ ] Configure at least one real provider; mark default
- [ ] Store API keys only via encrypted credentials field
- [ ] Enforce daily token budget (config exists; harden enforcement before high traffic)
- [ ] Monitor `/ai/logs` and Platform Analytics AI Usage
- [ ] Restrict `ai.manage` / `ai.chat` by role

## Permissions

`ai.view` · `ai.create` · `ai.update` · `ai.delete` · `ai.manage` · `ai.chat`

## Related docs

- `docs/ai/Overview.md`
