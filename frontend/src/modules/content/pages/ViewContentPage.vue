<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <template v-if="content">
        <RouterLink
          :to="{ name: 'content.review', params: { id: content.uuid } }"
          class="rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
        >
          Review / Approve
        </RouterLink>
        <button
          v-if="content.status?.slug === 'published'"
          type="button"
          class="rounded-[12px] border border-zinc-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50 disabled:opacity-60"
          :disabled="contentStore.saving"
          @click="unpublish"
        >
          Unpublish
        </button>
        <RouterLink
          :to="{ name: 'content.edit', params: { id: content.uuid } }"
          class="rounded-[12px] border border-zinc-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
        >
          Edit
        </RouterLink>
        <RouterLink
          :to="{ name: 'content.versions', params: { id: content.uuid } }"
          class="rounded-[12px] border border-zinc-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
        >
          Version history
        </RouterLink>
        <button
          v-if="content.deleted_at"
          type="button"
          class="rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
          :disabled="contentStore.saving"
          @click="restore"
        >
          Restore
        </button>
        <button
          v-else
          type="button"
          class="rounded-[12px] bg-rose-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-rose-700"
          @click="showDelete = true"
        >
          Delete
        </button>
      </template>
    </Teleport>

    <ContentItemSubnav v-if="contentId" :content-id="contentId" />

    <div
      v-if="contentStore.successMessage"
      class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700"
    >
      {{ contentStore.successMessage }}
    </div>
    <div
      v-if="contentStore.error"
      class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ contentStore.error }}
    </div>

    <div
      v-if="contentStore.loading && !content"
      class="grid gap-4 lg:grid-cols-3"
    >
      <div class="h-80 animate-pulse rounded-[12px] bg-zinc-100 lg:col-span-2" />
      <div class="h-80 animate-pulse rounded-[12px] bg-zinc-100" />
    </div>

    <div
      v-else-if="!content"
      class="rounded-[12px] bg-white px-6 py-16 text-center ring-1 ring-zinc-100"
    >
      <p class="text-sm font-medium text-slate-900">Content not found</p>
      <p class="mt-1 text-sm text-slate-500">This entry could not be loaded.</p>
      <RouterLink
        :to="{ name: 'content.index' }"
        class="mt-4 inline-flex rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
      >
        Back to content
      </RouterLink>
    </div>

    <div v-else class="grid gap-4 lg:grid-cols-3">
      <section class="space-y-4 lg:col-span-2">
        <div class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100">
          <div
            v-if="content.featured_image"
            class="aspect-[21/9] bg-slate-100"
          >
            <img
              :src="content.featured_image"
              :alt="content.title"
              class="h-full w-full object-cover"
            />
          </div>

          <div class="p-6">
            <div class="mb-4 flex flex-wrap items-center gap-2">
              <StatusBadge :status="content.status?.slug" :label="content.status?.name" />
              <span class="rounded-md bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600">
                {{ content.type?.name || 'Untitled type' }}
              </span>
              <span class="rounded-md bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600">
                v{{ content.version || 1 }}
              </span>
              <span
                v-if="content.is_featured"
                class="rounded-md bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700"
              >
                Featured
              </span>
              <span
                v-if="content.deleted_at"
                class="rounded-md bg-rose-50 px-2 py-0.5 text-xs font-medium text-rose-700"
              >
                Deleted
              </span>
            </div>

            <h2 class="text-xl font-semibold tracking-tight text-slate-900">
              {{ content.title }}
            </h2>
            <p
              v-if="content.summary || content.excerpt"
              class="mt-2 text-sm leading-relaxed text-slate-600"
            >
              {{ content.summary || content.excerpt }}
            </p>

            <div
              class="mt-4 flex flex-wrap gap-x-4 gap-y-1 border-t border-slate-100 pt-4 text-xs text-slate-500"
            >
              <span v-if="content.slug" class="font-mono">/{{ content.slug }}</span>
              <span v-if="content.publisher?.full_name">
                Published by {{ content.publisher.full_name }}
              </span>
              <span v-else-if="content.creator?.full_name">
                Created by {{ content.creator.full_name }}
              </span>
              <span>Updated {{ formatDate(content.updated_at) }}</span>
            </div>
          </div>
        </div>

        <div class="rounded-[12px] bg-white ring-1 ring-zinc-100">
          <div class="flex items-center justify-between border-b border-zinc-100 px-6 py-3.5">
            <h3 class="text-sm font-semibold text-slate-900">Body</h3>
            <span
              class="rounded-full bg-slate-100 px-2.5 py-0.5 text-[10px] font-medium uppercase tracking-wide text-slate-500"
            >
              {{ bodyFormatLabel }}
            </span>
          </div>
          <div class="p-6">
            <div
              v-if="!content.body"
              class="rounded-lg border border-dashed border-slate-200 bg-slate-50 px-4 py-10 text-center text-sm text-slate-500"
            >
              No body content yet.
            </div>
            <ContentPreview
              v-else
              body-only
              :body="content.body"
              :body-format="content.body_format || 'rich'"
            />
          </div>
        </div>

        <WorkflowTimeline :history="contentStore.workflowHistory" />
      </section>

      <aside class="space-y-4">
        <div class="rounded-[12px] bg-white p-5 ring-1 ring-zinc-100">
          <h3 class="mb-4 text-sm font-semibold text-slate-900">Details</h3>
          <dl class="space-y-4">
            <div>
              <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Slug</dt>
              <dd class="mt-1 break-all font-mono text-sm text-slate-900">{{ content.slug }}</dd>
            </div>
            <div>
              <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">
                Categories
              </dt>
              <dd class="mt-1.5 flex flex-wrap gap-1.5">
                <span
                  v-for="category in resolvedCategories"
                  :key="category.uuid"
                  class="rounded-md bg-slate-100 px-2 py-0.5 text-xs text-slate-600"
                >
                  {{ category.name }}
                </span>
                <span v-if="!resolvedCategories.length" class="text-sm text-slate-500">—</span>
              </dd>
            </div>
            <div>
              <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Tags</dt>
              <dd class="mt-1.5 flex flex-wrap gap-1.5">
                <span
                  v-for="tag in content.tags || []"
                  :key="tag.uuid"
                  class="rounded-md bg-slate-100 px-2 py-0.5 text-xs text-slate-600"
                >
                  {{ tag.name }}
                </span>
                <span v-if="!(content.tags || []).length" class="text-sm text-slate-500">—</span>
              </dd>
            </div>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">
                  Published
                </dt>
                <dd class="mt-1 text-sm text-slate-900">{{ formatDate(content.published_at) }}</dd>
              </div>
              <div>
                <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Updated</dt>
                <dd class="mt-1 text-sm text-slate-900">{{ formatDate(content.updated_at) }}</dd>
              </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Views</dt>
                <dd class="mt-1 text-sm font-medium text-slate-900">
                  {{ content.view_count ?? 0 }}
                </dd>
              </div>
              <div>
                <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Version</dt>
                <dd class="mt-1 text-sm font-medium text-slate-900">
                  v{{ content.version || 1 }}
                </dd>
              </div>
            </div>
            <div v-if="content.excerpt">
              <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Excerpt</dt>
              <dd class="mt-1 text-sm leading-relaxed text-slate-700">{{ content.excerpt }}</dd>
            </div>
          </dl>
        </div>

        <div class="rounded-[12px] bg-white p-5 ring-1 ring-zinc-100">
          <div class="mb-4 flex items-center justify-between gap-2">
            <h3 class="text-sm font-semibold text-slate-900">SEO preview</h3>
            <RouterLink
              :to="{ name: 'content.edit', params: { id: content.uuid } }"
              class="text-xs font-medium text-brand-600 hover:text-brand-700"
            >
              Edit SEO
            </RouterLink>
          </div>
          <SeoPreviewPanel
            mode="search"
            :title="content.title"
            :seo-title="content.seo_title"
            :seo-description="content.seo_description"
            :excerpt="content.excerpt"
            :summary="content.summary"
            :canonical-url="content.canonical_url"
            :featured-image="content.featured_image"
            :slug="content.slug"
            :type-slug="content.type?.slug || 'page'"
          />

          <dl class="mt-5 space-y-3 border-t border-slate-100 pt-4">
            <div>
              <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">
                SEO keywords
              </dt>
              <dd class="mt-1 text-sm text-slate-900">{{ content.seo_keywords || '—' }}</dd>
            </div>
            <div>
              <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">
                Canonical URL
              </dt>
              <dd class="mt-1 break-all text-sm text-slate-900">
                {{ content.canonical_url || '—' }}
              </dd>
            </div>
            <div class="grid grid-cols-2 gap-3">
              <div>
                <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">
                  Twitter card
                </dt>
                <dd class="mt-1 text-sm text-slate-900">
                  {{ content.twitter_card || 'summary_large_image' }}
                </dd>
              </div>
              <div>
                <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">
                  Schema type
                </dt>
                <dd class="mt-1 text-sm text-slate-900">{{ content.schema_type || 'Article' }}</dd>
              </div>
            </div>
          </dl>
        </div>

        <div class="rounded-[12px] bg-white p-5 ring-1 ring-zinc-100">
          <h3 class="mb-4 text-sm font-semibold text-slate-900">Social &amp; Open Graph</h3>
          <SeoPreviewPanel
            mode="social"
            :title="content.title"
            :seo-title="content.seo_title"
            :seo-description="content.seo_description"
            :excerpt="content.excerpt"
            :summary="content.summary"
            :canonical-url="content.canonical_url"
            :featured-image="content.featured_image"
            :og-title="content.og_title"
            :og-description="content.og_description"
            :og-image="content.og_image"
            :twitter-card="content.twitter_card || 'summary_large_image'"
            :twitter-title="content.twitter_title"
            :twitter-description="content.twitter_description"
            :twitter-image="content.twitter_image"
            :schema-type="content.schema_type || 'Article'"
            :schema-json="content.schema_json"
            :slug="content.slug"
            :type-slug="content.type?.slug || 'page'"
          />
        </div>
      </aside>
    </div>

    <DeleteConfirmation
      :open="showDelete"
      title="Delete content"
      :message="`Soft delete ${content?.title || 'this content'}?`"
      confirm-label="Delete"
      :loading="contentStore.saving"
      @cancel="showDelete = false"
      @confirm="confirmDelete"
    />
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { RouterLink, useRoute, useRouter } from 'vue-router';
import DeleteConfirmation from '@/modules/users/components/DeleteConfirmation.vue';
import StatusBadge from '@/modules/content/components/StatusBadge.vue';
import ContentItemSubnav from '@/modules/content/components/ContentItemSubnav.vue';
import ContentPreview from '@/modules/content/components/ContentPreview.vue';
import SeoPreviewPanel from '@/modules/content/components/SeoPreviewPanel.vue';
import WorkflowTimeline from '@/modules/content/components/WorkflowTimeline.vue';
import { useContentStore } from '@/modules/content/stores/content';

