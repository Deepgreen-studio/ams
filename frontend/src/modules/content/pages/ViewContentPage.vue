<template>
  <div>
    <PageHeader
      :title="content?.title || 'Content details'"
      description="Headless CMS content entry details."
    >
      <template #actions>
        <template v-if="content">
          <RouterLink
            :to="{ name: 'content.review', params: { id: content.uuid } }"
            class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
          >
            Review / Approve
          </RouterLink>
          <button
            v-if="content.status?.slug === 'published'"
            type="button"
            class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 disabled:opacity-60"
            :disabled="contentStore.saving"
            @click="unpublish"
          >
            Unpublish
          </button>
          <RouterLink
            :to="{ name: 'content.versions', params: { id: content.uuid } }"
            class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
          >
            Version history
          </RouterLink>
          <RouterLink
            :to="{ name: 'content.edit', params: { id: content.uuid } }"
            class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
          >
            Edit
          </RouterLink>
          <button
            v-if="content.deleted_at"
            type="button"
            class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
            :disabled="contentStore.saving"
            @click="restore"
          >
            Restore
          </button>
          <button
            v-else
            type="button"
            class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-medium text-white hover:bg-rose-700"
            @click="showDelete = true"
          >
            Delete
          </button>
        </template>
      </template>
    </PageHeader>

    <ContentItemSubnav v-if="content" :content-id="content.uuid" />

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
      class="h-64 animate-pulse rounded-xl bg-slate-100"
    />
    <div v-else-if="content" class="space-y-4">
      <div class="rounded-xl border border-slate-200 bg-white p-6">
        <div class="mb-4 flex flex-wrap items-center gap-2">
          <StatusBadge :status="content.status?.slug" :label="content.status?.name" />
          <span class="rounded-md bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600">{{
            content.type?.name
          }}</span>
          <span class="rounded-md bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600"
            >v{{ content.version || 1 }}</span
          >
          <span
            v-if="content.is_featured"
            class="rounded-md bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700"
            >Featured</span
          >
        </div>
        <dl class="grid gap-4 md:grid-cols-2">
          <div>
            <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Slug</dt>
            <dd class="mt-1 text-sm text-slate-900">{{ content.slug }}</dd>
          </div>
          <div>
            <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Categories</dt>
            <dd class="mt-1 flex flex-wrap gap-2">
              <span
                v-for="category in content.categories?.length
                  ? content.categories
                  : content.category
                    ? [content.category]
                    : []"
                :key="category.uuid"
                class="rounded-md bg-slate-100 px-2 py-0.5 text-xs text-slate-600"
              >
                {{ category.name }}
              </span>
              <span
                v-if="!(content.categories || []).length && !content.category"
                class="text-sm text-slate-500"
                >—</span
              >
            </dd>
          </div>
          <div>
            <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Published at</dt>
            <dd class="mt-1 text-sm text-slate-900">{{ formatDate(content.published_at) }}</dd>
          </div>
          <div>
            <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Updated</dt>
            <dd class="mt-1 text-sm text-slate-900">{{ formatDate(content.updated_at) }}</dd>
          </div>
          <div class="md:col-span-2">
            <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Excerpt</dt>
            <dd class="mt-1 text-sm text-slate-700">{{ content.excerpt || '—' }}</dd>
          </div>
          <div class="md:col-span-2">
            <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Tags</dt>
            <dd class="mt-1 flex flex-wrap gap-2">
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
        </dl>
      </div>

      <div class="rounded-xl border border-slate-200 bg-white p-6">
        <h2 class="mb-3 text-sm font-semibold text-slate-900">Body</h2>
        <div class="prose max-w-none whitespace-pre-wrap text-sm text-slate-700">
          {{ content.body || '—' }}
        </div>
      </div>

      <div class="rounded-xl border border-slate-200 bg-white p-6">
        <h2 class="mb-3 text-sm font-semibold text-slate-900">SEO</h2>
        <dl class="grid gap-4 md:grid-cols-2">
          <div>
            <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">SEO title</dt>
            <dd class="mt-1 text-sm text-slate-900">{{ content.seo_title || '—' }}</dd>
          </div>
          <div>
            <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">SEO keywords</dt>
            <dd class="mt-1 text-sm text-slate-900">{{ content.seo_keywords || '—' }}</dd>
          </div>
          <div class="md:col-span-2">
            <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">
              SEO description
            </dt>
            <dd class="mt-1 text-sm text-slate-700">{{ content.seo_description || '—' }}</dd>
          </div>
          <div>
            <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">
              Canonical URL
            </dt>
            <dd class="mt-1 break-all text-sm text-slate-900">
              {{ content.canonical_url || '—' }}
            </dd>
          </div>
          <div>
            <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Views</dt>
            <dd class="mt-1 text-sm text-slate-900">{{ content.view_count ?? 0 }}</dd>
          </div>
          <div>
            <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">
              Open Graph title
            </dt>
            <dd class="mt-1 text-sm text-slate-900">{{ content.og_title || '—' }}</dd>
          </div>
          <div>
            <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Twitter card</dt>
            <dd class="mt-1 text-sm text-slate-900">
              {{ content.twitter_card || 'summary_large_image' }}
            </dd>
          </div>
          <div>
            <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Schema type</dt>
            <dd class="mt-1 text-sm text-slate-900">{{ content.schema_type || 'Article' }}</dd>
          </div>
        </dl>
      </div>

      <WorkflowTimeline :history="contentStore.workflowHistory" />
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
import { computed, onMounted, ref } from 'vue';
import { RouterLink, useRoute, useRouter } from 'vue-router';
import PageHeader from '@/components/ui/PageHeader.vue';
import DeleteConfirmation from '@/modules/users/components/DeleteConfirmation.vue';
import StatusBadge from '@/modules/content/components/StatusBadge.vue';
import ContentItemSubnav from '@/modules/content/components/ContentItemSubnav.vue';
import WorkflowTimeline from '@/modules/content/components/WorkflowTimeline.vue';
import { useContentStore } from '@/modules/content/stores/content';

const route = useRoute();
const router = useRouter();
const contentStore = useContentStore();
const showDelete = ref(false);
const content = computed(() => contentStore.currentContent);

onMounted(async () => {
  await contentStore.fetchContent(route.params.id);
  await contentStore.fetchWorkflowHistory(route.params.id);
});

function formatDate(value) {
  if (!value) return '—';
  return new Date(value).toLocaleString();
}

async function unpublish() {
  await contentStore.unpublishContent(route.params.id);
}

async function restore() {
  await contentStore.restoreContent(route.params.id);
}

async function confirmDelete() {
  await contentStore.deleteContent(route.params.id);
  showDelete.value = false;
  await router.push({ name: 'content.index' });
}
</script>
