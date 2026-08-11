<template>
  <div>
    <!-- <PageHeader
      :title="isCreate ? 'Create content' : 'Edit content'"
      :description="
        isCreate
          ? 'Draft a page, post, or policy with live search and social previews.'
          : 'Update content, SEO metadata, and publish workflow status.'
      "
    >
      <template #actions>
        <span
          v-if="autosaveLabel"
          class="hidden self-center text-xs text-slate-500 sm:inline"
        >
          {{ autosaveLabel }}
        </span>
        <RouterLink
          v-if="!isCreate && contentId"
          :to="{ name: 'content.versions', params: { id: contentId } }"
          class="h-12 rounded-[12px] border border-slate-300 bg-white px-3 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >
          Versions
        </RouterLink>
        <RouterLink
          v-if="!isCreate && contentId"
          :to="{ name: 'content.review', params: { id: contentId } }"
          class="h-12 rounded-[12px] border border-slate-300 bg-white px-3 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >
          Workflow
        </RouterLink>
        <button
          v-if="!isCreate && ['draft', 'rejected'].includes(form.statusSlug)"
          type="button"
          class="rounded-lg border border-amber-300 bg-amber-50 px-3 py-2 text-sm font-medium text-amber-800 hover:bg-amber-100 disabled:opacity-60"
          :disabled="contentStore.saving"
          @click="submitForReview"
        >
          Submit for review
        </button>
        <button
          v-else-if="!isCreate && form.statusSlug === 'published'"
          type="button"
          class="h-12 rounded-[12px] border border-slate-300 bg-white px-3 text-sm font-medium text-slate-700 hover:bg-slate-50 disabled:opacity-60"
          :disabled="contentStore.saving"
          @click="unpublish"
        >
          Unpublish
        </button>
        <button
          type="button"
          class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
          :disabled="contentStore.saving"
          @click="saveDraft"
        >
          {{ contentStore.saving ? 'Saving…' : 'Save draft' }}
        </button>
      </template>
    </PageHeader> -->
    <Teleport defer to="#page-header-actions">
      <span
        v-if="autosaveLabel"
        class="hidden self-center text-xs text-slate-500 sm:inline"
      >
        {{ autosaveLabel }}
      </span>
      <RouterLink
        v-if="!isCreate && contentId"
        :to="{ name: 'content.versions', params: { id: contentId } }"
        class="rounded-[12px] border border-zinc-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
      >
        Versions
      </RouterLink>
      <RouterLink
        v-if="!isCreate && contentId"
        :to="{ name: 'content.review', params: { id: contentId } }"
        class="rounded-[12px] border border-zinc-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
      >
        Workflow
      </RouterLink>
      <button
        v-if="!isCreate && ['draft', 'rejected'].includes(form.statusSlug)"
        type="button"
        class="rounded-[12px] border border-amber-300 bg-amber-50 px-5 py-2.5 text-sm font-medium text-amber-800 hover:bg-amber-100 disabled:opacity-60"
        :disabled="contentStore.saving"
        @click="submitForReview"
      >
        Submit for review
      </button>
      <button
        v-else-if="!isCreate && form.statusSlug === 'published'"
        type="button"
        class="rounded-[12px] border border-zinc-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50 disabled:opacity-60"
        :disabled="contentStore.saving"
        @click="unpublish"
      >
        Unpublish
      </button>
      <button
        type="button"
        class="rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
        :disabled="contentStore.saving"
        @click="saveDraft"
      >
        {{ contentStore.saving ? 'Saving…' : 'Save draft' }}
      </button>
    </Teleport>

    <ContentSubnav v-if="isCreate" />
    <ContentItemSubnav v-else-if="contentId" :content-id="contentId" />

    <div
      v-if="contentStore.error"
      class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ contentStore.error }}
    </div>
    <div
      v-if="contentStore.successMessage"
      class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700"
    >
      {{ contentStore.successMessage }}
    </div>

    <div
      v-if="contentStore.loading && !isCreate && !contentId"
      class="h-72 animate-pulse rounded-[12px] bg-zinc-100"
    />

    <div v-else class="grid items-start gap-6 xl:grid-cols-12">
      <!-- Main editor column -->
      <div class="space-y-5 xl:col-span-8">
        <!-- Details -->
        <section class="rounded-[12px] bg-white ring-1 ring-zinc-100">
          <header class="border-b border-zinc-100 px-6 py-5 sm:px-8">
            <h2 class="text-base font-semibold text-slate-900">Details</h2>
            <p class="mt-0.5 text-xs text-slate-500">
              Core metadata used across delivery channels.
            </p>
          </header>
          <div class="space-y-6 px-6 py-6 sm:px-8">
            <div class="grid gap-5 sm:grid-cols-2">
              <div>
                <label class="field-label">Content type</label>
                <select
                  v-model="form.content_type_id"
                  class="input"
                  required
                  :disabled="!isCreate"
                >
                  <option value="" disabled>Select type</option>
                  <option
                    v-for="type in contentStore.types"
                    :key="type.uuid"
                    :value="type.uuid"
                  >
                    {{ type.name }}
                  </option>
                </select>
              </div>
              <div>
                <label class="field-label">Slug</label>
                <input
                  v-model="form.slug"
                  type="text"
                  class="input font-mono"
                  placeholder="auto-generated if empty"
                />
              </div>
            </div>

            <div>
              <label class="field-label">Title</label>
              <input
                v-model="form.title"
                type="text"
                class="input text-base font-semibold"
                required
                placeholder="Content title"
              />
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
              <div>
                <label class="field-label">Summary</label>
                <textarea
                  v-model="form.summary"
                  rows="3"
                  class="input"
                  placeholder="Short summary for cards and feeds"
                />
              </div>
              <div>
                <label class="field-label">Excerpt</label>
                <textarea
                  v-model="form.excerpt"
                  rows="3"
                  class="input"
                  placeholder="Optional excerpt"
                />
              </div>
            </div>

            <FeaturedImageField v-model="form.featured_image" />

            <div class="grid gap-5 sm:grid-cols-2">
              <div>
                <div class="mb-1.5 flex items-center justify-between">
                  <label class="field-label mb-0">Categories</label>
                  <span class="text-xs text-slate-400">
                    {{ form.categories.length }} selected
                  </span>
                </div>
                <div
                  class="max-h-44 space-y-1 overflow-y-auto rounded-[12px] bg-zinc-50/80 p-2 ring-1 ring-zinc-100"
                >
                  <label
                    v-for="category in contentStore.categories"
                    :key="category.uuid"
                    class="flex cursor-pointer items-center gap-2.5 rounded-[10px] px-2.5 py-2 text-sm text-slate-700 transition hover:bg-white"
                    :class="
                      form.categories.includes(category.uuid)
                        ? 'bg-white ring-1 ring-brand-100'
                        : ''
                    "
                  >
                    <input
                      type="checkbox"
                      class="rounded border-slate-300 text-brand-600 focus:ring-brand-500"
                      :checked="form.categories.includes(category.uuid)"
                      @change="toggleCategory(category.uuid)"
                    />
                    <span>{{ category.name }}</span>
                  </label>
                  <p
                    v-if="!contentStore.categories.length"
                    class="px-2 py-6 text-center text-xs text-slate-400"
                  >
                    No categories yet
                  </p>
                </div>
              </div>
              <div>
                <label class="field-label">Tags</label>
                <input
                  v-model="form.tagsInput"
                  type="text"
                  class="input"
                  placeholder="news, release, policy"
                />
                <p class="mt-1.5 text-xs text-slate-500">
                  Comma-separated. New tags are created on save.
                </p>
              </div>
            </div>
          </div>
        </section>

        <!-- Body -->
        <section class="rounded-[12px] bg-white ring-1 ring-zinc-100">
          <header class="flex items-center justify-between border-b border-zinc-100 px-6 py-5 sm:px-8">
            <div>
              <h2 class="text-base font-semibold text-slate-900">Body</h2>
              <p class="mt-0.5 text-xs text-slate-500">
                Rich text, markdown, or HTML with media embeds.
              </p>
            </div>
            <label class="flex items-center gap-2 text-xs font-medium text-slate-600">
              <input
                v-model="showLivePreview"
                type="checkbox"
                class="rounded border-slate-300 text-brand-600 focus:ring-brand-500"
              />
              Live preview
            </label>
          </header>
          <div class="px-6 py-6 sm:px-8">
            <ContentBodyEditor
              v-model="form.body_format"
              :html="form.body"
              :markdown="form.markdown"
              :json="form.editor_json"
              @update:html="form.body = $event"
              @update:markdown="form.markdown = $event"
              @update:json="form.editor_json = $event"
              @upload-error="uploadError = $event"
            />
            <p v-if="uploadError" class="mt-2 text-xs text-rose-600">{{ uploadError }}</p>
          </div>
        </section>

        <!-- SEO -->
        <section class="rounded-[12px] bg-white ring-1 ring-zinc-100">
          <button
            type="button"
            class="flex w-full items-center justify-between px-6 py-5 text-left sm:px-8"
            @click="seoOpen = !seoOpen"
          >
            <div>
              <h2 class="text-base font-semibold text-slate-900">SEO &amp; social</h2>
              <p class="mt-0.5 text-xs text-slate-500">
                Search snippets, Open Graph, Twitter, and Schema.org.
              </p>
            </div>
            <span
              class="flex h-8 w-8 items-center justify-center rounded-[10px] text-slate-500 ring-1 ring-zinc-100"
            >
              <svg
                class="h-4 w-4 transition-transform"
                :class="seoOpen ? 'rotate-180' : ''"
                viewBox="0 0 20 20"
                fill="currentColor"
                aria-hidden="true"
              >
                <path
                  fill-rule="evenodd"
                  d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.25a.75.75 0 01-1.06 0L5.21 8.29a.75.75 0 01.02-1.08z"
                  clip-rule="evenodd"
                />
              </svg>
            </span>
          </button>

          <div v-show="seoOpen" class="border-t border-zinc-100 px-6 pb-6 pt-5 sm:px-8">
            <div class="grid gap-5 sm:grid-cols-2">
              <div>
                <label class="field-label">SEO title</label>
                <input v-model="form.seo_title" type="text" class="input" />
              </div>
              <div>
                <label class="field-label">Keywords</label>
                <input
                  v-model="form.seo_keywords"
                  type="text"
                  class="input"
                  placeholder="comma separated"
                />
              </div>
              <div class="sm:col-span-2">
                <label class="field-label">SEO description</label>
                <textarea v-model="form.seo_description" rows="2" class="input" />
              </div>
              <div class="sm:col-span-2">
                <label class="field-label">Canonical URL</label>
                <input
                  v-model="form.canonical_url"
                  type="url"
                  class="input"
                  placeholder="https://…"
                />
              </div>
              <div>
                <label class="field-label">Open Graph title</label>
                <input v-model="form.og_title" type="text" class="input" />
              </div>
              <div>
                <label class="field-label">Open Graph image URL</label>
                <input
                  v-model="form.og_image"
                  type="url"
                  class="input"
                  placeholder="https://…"
                />
              </div>
              <div class="sm:col-span-2">
                <label class="field-label">Open Graph description</label>
                <textarea v-model="form.og_description" rows="2" class="input" />
              </div>
              <div>
                <label class="field-label">Twitter card</label>
                <select v-model="form.twitter_card" class="input">
                  <option value="">Default (summary_large_image)</option>
                  <option value="summary">summary</option>
                  <option value="summary_large_image">summary_large_image</option>
                </select>
              </div>
              <div>
                <label class="field-label">Twitter image URL</label>
                <input
                  v-model="form.twitter_image"
                  type="url"
                  class="input"
                  placeholder="https://…"
                />
              </div>
              <div>
                <label class="field-label">Twitter title</label>
                <input v-model="form.twitter_title" type="text" class="input" />
              </div>
              <div>
                <label class="field-label">Schema.org type</label>
                <input
                  v-model="form.schema_type"
                  type="text"
                  class="input"
                  placeholder="Article"
                />
              </div>
              <div class="sm:col-span-2">
                <label class="field-label">Twitter description</label>
                <textarea v-model="form.twitter_description" rows="2" class="input" />
              </div>
              <div class="sm:col-span-2">
                <label class="field-label">Schema.org JSON (optional override)</label>
                <textarea
                  v-model="form.schema_json_text"
                  rows="4"
                  class="input font-mono text-xs"
                  placeholder='{"@context": "https://schema.org","@type": "Article"}'
                />
              </div>
            </div>
          </div>
        </section>
      </div>

      <!-- Preview sidebar -->
      <aside class="xl:col-span-4">
        <div class="xl:sticky xl:top-4 xl:max-h-[calc(100vh-2rem)] xl:overflow-y-auto">
          <div
            v-if="showLivePreview || showPreviewPanel"
            class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100"
          >
            <div class="border-b border-zinc-100 p-2.5">
              <div class="grid grid-cols-3 gap-1 rounded-[10px] bg-zinc-100 p-1">
                <button
                  v-for="tab in previewTabs"
                  :key="tab.id"
                  type="button"
                  class="rounded-[8px] px-2 py-1.5 text-xs font-medium transition"
                  :class="
                    previewTab === tab.id
                      ? 'bg-white text-slate-900 shadow-sm'
                      : 'text-slate-600 hover:text-slate-900'
                  "
                  @click="previewTab = tab.id"
                >
                  {{ tab.label }}
                </button>
              </div>
            </div>

            <div class="p-5">
              <ContentPreview
                v-if="previewTab === 'content'"
                embedded
                :live="showLivePreview"
                :title="form.title"
                :slug="form.slug"
                :summary="form.summary"
                :excerpt="form.excerpt"
                :body="form.body_format === 'markdown' ? form.markdown : form.body"
                :body-format="form.body_format"
                :featured-image="form.featured_image"
                :seo-title="form.seo_title"
                :seo-description="form.seo_description"
                :keywords="form.seo_keywords"
                :canonical-url="form.canonical_url"
              />

              <SeoPreviewPanel
                v-else
                :mode="previewTab"
                :title="form.title"
                :seo-title="form.seo_title"
                :seo-description="form.seo_description"
                :excerpt="form.excerpt"
                :summary="form.summary"
                :canonical-url="form.canonical_url"
                :featured-image="form.featured_image"
                :og-title="form.og_title"
                :og-description="form.og_description"
                :og-image="form.og_image"
                :twitter-card="form.twitter_card || 'summary_large_image'"
                :twitter-title="form.twitter_title"
                :twitter-description="form.twitter_description"
                :twitter-image="form.twitter_image"
                :schema-type="form.schema_type || 'Article'"
                :schema-json="parsedSchemaJson"
                :slug="form.slug"
              />
            </div>
          </div>

          <button
            v-else
            type="button"
            class="w-full rounded-[12px] border border-dashed border-zinc-300 bg-white px-4 py-10 text-sm font-medium text-slate-600 hover:border-zinc-400 hover:bg-zinc-50"
            @click="showPreviewPanel = true"
          >
            Show preview panel
          </button>
        </div>
      </aside>
    </div>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue';
