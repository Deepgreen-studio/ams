<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <RouterLink
        :to="{ name: 'support.knowledge.center' }"
        class="inline-flex items-center gap-2 rounded-[12px] border border-zinc-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
      >
        <BookOpenIcon class="h-4 w-4" />
        Knowledge center
      </RouterLink>
      <RouterLink
        v-if="can('support.create')"
        :to="{ name: 'support.knowledge.create' }"
        class="inline-flex items-center gap-2 rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
      >
        <PlusIcon class="h-4 w-4" />
        New article
      </RouterLink>
    </Teleport>

    <SupportSubnav />

    <div class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100">
      <div class="border-b border-zinc-100 px-6 py-5 sm:px-8">
        <div class="mb-4">
          <h2 class="text-base font-semibold text-slate-900">Knowledge articles</h2>
          <p class="mt-0.5 text-xs text-slate-500">
            Search and browse by type, status, and category.
          </p>
        </div>
        <form
          class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between"
          @submit.prevent="onSearch"
        >
          <div class="relative min-w-0 flex-1 lg:max-w-sm">
            <MagnifyingGlassIcon
              class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
            />
            <input
              v-model="search"
              type="search"
              placeholder="Search title, summary…"
              class="h-10 w-full rounded-[12px] border border-zinc-200 bg-white py-2 pl-10 pr-3 text-sm text-slate-800 placeholder:text-slate-400 focus:border-brand-500 focus:outline-none focus:ring-0"
            />
          </div>
          <div class="flex flex-wrap items-center gap-2">
            <SelectBox
              v-model="type"
              wrapper-class="min-w-[10.5rem]"
              :options="typeOptions"
              @change="onFilterChange"
            />
            <SelectBox
              v-model="status"
              wrapper-class="min-w-[10.5rem]"
              :options="statusOptions"
              @change="onFilterChange"
            />
            <SelectBox
              v-if="categoryOptions.length > 1"
              v-model="category"
              wrapper-class="min-w-[12rem]"
              :options="categoryOptions"
              @change="onFilterChange"
            />
            <button
              type="submit"
              class="h-10 rounded-[12px] bg-brand-600 px-5 text-sm font-medium text-white hover:bg-brand-700"
            >
              Search
            </button>
          </div>
        </form>
      </div>

      <div v-if="store.loading && !store.articles.length" class="grid gap-3 p-4 sm:grid-cols-2 xl:grid-cols-3 sm:p-6">
        <div v-for="n in 6" :key="n" class="h-36 animate-pulse rounded-[12px] bg-zinc-100" />
      </div>

      <EmptyState
        v-else-if="!store.articles.length"
        title="No articles found"
        description="Try a different search, or create a new knowledge article."
      >
        <template #action>
          <button
            type="button"
            class="rounded-[12px] border border-zinc-200 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
            @click="resetFilters"
          >
            Reset filters
          </button>
          <RouterLink
            v-if="can('support.create')"
            :to="{ name: 'support.knowledge.create' }"
            class="rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
          >
            New article
          </RouterLink>
        </template>
      </EmptyState>

      <div v-else class="grid gap-3 p-3 sm:grid-cols-2 xl:grid-cols-3 sm:p-4">
        <RouterLink
          v-for="article in store.articles"
          :key="article.uuid"
          :to="{ name: 'support.knowledge.show', params: { id: article.uuid } }"
          class="rounded-[12px] px-4 py-4 transition hover:bg-zinc-50"
        >
          <div class="mb-2 flex flex-wrap items-center gap-2">
            <span class="inline-flex items-center rounded-full bg-slate-50 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-wide text-slate-600 ring-1 ring-slate-500/20">
              {{ article.type_label || article.type }}
            </span>
            <span
              class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset"
              :class="statusTone(article.status)"
            >
              {{ statusLabel(article.status) }}
            </span>
          </div>
          <h3 class="text-sm font-semibold text-slate-900">{{ article.title }}</h3>
          <p class="mt-1.5 line-clamp-2 text-sm text-slate-500">
            {{ article.summary || 'No summary' }}
          </p>
          <p class="mt-3 text-xs text-slate-400">
            {{ article.category?.name || 'Uncategorized' }}
            <span v-if="article.helpful_count"> · {{ article.helpful_count }} helpful</span>
          </p>
        </RouterLink>
      </div>

      <div v-if="store.meta?.total" class="border-t border-zinc-100 px-6 py-4 sm:px-8">
        <Pagination
          :meta="store.meta"
          :loading="store.loading"
          @change="onPageChange"
          @per-page="onPerPage"
        />
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import { BookOpenIcon, MagnifyingGlassIcon, PlusIcon } from '@heroicons/vue/24/outline';
import EmptyState from '@/components/ui/EmptyState.vue';
import { usePermissions } from '@/composables/usePermissions';
import { useToast } from '@/composables/useToast';
import Pagination from '@/modules/users/components/Pagination.vue';
import SelectBox from '@/modules/users/components/SelectBox.vue';
import SupportSubnav from '@/modules/support/components/SupportSubnav.vue';
import { useKnowledgeBaseStore } from '@/modules/support/stores/knowledgeBase';

