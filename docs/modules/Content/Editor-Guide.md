# Content CMS — Editor Guide

For **content writers** and **content editors** using the AMS admin UI.

## Getting started

1. Sign in with a role that has at least `content.view` + `content.create` (e.g. `content-writer`).
2. Open **Content → Content** (`/content/list`) or **Dashboard** (`/content`).
3. Click **Create** to open the editor.

## Create & edit content

| Field | Guidance |
|-------|----------|
| Type | Page, blog, FAQ, etc. Choose before first save. |
| Title / slug | Slug auto-derives if blank; unique per type. |
| Summary / excerpt | Used in cards and SEO fallbacks. |
| Body | TipTap rich editor (HTML) or Markdown / JSON modes. |
| Featured image | Pick from media library or paste URL. |
| Categories / tags | Multi-select; tags may be entered as comma-separated names. |
| SEO block | Meta title/description/keywords, canonical, OG, Twitter, Schema type/JSON. |

### Autosave
After the first create, edits debounce (~2s) to `POST /content/{id}/autosave`. Autosave does **not** create a version snapshot.

### Live preview
Toggle **Live preview** for body rendering. The **SEO Preview** panel shows Google snippet, Open Graph, Twitter card, and Schema.org JSON-LD.

## Workflow (writers & editors)

```
Draft → Submit for review → Reviewed → Approved → Published
                ↓
            Rejected → return to draft / revise
```

| Action | Who | UI |
|--------|-----|-----|
| Save draft | Writer+ | Editor |
| Submit for review | `content.submit` | Editor / Review |
| Mark reviewed | `content.review` | Approval Queue / Review page |
| Approve | `content.approve` | Approval Queue / Review |
| Reject | Requires comment | Review (API currently allows `content.view` — prefer reviewer roles) |
| Publish | `content.publish` | Workflow publish (Admin) |

Direct publish from draft without approval is blocked by workflow rules.

## Version history

Open **Versions** on a content item to:
- Browse timeline of snapshots (create/update/publish)
- Compare two versions
- Restore a prior snapshot (creates a new version)

## Media

- **Media Library** (`/content/media`): folders, multi-upload, replace file (version history), preview/download.
- Editor uploads are stored in the library automatically.
- Prefer meaningful `alt` text for accessibility and SEO images.

## SEO checklist (editors)

1. Unique meta title (~50–60 chars).
2. Meta description ~150–160 chars.
3. Canonical URL when content is mirrored.
4. OG image (1200×630 recommended) distinct from decorative thumbnails when possible.
5. Twitter card type `summary_large_image` for marketing pages.
6. Validate Schema JSON if overriding the default Article payload.
7. Use **SEO Tools** / **Delivery Preview** after publish to inspect live packages.

## Tips

- Use categories for browse hierarchy; tags for cross-cutting topics.
- Keep body formats consistent per type (prefer rich HTML for CMS pages).
- After restore from version history, re-check workflow status before publishing.