import { RouterLink, useRoute, useRouter } from 'vue-router';
// import PageHeader from '@/components/ui/PageHeader.vue';
import ContentBodyEditor from '@/modules/content/components/ContentBodyEditor.vue';
import ContentPreview from '@/modules/content/components/ContentPreview.vue';
import ContentItemSubnav from '@/modules/content/components/ContentItemSubnav.vue';
import ContentSubnav from '@/modules/content/components/ContentSubnav.vue';
import FeaturedImageField from '@/modules/content/components/FeaturedImageField.vue';
import SeoPreviewPanel from '@/modules/content/components/SeoPreviewPanel.vue';
import { useContentStore } from '@/modules/content/stores/content';

const props = defineProps({
  mode: { type: String, default: 'edit' },
});

const route = useRoute();
const router = useRouter();
const contentStore = useContentStore();

const isCreate = computed(() => props.mode === 'create' || route.name === 'content.create');
const contentId = computed(() => (isCreate.value ? createdId.value : route.params.id));
const createdId = ref(null);
const showLivePreview = ref(true);
const showPreviewPanel = ref(true);
const seoOpen = ref(false);
const previewTab = ref('content');
const uploadError = ref('');
const autosaveLabel = ref('');
const hydrated = ref(false);
let autosaveTimer = null;

