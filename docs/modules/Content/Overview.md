# Content CMS — Module Overview

**Phase:** 5.1–5.8 (Foundation through Review)  
**Status:** Feature-complete for Phase 5; gaps documented in readiness report  
**Stack:** Laravel 12 API + Vue 3 admin UI + headless delivery APIs  

## Purpose

Enterprise headless CMS for managing pages, blogs, FAQs, policies, and related content from a single AMS dashboard, with editorial workflow, media library, version history, SEO packages, and public/private delivery APIs for mobile and web consumers.

## Submodules

| Submodule | Responsibility |
|-----------|----------------|
| Catalog | Content types and statuses |
| Content | CRUD, dashboard, autosave, soft delete/restore |
| Editor | TipTap rich body, summary, featured image, SEO fields |
| Categories | Nested taxonomy, tree, bulk, SEO fields |
| Tags | Flat taxonomy, bulk, search |
| Media | Folders, library upload/replace/download, file versions |
| Workflow | Linear submit → review → approve → publish / reject |
| Versions | Snapshots on create/update/publish; compare; restore |
| Headless API | Public (published) + private (Sanctum / API key) delivery |
| SEO | Meta, Open Graph, Twitter Card, Schema.org, sitemap, robots |

## Domain Architecture

```
HTTP (Sanctum + permission | cms.private | public throttle)
  → Controllers (authorize, validate, call service, JSON response)
    → Services (business rules, SEO, workflow, media)
      → Repositories (query/persistence)
        → Models (Eloquent, casts, relations)
```

Namespace: `App\Domains\Content\`

Config: `backend/config/cms.php` (`CMS_SITE_URL`, sitemap path pattern, robots rules, API key prefix).

## Permissions

| Permission | Usage |
|------------|--------|
| `content.view` | Lists, details, dashboard, versions, media read, private CMS |
| `content.create` | Create content, categories, tags, media, API keys |
| `content.update` | Edit content, autosave, taxonomy, media metadata, version restore |
| `content.delete` | Soft delete content/taxonomy/media/API keys |
| `content.submit` | Submit draft for review |
| `content.review` | Mark reviewed |
| `content.approve` | Approve for publish readiness |
| `content.publish` | Publish / unpublish via workflow or legacy endpoints |

Policy: `ContentPolicy` (permission-based). Shared by media/catalog/API-key controllers.

## Role map (seeded)

| Role | Content ability |
|------|-----------------|
| `super-admin` | All |
| `content-writer` | view, create, update, submit |
| `content-editor` | + review |
| `content-manager` | + delete, approve (no publish) |
| `company-admin` | view/create/update/publish/submit/review/approve (no delete) |

## Cross-Cutting Concerns

- UUID route keys on domain models
- Soft deletes on mutable entities
- Spatie Activity Log via domain events + `LogContentActivity`
- Standard API envelope: `{ success, message, data }`
- Public delivery increments `view_count` for popular sorting
- Version snapshots skip quiet autosaves

## Related Docs

- [Database Documentation](./Database.md)
- [API Documentation](./API.md)
- [Editor Guide](./Editor-Guide.md)
- [Administrator Guide](./Administrator-Guide.md)
- [Phase 5 Review Reports](./Review-Reports.md)
- [Module index](../Content.md)
