<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <RouterLink
        :to="{ name: 'support.knowledge.index' }"
        class="inline-flex items-center gap-2 rounded-[12px] border border-zinc-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
      >
        All articles
      </RouterLink>
      <button
        v-if="article && article.status !== 'published'"
        type="button"
        class="inline-flex items-center gap-2 rounded-[12px] border border-zinc-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50 disabled:opacity-60"
        :disabled="store.saving"
        @click="publish"
      >
        Publish
      </button>
      <RouterLink
        v-if="article"
        :to="{ name: 'support.knowledge.edit', params: { id: article.uuid } }"
        class="inline-flex items-center gap-2 rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
      >
        Edit
      </RouterLink>
    </Teleport>

    <SupportSubnav />

    <div v-if="store.loading && !article" class="h-64 animate-pulse rounded-[12px] bg-zinc-100" />

    <div
      v-else-if="!article"
      class="rounded-[12px] bg-white px-6 py-16 text-center ring-1 ring-zinc-100"
    >
      <p class="text-sm font-medium text-slate-900">Unable to load this article</p>
      <p class="mt-1 text-xs text-slate-500">It may have been removed, or the request failed.</p>
      <div class="mt-6 flex flex-wrap items-center justify-center gap-2">
        <button
          type="button"
          class="rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
          @click="loadArticle"
        >
          Retry
        </button>
        <RouterLink
          :to="{ name: 'support.knowledge.index' }"
          class="rounded-[12px] border border-zinc-200 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
        >
          Back to articles
        </RouterLink>
      </div>
    </div>

    <div v-else class="grid gap-4 lg:grid-cols-3">
      <article class="rounded-[12px] bg-white p-6 ring-1 ring-zinc-100 sm:p-8 lg:col-span-2">
        <div class="mb-4 flex flex-wrap items-center gap-2">
          <span class="inline-flex items-center rounded-full bg-slate-50 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-wide text-slate-600 ring-1 ring-slate-500/20">
            {{ article.type_label || article.type }}
          </span>
          <span
            class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset"
            :class="statusTone(article.status)"
          >
            {{ statusLabel(article.status) }}
          </span>
          <span
            v-if="article.content"
            class="inline-flex items-center rounded-full bg-sky-50 px-2.5 py-1 text-xs font-medium text-sky-700 ring-1 ring-sky-100"
          >
            CMS linked · v{{ article.content.version }}
          </span>
        </div>

        <h1 class="text-xl font-semibold tracking-tight text-slate-900">{{ article.title }}</h1>
        <p v-if="article.summary" class="mt-2 text-sm text-slate-600">{{ article.summary }}</p>

        <div
          v-if="article.type === 'video' && article.video_url"
          class="mt-6 overflow-hidden rounded-[12px] bg-zinc-50 p-4 ring-1 ring-zinc-100"
        >
          <a :href="article.video_url" target="_blank" rel="noopener" class="text-sm font-medium text-brand-700 hover:text-brand-600">
            Open video
          </a>
        </div>

        <div class="prose prose-sm mt-6 max-w-none text-slate-800" v-html="safeBody"></div>

        <div class="mt-8 border-t border-zinc-100 pt-5">
          <p class="mb-3 text-sm font-medium text-slate-900">Was this helpful?</p>
          <div class="flex flex-wrap gap-2">
            <button
              type="button"
              class="rounded-[12px] px-3.5 py-2 text-sm font-medium"
              :class="article.user_feedback === true ? 'bg-emerald-100 text-emerald-800' : 'bg-zinc-100 text-slate-700 hover:bg-zinc-200'"
              @click="store.submitFeedback(article.uuid, true)"
            >
              Helpful ({{ article.helpful_count ?? 0 }})
            </button>
            <button
              type="button"
              class="rounded-[12px] px-3.5 py-2 text-sm font-medium"
              :class="article.user_feedback === false ? 'bg-rose-100 text-rose-800' : 'bg-zinc-100 text-slate-700 hover:bg-zinc-200'"
              @click="store.submitFeedback(article.uuid, false)"
            >
              Not helpful ({{ article.not_helpful_count ?? 0 }})
            </button>
          </div>
        </div>
      </article>

      <aside class="space-y-4">
        <section class="rounded-[12px] bg-white p-6 ring-1 ring-zinc-100">
          <h2 class="text-base font-semibold text-slate-900">Details</h2>
          <dl class="mt-4 space-y-3 text-sm">
            <div class="flex items-center justify-between gap-3">
              <dt class="text-slate-500">Category</dt>
              <dd class="font-medium text-slate-900">{{ article.category?.name || '—' }}</dd>
            </div>
            <div class="flex items-start justify-between gap-3">
              <dt class="text-slate-500">Tags</dt>
              <dd class="text-right font-medium text-slate-900">
                {{ article.tags?.map((tag) => tag.name).join(', ') || '—' }}
              </dd>
            </div>
            <div class="flex items-center justify-between gap-3">
              <dt class="text-slate-500">Views</dt>
              <dd class="font-medium text-slate-900">{{ article.view_count ?? 0 }}</dd>
            </div>
            <div class="flex items-center justify-between gap-3">
              <dt class="text-slate-500">Version</dt>
              <dd class="font-medium text-slate-900">{{ article.version ?? 1 }}</dd>
            </div>
          </dl>
        </section>

        <section class="rounded-[12px] bg-white p-6 ring-1 ring-zinc-100">
          <h2 class="text-base font-semibold text-slate-900">Related articles</h2>
          <p v-if="!store.related.length" class="mt-4 text-sm text-slate-500">No related articles.</p>
          <ul v-else class="mt-3 space-y-1">
            <li v-for="item in store.related" :key="item.uuid">
              <RouterLink
                :to="{ name: 'support.knowledge.show', params: { id: item.uuid } }"
                class="block rounded-[12px] px-3 py-2 text-sm text-slate-700 hover:bg-zinc-50 hover:text-brand-700"
              >
                {{ item.title }}
              </RouterLink>
            </li>
          </ul>
        </section>

        <section class="rounded-[12px] bg-white p-6 ring-1 ring-zinc-100">
          <div class="mb-3 flex items-center justify-between gap-2">
            <h2 class="text-base font-semibold text-slate-900">Version history</h2>
            <button type="button" class="text-xs font-medium text-brand-700 hover:text-brand-600" @click="loadVersions">
              Refresh
            </button>
          </div>
          <p v-if="!store.versions.length" class="text-sm text-slate-500">No versions loaded.</p>
          <ul v-else class="space-y-2">
            <li
              v-for="version in store.versions"
              :key="version.uuid"
              class="rounded-[12px] bg-zinc-50 px-3 py-2.5"
            >
              <div class="flex items-center justify-between gap-2">
                <span class="text-sm font-medium text-slate-900">v{{ version.version }}</span>
                <button
                  type="button"
                  class="text-xs font-medium text-brand-700 hover:text-brand-600 disabled:opacity-60"
                  :disabled="store.saving"
                  @click="restore(version)"
                >
                  Restore
                </button>
              </div>
              <p class="mt-0.5 text-xs text-slate-500">{{ version.reason || 'Update' }}</p>
            </li>
          </ul>
        </section>

        <section class="rounded-[12px] bg-white p-6 ring-1 ring-zinc-100">
          <h2 class="text-base font-semibold text-slate-900">CMS connection</h2>
          <p v-if="article.content" class="mt-2 text-sm text-slate-600">
            Linked to <span class="font-medium text-slate-900">{{ article.content.title }}</span>
          </p>
          <div class="mt-4 space-y-2">
            <input
              v-model="cmsContentId"
              type="text"
              class="h-10 w-full rounded-[12px] border border-zinc-200 px-3.5 text-sm text-slate-800 placeholder:text-slate-400 focus:border-brand-500 focus:outline-none focus:ring-0"
              placeholder="CMS content UUID"
            />
            <button
              type="button"
              class="inline-flex h-10 w-full items-center justify-center rounded-[12px] bg-slate-900 px-4 text-sm font-medium text-white hover:bg-slate-800 disabled:opacity-60"
              :disabled="store.saving || !cmsContentId"
              @click="linkCms"
            >
              Link & sync from CMS
            </button>
          </div>
        </section>
      </aside>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import DOMPurify from 'dompurify';