const previewTabs = [
  { id: 'content', label: 'Content' },
  { id: 'search', label: 'Search' },
  { id: 'social', label: 'Social' },
];

const form = reactive({
  content_type_id: '',
  title: '',
  slug: '',
  summary: '',
  excerpt: '',
  body: '',
  markdown: '',
  body_format: 'rich',
  editor_json: null,
  featured_image: '',
  seo_title: '',
  seo_description: '',
  seo_keywords: '',
  canonical_url: '',
  og_title: '',
  og_description: '',
  og_image: '',
  twitter_card: '',
  twitter_title: '',
  twitter_description: '',
  twitter_image: '',
  schema_type: '',
  schema_json_text: '',
  categories: [],
  tagsInput: '',
  statusSlug: 'draft',
});

const parsedSchemaJson = computed(() => {
  const raw = form.schema_json_text?.trim();
  if (!raw) return null;
  try {
    return JSON.parse(raw);
  } catch {
    return raw;
  }
});

onMounted(async () => {
  await contentStore.fetchCatalog();
  if (!isCreate.value) {
    const content = await contentStore.fetchContent(route.params.id);
    hydrate(content);
  } else if (contentStore.types[0]) {
    form.content_type_id = contentStore.types[0].uuid;
  }
  hydrated.value = true;
});

