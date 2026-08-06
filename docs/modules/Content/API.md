# Content CMS — API Documentation

Base prefix: `/api/v1`  
Envelope: `{ "success": true|false, "message": "", "data": {} }`

## Authentication

| Surface | Auth |
|---------|------|
| Admin `/content/*` | Sanctum + Spatie `permission:*` |
| Public `/cms/public/*` | None (published only) |
| Private `/cms/private/*` | Sanctum user with `content.view` **or** `X-CMS-Api-Key: cms_…` / `Authorization: Bearer cms_…` |
| SEO JSON `/cms/seo/*` | None |
| Web `/sitemap.xml`, `/robots.txt` | None |

---

## Admin API — `/content`

### Dashboard & catalog
| Method | Path | Permission |
|--------|------|------------|
| GET | `/content/dashboard` | view |
| GET/POST | `/content/types` | view / create |
| PUT | `/content/types/{type}` | update |
| GET | `/content/statuses` | view |

### CMS API keys
| Method | Path | Permission |
|--------|------|------------|
| GET | `/content/api-keys` | view |
| POST | `/content/api-keys` | create — returns `plain_text` once |
| DELETE | `/content/api-keys/{uuid}` | delete |

### Categories & tags
Full CRUD + `tree` (categories), `bulk`, `restore`. Permissions: view/create/update/delete accordingly.

### Media folders & library
CRUD folders (`tree`), library list/upload/replace/download/versions/restore. Upload multipart.

### Content CRUD / editor
| Method | Path | Notes |
|--------|------|-------|
| GET/POST | `/content` | List / create |
| GET/PUT/DELETE | `/content/{uuid}` | Show / update / soft delete |
| POST | `/content/{uuid}/autosave` | Quiet save (no version) |
| POST | `/content/media` | Editor image upload → library |
| POST | `/content/{uuid}/restore` | Restore |
| POST | `/content/{uuid}/publish` · `/unpublish` | Legacy publish gates |

### Workflow
| Method | Path | Permission |
|--------|------|------------|
| GET | `/content/workflow/queue` | view |
| GET | `/content/{uuid}/workflow/history` | view |
| POST | `…/submit` | submit |
| POST | `…/review` | review |
| POST | `…/approve` | approve |
| POST | `…/reject` | view *(see security findings)* |
| POST | `…/publish` | publish |
| POST | `…/archive` · `…/return-to-draft` | update |

### Versions
| Method | Path | Permission |
|--------|------|------------|
| GET | `/content/{uuid}/versions` | view |
| GET | `/content/{uuid}/versions/compare?from=&to=` | view |
| GET | `/content/{uuid}/versions/{version}` | view |
| POST | `/content/{uuid}/versions/{version}/restore` | update |

---

## Public headless — `/cms/public`

Published content only. Future `published_at` hidden.

| Method | Path |
|--------|------|
| GET | `/contents` · `/contents/{uuid\|slug}` · `/contents/{id}/seo` |
| GET | `/search?q=` |
| GET | `/featured` · `/latest` · `/popular` |
| GET | `/categories` · `/categories/{slug}` · `/categories/{slug}/contents` |
| GET | `/tags` · `/tags/{slug}` · `/tags/{slug}/contents` |

Query helpers: `type`, `category`, `tag`, `sort_by`, `sort_dir`, `per_page`, `include_body`, `include_seo`, `preview=1` (skip view increment on show).

Show response includes SEO package: title, description, canonical, open_graph, twitter_card, schema_org.

---

## Private headless — `/cms/private`

Same collection patterns as public, plus draft visibility and:

| Method | Path |
|--------|------|
| GET | `/contents/{id}/preview` → `{ content, seo }` |

Filters may include `status`, `trashed`.

---

## SEO discovery

| Method | Path | Content-Type |
|--------|------|----------------|
| GET | `/sitemap.xml` | `application/xml` |
| GET | `/robots.txt` | `text/plain` |
| GET | `/api/v1/cms/seo/sitemap.json` | JSON entries |
| GET | `/api/v1/cms/seo/robots.json` | JSON text |

Config: `config/cms.php` (`site_url`, path pattern, include categories/tags).

---

## Example — public show

```http
GET /api/v1/cms/public/contents/enterprise-guide?type=page
```

```json
{
  "success": true,
  "message": "",
  "data": {
    "content": {
      "uuid": "...",
      "title": "Enterprise Guide",
      "slug": "enterprise-guide",
      "is_featured": true,
      "view_count": 6,
      "seo": {
        "title": "Enterprise Guide SEO",
        "canonical_url": "https://example.com/content/page/enterprise-guide",
        "open_graph": {},
        "twitter_card": { "card": "summary_large_image" },
        "schema_org": { "@context": "https://schema.org", "@type": "Article" }
      }
    }
  }
}
```
