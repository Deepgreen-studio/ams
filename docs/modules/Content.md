# Content CMS Module

Enterprise Headless CMS for AMS (Phases 5.1–5.8).

## Documentation set

| Document | Description |
|----------|-------------|
| [Overview](./Content/Overview.md) | CMS purpose, architecture, permissions |
| [Database](./Content/Database.md) | Tables, relationships, indexes |
| [API](./Content/API.md) | Admin, public, private, and SEO endpoints |
| [Editor Guide](./Content/Editor-Guide.md) | Writers/editors workflow |
| [Administrator Guide](./Content/Administrator-Guide.md) | Ops, workflow, SEO, API keys |
| [Review Reports](./Content/Review-Reports.md) | Architecture, security, performance, testing, readiness |

## Phase summary

| Phase | Deliverable |
|-------|-------------|
| 5.1 | CMS foundation (types, statuses, CRUD, dashboard) |
| 5.2 | Categories & tags |
| 5.3 | Content editor (TipTap, autosave, media upload) |
| 5.4 | Version history (compare / restore) |
| 5.5 | Media library (folders, versions, replace) |
| 5.6 | Approval (Writer → Editor → Manager → Admin) |
| 5.7 | Headless CMS API + SEO delivery |
| 5.8 | Module review + documentation (this milestone) |

## Quick test

```bash
cd backend
php artisan migrate
php artisan test --filter=Content
php artisan test tests/Unit/Content
```

**Last review run (2026-08-03):** 32 feature + 5 unit tests passed (280 assertions).

## Frontend entry

`/content` → Dashboard · Content · Approval Queue · Media · Taxonomy · Delivery Preview · SEO Tools · API Explorer

## Permissions

`content.view` · `content.create` · `content.update` · `content.delete` · `content.publish` · `content.submit` · `content.review` · `content.approve`