const route = useRoute();
const router = useRouter();
const contentStore = useContentStore();
const showDelete = ref(false);

const contentId = computed(() => String(route.params.id || ''));
const content = computed(() => {
  const current = contentStore.currentContent;
  if (!current) return null;
  if (contentId.value && current.uuid !== contentId.value) return null;
  return current;
});

const resolvedCategories = computed(() => {
  if (!content.value) return [];
  if (content.value.categories?.length) return content.value.categories;
  if (content.value.category) return [content.value.category];
  return [];
});

const bodyFormatLabel = computed(() => {
  const format = content.value?.body_format;
  if (format === 'markdown') return 'Markdown';
  if (format === 'html') return 'HTML';
  return 'Rich text';
});

async function loadContent(id) {
  if (!id) return;
  try {
    await contentStore.fetchContent(id);
    await contentStore.fetchWorkflowHistory(id).catch(() => {});
  } catch {
    /* store handles error */
  }
}

onMounted(() => {
  loadContent(contentId.value);
});

watch(contentId, (id) => {
  loadContent(id);
});

function formatDate(value) {
  if (!value) return '—';
  return new Date(value).toLocaleString();
}

async function unpublish() {
  await contentStore.unpublishContent(contentId.value);
}

async function restore() {
  await contentStore.restoreContent(contentId.value);
}

async function confirmDelete() {
  await contentStore.deleteContent(contentId.value);
  showDelete.value = false;
  await router.push({ name: 'content.index' });
}
</script>