onBeforeUnmount(() => {
  if (autosaveTimer) clearTimeout(autosaveTimer);
});

watch(
  form,
  () => {
    if (!hydrated.value || !contentId.value) return;
    scheduleAutosave();
  },
  { deep: true },
);

function toggleCategory(uuid) {
  const index = form.categories.indexOf(uuid);
  if (index === -1) {
    form.categories.push(uuid);
  } else {
    form.categories.splice(index, 1);
  }
}

function hydrate(content) {
  if (!content) return;
  form.content_type_id = content.type?.uuid || '';
  form.title = content.title || '';
  form.slug = content.slug || '';
  form.summary = content.summary || '';
  form.excerpt = content.excerpt || '';
  form.body = content.body || '';
  form.markdown = content.body_format === 'markdown' ? content.body || '' : '';
  form.body_format = content.body_format || 'rich';
  form.editor_json = content.editor_json || null;
  form.featured_image = content.featured_image || '';
  form.seo_title = content.seo_title || '';
  form.seo_description = content.seo_description || '';
  form.seo_keywords = content.seo_keywords || '';
  form.canonical_url = content.canonical_url || '';
  form.og_title = content.og_title || '';
  form.og_description = content.og_description || '';
  form.og_image = content.og_image || '';
  form.twitter_card = content.twitter_card || '';
  form.twitter_title = content.twitter_title || '';
  form.twitter_description = content.twitter_description || '';
  form.twitter_image = content.twitter_image || '';
  form.schema_type = content.schema_type || '';
  form.schema_json_text = content.schema_json ? JSON.stringify(content.schema_json, null, 2) : '';
  form.categories = (content.categories || []).map((item) => item.uuid);
  form.tagsInput = (content.tags || []).map((item) => item.name).join(', ');
  form.statusSlug = content.status?.slug || 'draft';
}

