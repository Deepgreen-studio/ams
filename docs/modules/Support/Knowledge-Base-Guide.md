# Knowledge Base Guide

Enterprise help content for AMS Support agents and (future) portal self-service.

## Content types

| Type | Use for |
|------|---------|
| Article | General reference |
| Guide | Step-by-step how-tos |
| FAQ | Short Q&A |
| Tutorial | Learning paths |
| Video | Embed or link to video content |
| Release notes | Product change announcements |

## Lifecycle

1. **Draft** — create / edit  
2. **Publish** — visible in Knowledge Center lists (status `published`)  
3. **Archive** — retained, removed from featured/popular  
4. Soft delete — admin delete (restorable from DB with trashed tools if needed)

Every content update creates a **version**. Restore from version history to roll back.

## Categories & tags

- Categories: hierarchical (`parent_id`), used for browsing  
- Tags: free-form labels for search/filter  
- Manage under Knowledge APIs (requires `support.manage`)

## CMS connection

Optional link to CMS `contents`:

1. Create or edit a knowledge article  
2. Call **Link CMS** with CMS content UUID and `sync: true`  
3. When `sync_from_cms` is enabled, title/body/image follow CMS on sync  

Preferred CMS type mapping is defined in `KnowledgeArticleType::preferredCmsType()`.

## Feedback

Agents (and authenticated viewers) can mark **Helpful / Not helpful**. Counts feed “helpful” analytics on the article.

## Agent tips

- Prefer FAQ for repeated ticket answers; link from replies or use canned responses that point to KB URLs  
- Feature high-traffic articles from the Knowledge Center dashboard  
- Use release notes type for product update communications  

## Permissions

| Action | Permission |
|--------|------------|
| Browse / search / view | `support.view` |
| Create / edit / publish | `support.create` / `support.update` / `manage` |
| Categories & tags admin | `support.manage` |

## Frontend

- `/support/knowledge` — Knowledge Center  
- `/support/knowledge/articles` — browse/search  
- `/support/knowledge/articles/:id` — viewer, feedback, versions, related  