const store = useKnowledgeBaseStore();
const route = useRoute();
const { can } = usePermissions();
const toast = useToast();

const search = ref('');
const type = ref('');
const status = ref('');
const category = ref('');
const perPage = ref(10);

const typeOptions = [
  { value: '', label: 'All types' },
  { value: 'article', label: 'Articles' },
  { value: 'guide', label: 'Guides' },
  { value: 'faq', label: 'FAQs' },
  { value: 'tutorial', label: 'Tutorials' },
  { value: 'video', label: 'Videos' },
  { value: 'release_notes', label: 'Release Notes' },
];

const statusOptions = [
  { value: '', label: 'All statuses' },
  { value: 'published', label: 'Published' },
  { value: 'draft', label: 'Draft' },
  { value: 'archived', label: 'Archived' },
];

const categoryOptions = computed(() => [
  { value: '', label: 'All categories' },
  ...flattenCategories(store.categories),
]);

watch(
  () => store.error,
  (message) => {
    if (!message) return;
    toast.error(message);
    store.error = null;
  },
);

watch(
  () => route.query,
  () => {
    applyQuery();
    loadArticles(1).catch(() => {});
  },
);

onMounted(async () => {
  store.error = null;
  applyQuery();
  store.fetchCategories().catch(() => {});
  await loadArticles(1).catch(() => {});
});

function flattenCategories(items, depth = 0) {
  const result = [];
  (items || []).forEach((item) => {
    result.push({
      value: item.uuid,
      label: depth ? `${'— '.repeat(depth)}${item.name}` : item.name,
    });
    result.push(...flattenCategories(item.children || [], depth + 1));
  });
  return result;
}

function applyQuery() {
  search.value = String(route.query.search || '');
  type.value = String(route.query.type || '');
  status.value = String(route.query.status || '');
  category.value = String(route.query.category || '');
}

function listParams(page = store.meta?.current_page || 1) {
  return {
    search: search.value,
    type: type.value,
    status: status.value,
    category: category.value,
    page,
    per_page: perPage.value,
  };
}

async function loadArticles(page = 1) {
  await store.fetchArticles(listParams(page));
}

function onSearch() {
  loadArticles(1).catch(() => {});
}

function onFilterChange() {
  loadArticles(1).catch(() => {});
}

function onPageChange(page) {
  loadArticles(page).catch(() => {});
}

function onPerPage(value) {
  perPage.value = value;
  loadArticles(1).catch(() => {});
}

function resetFilters() {
  search.value = '';
  type.value = '';
  status.value = '';
  category.value = '';
  loadArticles(1).catch(() => {});
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
