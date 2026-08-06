# Content CMS — Database Documentation

**Engine:** MySQL 8+  
**Migrations:** `2026_08_03_300000` … `2026_08_03_306000`

## Entity relationship (logical)

```
content_types ──┐
content_statuses ┼── contents ──┬── content_category (pivot) ── content_categories (self parent)
                 │              ├── content_tag (pivot) ── content_tags
                 │              ├── content_versions
                 │              └── content_workflow_histories
media_folders (self parent) ── media_library
cms_api_keys
```

## Tables

### `content_types`
System/custom types (`page`, `blog`, …). Soft deletes. Unique `slug`.

### `content_statuses`
Workflow + lifecycle statuses (`draft`, `pending_review`, `reviewed`, `approved`, `published`, `rejected`, `archived`, `scheduled`, …). Unique `slug`.

### `content_categories`
Nested categories (`parent_id` nullable FK). SEO title/description, `is_active`, `sort_order`. Soft deletes.

### `content_tags`
Flat tags with SEO fields, `is_active`, `sort_order`. Soft deletes.

### `contents`
Primary content rows.

| Column group | Columns |
|--------------|---------|
| Identity | `id`, `uuid`, `title`, `slug` (unique per type) |
| Relations | `content_type_id`, `content_status_id`, `content_category_id` (legacy single), pivots for multi-category/tags |
| Body | `summary`, `excerpt`, `body`, `body_format`, `editor_json`, `featured_image` |
| SEO | `seo_title`, `seo_description`, `seo_keywords`, `canonical_url` |
| Social | `og_title`, `og_description`, `og_image`, `twitter_*`, `schema_type`, `schema_json` |
| Delivery | `is_featured`, `view_count`, `last_viewed_at`, `published_at`, `published_by` |
| Workflow | `current_workflow_level`, `last_workflow_comment`, submitted/reviewed/approved/rejected by+at |
| Editor | `version`, `last_autosaved_at`, `metadata`, audit FKs, soft deletes |

**Indexes:** type+status, category+status, featured, published_at, view_count, title, workflow actors as applicable.

### `content_category` / `content_tag`
Many-to-many pivots between contents and categories/tags.

### `content_versions`
Immutable snapshots: `version`, `status`, `snapshot` (JSON), `reason`, `created_by`.

### `content_workflow_histories`
Transition audit: action, from/to status, actor, comments, level, timestamps.

### `media_folders`
Nested folders (`parent_id`), soft deletes.

### `media_library`
File assets with `media_group_uuid` + `version` for replace history; checksum, type, sizes, alt/caption, disk/path URLs, soft deletes.

### `cms_api_keys`
Headless credentials: `key_prefix`, `key_hash` (SHA-256), `abilities` JSON, `is_active`, `expires_at`, `last_used_at`, soft deletes.

## Relationships (Eloquent)

| Model | Relations |
|-------|-----------|
| Content | type, status, category, categories, tags, versions, workflowHistories, creator, publisher, workflow actors |
| ContentCategory | parent, children, contents |
| ContentTag | contents |
| MediaLibraryItem | folder, uploader |
| MediaFolder | parent, children, media |
| CmsApiKey | creator |

## Seed data

`ContentFoundationSeeder` — system types and statuses.  
Permissions/roles via `RolesAndPermissionsSeeder`.

## Migration notes

- Prefer migrations over manual DDL.
- Dual category model: keep `content_category_id` in sync with primary pivot membership when APIs update categories.
- Sitemap generation reads published contents (limit 5000) plus optional category/tag URLs from config.
