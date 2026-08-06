<template>
  <div>
    <PageHeader title="Knowledge Articles" description="Search and browse by type, category, and tags">
      <template #actions>
        <RouterLink
          :to="{ name: 'support.knowledge.create' }"
          class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
        >
          New article
        </RouterLink>
      </template>
    </PageHeader>

    <SupportSubnav />

    <div class="mb-4 grid gap-3 md:grid-cols-4">
      <input v-model="local.search" type="search" class="input md:col-span-2" placeholder="Search…" @keyup.enter="reload" />
      <select v-model="local.type" class="input" @change="reload">
        <option value="">All types</option>
        <option value="article">Articles</option>
        <option value="guide">Guides</option>
        <option value="faq">FAQs</option>
        <option value="tutorial">Tutorials</option>
        <option value="video">Videos</option>
        <option value="release_notes">Release Notes</option>
      </select>
      <select v-model="local.status" class="input" @change="reload">
        <option value="">All statuses</option>
        <option value="published">Published</option>
        <option value="draft">Draft</option>
        <option value="archived">Archived</option>
      </select>
    </div>

    <div v-if="store.loading" class="h-40 animate-pulse rounded-xl bg-slate-100" />

    <div v-else class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
      <RouterLink
        v-for="article in store.articles"
        :key="article.uuid"
        :to="{ name: 'support.knowledge.show', params: { id: article.uuid } }"
        class="rounded-xl border border-slate-200 bg-white p-4 hover:border-brand-200 hover:shadow-sm"
      >
        <div class="mb-2 flex items-center justify-between gap-2">
          <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold uppercase text-slate-600">
            {{ article.type_label || article.type }}
          </span>
          <span class="text-xs text-slate-400">{{ article.status }}</span>
        </div>
        <h3 class="font-semibold text-slate-900">{{ article.title }}</h3>
        <p class="mt-2 line-clamp-3 text-sm text-slate-600">{{ article.summary || 'No summary' }}</p>
        <p class="mt-3 text-xs text-slate-400">
          {{ article.category?.name || 'Uncategorized' }} · {{ article.helpful_count }} helpful
        </p>
      </RouterLink>
      <div v-if="store.articles.length === 0" class="col-span-full py-12 text-center text-sm text-slate-500">
        No articles found.
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, reactive, watch } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import PageHeader from '@/components/ui/PageHeader.vue';
import SupportSubnav from '@/modules/support/components/SupportSubnav.vue';
import { useKnowledgeBaseStore } from '@/modules/support/stores/knowledgeBase';

const store = useKnowledgeBaseStore();
const route = useRoute();
const local = reactive({
  search: '',
  type: '',
  status: '',
  category: '',
});

onMounted(() => {
  local.search = route.query.search || '';
  local.type = route.query.type || '';
  local.status = route.query.status || '';
  local.category = route.query.category || '';
  reload();
});

watch(
  () => route.query,
  () => {
    local.search = route.query.search || '';
    local.type = route.query.type || '';
    local.category = route.query.category || '';
    reload();
  }
);

function reload() {
  store.fetchArticles({
    search: local.search,
    type: local.type,
    status: local.status,
    category: local.category,
    page: 1,
  });
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
