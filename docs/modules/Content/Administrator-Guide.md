# Content CMS — Administrator Guide

For **platform admins**, **content managers**, and operations.

## Access & roles

| Role | Typical responsibility |
|------|------------------------|
| `super-admin` | Full CMS + API keys |
| `content-manager` | Taxonomy, media, approve/reject; **cannot publish** (by seed) |
| `company-admin` | Publish path without delete |
| `content-editor` / `content-writer` | Editorial pipeline |

Assign Spatie roles; never hardcode permissions in UI.

## Operational surfaces

| UI route | Purpose |
|----------|---------|
| `/content` | Dashboard metrics |
| `/content/list` | Content table |
| `/content/workflow` | Approval queue |
| `/content/media` | Media library |
| `/content/categories` · `/tree` | Category management |
| `/content/tags` | Tag manager |
| `/content/delivery` | Public/private content & search preview |
| `/content/seo` | Sitemap/robots inspector + SEO checker |
| `/content/api-explorer` | Live API explorer + API key minting |

## Workflow operations

1. Monitor **Approval Queue** daily.
2. Enforce linear transitions; do not grant skip paths in custom code.
3. Require reject comments.
4. Publishing: only users with `content.publish` via workflow publish after approve.
5. Archive / return-to-draft for retired content.

## Taxonomy

- Prefer depth-limited category trees; avoid cycles (API blocks descendant-as-parent).
- Use bulk activate/deactivate for seasonal taxonomies.
- Category/tag SEO fields surface on public delivery listings.

## Media operations

- Organize by folder before large campaigns.
- Replacing a file creates a new version under the same `media_group_uuid`.
- Non-empty folders cannot be deleted.
- Disk is application public disk for CMS assets — confirm CDN/cache headers in production.

## SEO & discovery

1. Set `CMS_SITE_URL` (and optional `CMS_CONTENT_PATH_PATTERN`) in `.env`.
2. Verify `GET /sitemap.xml` and `GET /robots.txt` on the API host (or reverse-proxy them to the public apex).
3. Use SEO Tools to refresh sitemap JSON / robots text.
4. Featured / latest / popular power consumer apps — featured is editorial (`is_featured`); popular uses `view_count`.

## Headless consumers

### Public
Integrate against `/api/v1/cms/public/*`. No credentials. Cache responses at the edge; account for view increments on show.

### Private / preview
- Dashboard session (Sanctum cookie/token) for staff preview, **or**
- CMS API key from **API Explorer → API Keys** (store plaintext once).
- Send `X-CMS-Api-Key: cms_…`.
- Revoke compromised keys immediately (`DELETE /content/api-keys/{uuid}`).

## Monitoring checklist

- [ ] Workflow queue backlog
- [ ] Failed media uploads / disk space
- [ ] Sitemap entry count vs published content
- [ ] API key last-used timestamps
- [ ] Activity log for publish/permission changes

## Configuration reference

```env
CMS_SITE_URL=https://www.example.com
CMS_CONTENT_PATH_PATTERN=/content/{type}/{slug}
CMS_SITEMAP_INCLUDE_CATEGORIES=true
CMS_SITEMAP_INCLUDE_TAGS=true
```

See `config/cms.php`.