function buildPayload(status = null) {
  const tags = form.tagsInput
    .split(',')
    .map((tag) => tag.trim())
    .filter(Boolean);

  let schemaJson = null;
  const schemaRaw = form.schema_json_text?.trim();
  if (schemaRaw) {
    try {
      schemaJson = JSON.parse(schemaRaw);
    } catch {
      schemaJson = null;
    }
  }

  return {
    content_type_id: form.content_type_id,
    title: form.title,
    slug: form.slug || null,
    summary: form.summary || null,
    excerpt: form.excerpt || null,
    body: form.body_format === 'markdown' ? form.markdown : form.body,
    body_format: form.body_format,
    editor_json: form.body_format === 'rich' ? form.editor_json : null,
    featured_image: form.featured_image || null,
    seo_title: form.seo_title || null,
    seo_description: form.seo_description || null,
    seo_keywords: form.seo_keywords || null,
    canonical_url: form.canonical_url || null,
    og_title: form.og_title || null,
    og_description: form.og_description || null,
    og_image: form.og_image || null,
    twitter_card: form.twitter_card || null,
    twitter_title: form.twitter_title || null,
    twitter_description: form.twitter_description || null,
    twitter_image: form.twitter_image || null,
    schema_type: form.schema_type || null,
    schema_json: schemaJson,
    categories: form.categories,
    tags,
    status: status || 'draft',
  };
}