import { RouterLink, useRoute } from 'vue-router';
import { useToast } from '@/composables/useToast';
import SupportSubnav from '@/modules/support/components/SupportSubnav.vue';
import { useKnowledgeBaseStore } from '@/modules/support/stores/knowledgeBase';

const route = useRoute();
const store = useKnowledgeBaseStore();
const toast = useToast();
const cmsContentId = ref('');

const article = computed(() => store.currentArticle);
const safeBody = computed(() =>
  DOMPurify.sanitize(article.value?.body || '', { USE_PROFILES: { html: true } })
);

watch(
  () => store.successMessage,
  (message) => {
    if (!message) return;
    toast.success(message);
    store.successMessage = null;
  },
);

watch(
  () => store.error,
  (message) => {
    if (!message) return;
    toast.error(message);
    store.error = null;
  },
);

onMounted(() => {
  store.successMessage = null;
  store.error = null;
  loadArticle();
});

watch(
  () => route.params.id,
  (id) => {
    if (!id) return;
    loadArticle();
  },
);

async function loadArticle() {
  try {
    await store.fetchArticle(route.params.id);
    await store.fetchVersions(route.params.id);
  } catch {
    // Toast is shown from store.error.
  }
}

async function loadVersions() {
  await store.fetchVersions(route.params.id).catch(() => {});
}

async function publish() {
  if (!article.value) return;
  try {
    await store.publishArticle(article.value.uuid);
  } catch {
    // Toast is shown from store.error.
  }
}

async function restore(version) {
  if (!article.value) return;
  try {
    await store.restoreVersion(article.value.uuid, version.uuid);
  } catch {
    // Toast is shown from store.error.
  }
}

async function linkCms() {
  if (!article.value || !cmsContentId.value) return;
  try {
    await store.linkCms(article.value.uuid, cmsContentId.value, true);
  } catch {
    // Toast is shown from store.error.
  }
}

function statusLabel(value) {
  if (value === 'published') return 'Published';
  if (value === 'draft') return 'Draft';
  if (value === 'archived') return 'Archived';
  return value || 'Unknown';
}

function statusTone(value) {
  switch (value) {
    case 'published':
      return 'bg-emerald-50 text-emerald-700 ring-emerald-600/20';
    case 'draft':
      return 'bg-amber-50 text-amber-800 ring-amber-600/20';
    case 'archived':
      return 'bg-slate-50 text-slate-600 ring-slate-500/20';
    default:
      return 'bg-slate-50 text-slate-600 ring-slate-500/20';
  }
}
</script>
