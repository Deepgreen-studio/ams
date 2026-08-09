<template>
  <div>
    <!-- <PageHeader :title="article?.title || 'Article'" :description="article?.type_label || 'Knowledge article'">
      <template #actions>
        <template v-if="article">
          <button
            v-if="article.status !== 'published'"
            type="button"
            class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
            :disabled="store.saving"
            @click="store.publishArticle(article.uuid)"
          >
            Publish
          </button>
          <RouterLink
            :to="{ name: 'support.knowledge.edit', params: { id: article.uuid } }"
            class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
          >
            Edit
          </RouterLink>
        </template>
      </template>
    </PageHeader> -->
    <Teleport defer to="#page-header-actions">
      <template v-if="article">
          <button
            v-if="article.status !== 'published'"
            type="button"
            class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
            :disabled="store.saving"
            @click="store.publishArticle(article.uuid)"
          >
            Publish
          </button>
          <RouterLink
            :to="{ name: 'support.knowledge.edit', params: { id: article.uuid } }"
            class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
          >
            Edit
          </RouterLink>
    </Teleport>

    <SupportSubnav />

    <div
      v-if="store.successMessage"
      class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700"
    >
      {{ store.successMessage }}
    </div>
    <div
      v-if="store.error"
      class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ store.error }}
    </div>

    <div v-if="store.loading && !article" class="h-48 animate-pulse rounded-xl bg-slate-100" />

    <div v-else-if="article" class="grid gap-6 lg:grid-cols-3">
      <article class="rounded-xl border border-slate-200 bg-white p-6 lg:col-span-2">
        <div class="mb-4 flex flex-wrap gap-2 text-xs">
          <span class="rounded-full bg-slate-100 px-2 py-1 font-semibold uppercase text-slate-600">
            {{ article.type_label }}
          </span>
          <span class="rounded-full bg-slate-100 px-2 py-1 text-slate-600">{{ article.status }}</span>
          <span v-if="article.content" class="rounded-full bg-sky-50 px-2 py-1 text-sky-700">
            CMS linked · v{{ article.content.version }}
          </span>
        </div>

        <p v-if="article.summary" class="mb-4 text-sm text-slate-600">{{ article.summary }}</p>

        <div
          v-if="article.type === 'video' && article.video_url"
          class="mb-6 overflow-hidden rounded-lg border border-slate-200"
        >
          <a :href="article.video_url" target="_blank" rel="noopener" class="block p-4 text-sm text-brand-700">
            Open video: {{ article.video_url }}
          </a>
        </div>

        <div class="prose prose-sm max-w-none text-slate-800" v-html="safeBody" />

        <div class="mt-8 border-t border-slate-200 pt-5">
          <p class="mb-3 text-sm font-medium text-slate-900">Was this helpful?</p>
          <div class="flex flex-wrap gap-2">
            <button
              type="button"
              class="rounded-lg px-3 py-1.5 text-sm font-medium"
              :class="article.user_feedback === true ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-700'"
              @click="store.submitFeedback(article.uuid, true)"
            >
              Helpful ({{ article.helpful_count }})
            </button>
            <button
              type="button"
              class="rounded-lg px-3 py-1.5 text-sm font-medium"
              :class="article.user_feedback === false ? 'bg-rose-100 text-rose-800' : 'bg-slate-100 text-slate-700'"
              @click="store.submitFeedback(article.uuid, false)"
            >
              Not helpful ({{ article.not_helpful_count }})
            </button>
          </div>
        </div>
      </article>

      <aside class="space-y-4">
        <div class="rounded-xl border border-slate-200 bg-white p-5">
          <h3 class="text-sm font-semibold text-slate-900">Details</h3>
          <dl class="mt-3 space-y-2 text-sm">
            <div>
              <dt class="text-xs uppercase text-slate-500">Category</dt>
              <dd>{{ article.category?.name || '—' }}</dd>
            </div>
            <div>
              <dt class="text-xs uppercase text-slate-500">Tags</dt>
              <dd>{{ article.tags?.map((t) => t.name).join(', ') || '—' }}</dd>
            </div>
            <div>
              <dt class="text-xs uppercase text-slate-500">Views</dt>
              <dd>{{ article.view_count }}</dd>
            </div>
            <div>
              <dt class="text-xs uppercase text-slate-500">Version</dt>
              <dd>{{ article.version }}</dd>
            </div>
          </dl>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5">
          <h3 class="mb-3 text-sm font-semibold text-slate-900">Related articles</h3>
          <ul class="space-y-2 text-sm">
            <li v-for="item in store.related" :key="item.uuid">
              <RouterLink
                :to="{ name: 'support.knowledge.show', params: { id: item.uuid } }"
                class="text-brand-700 hover:underline"
              >
                {{ item.title }}
              </RouterLink>
            </li>
            <li v-if="!store.related.length" class="text-slate-500">No related articles.</li>
          </ul>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5">
          <div class="mb-3 flex items-center justify-between">
            <h3 class="text-sm font-semibold text-slate-900">Version history</h3>
            <button type="button" class="text-xs text-brand-700" @click="loadVersions">Refresh</button>
          </div>
          <ul class="max-h-64 space-y-2 overflow-y-auto text-sm">
            <li v-for="version in store.versions" :key="version.uuid" class="rounded-lg bg-slate-50 p-2">
              <div class="flex items-center justify-between gap-2">
                <span class="font-medium">v{{ version.version }}</span>
                <button
                  type="button"
                  class="text-xs text-brand-700 hover:underline"
                  :disabled="store.saving"
                  @click="store.restoreVersion(article.uuid, version.uuid)"
                >
                  Restore
                </button>
              </div>
              <p class="text-xs text-slate-500">{{ version.reason || 'Update' }}</p>
            </li>
            <li v-if="!store.versions.length" class="text-slate-500">No versions loaded.</li>
          </ul>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5">
          <h3 class="mb-3 text-sm font-semibold text-slate-900">CMS connection</h3>
          <p v-if="article.content" class="mb-3 text-sm text-slate-600">
            Linked to <span class="font-medium">{{ article.content.title }}</span>
          </p>
          <div class="flex flex-col gap-2">
            <input v-model="cmsContentId" type="text" class="input" placeholder="CMS content UUID" />
            <button
              type="button"
              class="rounded-lg bg-slate-900 px-3 py-2 text-xs font-medium text-white"
              :disabled="store.saving || !cmsContentId"
              @click="store.linkCms(article.uuid, cmsContentId, true)"
            >
              Link & sync from CMS
            </button>
          </div>
        </div>
      </aside>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import DOMPurify from 'dompurify';
import { RouterLink, useRoute } from 'vue-router';
// import PageHeader from '@/components/ui/PageHeader.vue';
import SupportSubnav from '@/modules/support/components/SupportSubnav.vue';
import { useKnowledgeBaseStore } from '@/modules/support/stores/knowledgeBase';

const route = useRoute();
const store = useKnowledgeBaseStore();
const cmsContentId = ref('');

const article = computed(() => store.currentArticle);
const safeBody = computed(() =>
  DOMPurify.sanitize(article.value?.body || '', { USE_PROFILES: { html: true } })
);

onMounted(async () => {
  await store.fetchArticle(route.params.id);
  await store.fetchVersions(route.params.id);
});

watch(
  () => route.params.id,
  async (id) => {
    if (!id) return;
    await store.fetchArticle(id);
    await store.fetchVersions(id);
  }
);

async function loadVersions() {
  await store.fetchVersions(route.params.id);
}
</script>

<style scoped>
.input {
  width: 100%;
  border-radius: 0.5rem;
  border: 1px solid #cbd5e1;
  padding: 0.5rem 0.75rem;
  font-size: 0.875rem;
}
</style>