function scheduleAutosave() {
  if (autosaveTimer) clearTimeout(autosaveTimer);
  autosaveLabel.value = 'Unsaved changes…';
  autosaveTimer = setTimeout(async () => {
    try {
      await contentStore.autosaveContent(contentId.value, buildPayload());
      autosaveLabel.value = `Autosaved ${new Date().toLocaleTimeString()}`;
    } catch {
      autosaveLabel.value = 'Autosave failed';
    }
  }, 2000);
}

async function ensureCreated() {
  if (contentId.value) return contentId.value;
  if (!form.title || !form.content_type_id) {
    throw new Error('Title and content type are required before saving.');
  }
  const content = await contentStore.createContent(buildPayload('draft'));
  createdId.value = content.uuid;
  await router.replace({ name: 'content.edit', params: { id: content.uuid } });
  return content.uuid;
}

async function saveDraft() {
  try {
    if (isCreate.value && !createdId.value) {
      await ensureCreated();
      contentStore.successMessage = 'Draft created successfully.';
      return;
    }
    await contentStore.updateContent(contentId.value, buildPayload('draft'));
    form.statusSlug = 'draft';
  } catch {
    /* store handles error */
  }
}

async function submitForReview() {
  try {
    const id = await ensureCreated();
    await contentStore.updateContent(id, buildPayload('draft'));
    await contentStore.runWorkflow('submit', id, { comments: 'Submitted from editor' });
    form.statusSlug = 'pending_review';
  } catch {
    /* store handles error */
  }
}

async function unpublish() {
  try {
    await contentStore.unpublishContent(contentId.value);
    form.statusSlug = 'draft';
  } catch {
    /* store handles error */
  }
}
</script>

<style scoped>
.field-label {
  display: block;
  margin-bottom: 0.375rem;
  font-size: 0.875rem;
  font-weight: 500;
  color: #334155;
}

.input {
  width: 100%;
  height: 3rem;
  border-radius: 0.75rem;
  border: 1px solid #e4e4e7;
  background: #fff;
  padding: 0.5rem 0.875rem;
  font-size: 0.875rem;
  color: #0f172a;
  outline: none;
  box-shadow: none;
  transition: border-color 0.15s ease;
}

.input::placeholder {
  color: #a1a1aa;
}

.input:focus {
  border-color: var(--color-brand-500, #f97316);
  outline: none;
  box-shadow: none;
}

.input:disabled {
  cursor: not-allowed;
  background: #fafafa;
  color: #64748b;
}

textarea.input {
  height: auto;
  min-height: 5.5rem;
  resize: vertical;
  padding-top: 0.75rem;
  padding-bottom: 0.75rem;
}

select.input {
  cursor: pointer;
}
</style>
