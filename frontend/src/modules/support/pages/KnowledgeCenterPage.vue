<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <RouterLink
        :to="{ name: 'support.knowledge.index' }"
        class="inline-flex items-center gap-2 rounded-[12px] border border-zinc-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
      >
        <BookOpenIcon class="h-4 w-4" />
        Browse all
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

    <div v-if="store.loading && !dashboard" class="mb-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
      <div v-for="n in 4" :key="n" class="h-28 animate-pulse rounded-[12px] bg-zinc-100" />
    </div>

    <div
      v-else-if="store.error && !dashboard"
      class="rounded-[12px] bg-white px-6 py-16 text-center ring-1 ring-zinc-100"
    >
      <p class="text-sm font-medium text-slate-900">Unable to load knowledge center</p>
      <p class="mt-1 text-xs text-slate-500">Refresh to try loading articles and categories again.</p>
      <button
        type="button"
        class="mt-6 rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
        @click="reload"
      >
        Retry
      </button>
    </div>

    <template v-else-if="dashboard">
      <form
        class="mb-4 flex flex-col gap-3 rounded-[12px] bg-white p-4 ring-1 ring-zinc-100 sm:flex-row sm:items-center sm:px-6"
        @submit.prevent="goSearch"
      >
        <div class="relative min-w-0 flex-1">
          <MagnifyingGlassIcon
            class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
          />
          <input
            v-model="search"
            type="search"
            placeholder="Search the knowledge base…"
            class="h-10 w-full rounded-[12px] border border-zinc-200 bg-white py-2 pl-10 pr-3 text-sm text-slate-800 placeholder:text-slate-400 focus:border-brand-500 focus:outline-none focus:ring-0"
          />
        </div>
        <button
          type="submit"
          class="inline-flex h-10 items-center justify-center rounded-[12px] bg-brand-600 px-5 text-sm font-medium text-white hover:bg-brand-700"
        >
          Search
        </button>
      </form>

      <div class="mb-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div
          v-for="card in summaryCards"
          :key="card.label"
          class="flex items-center justify-between gap-4 rounded-[12px] bg-white px-6 py-5 ring-1 ring-zinc-100 transition hover:ring-brand-200"
        >
          <div class="min-w-0">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ card.label }}</p>
            <p class="mt-1 truncate text-2xl font-bold tracking-tight text-slate-900">{{ card.value }}</p>
            <p v-if="card.hint" class="mt-1 text-xs text-slate-400">{{ card.hint }}</p>
          </div>
          <div
            class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-[12px]"
            :class="card.iconBg"
          >
            <component :is="card.icon" class="h-5 w-5" :class="card.iconColor" />
          </div>
        </div>
      </div>

      <div class="mb-4 overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100">
        <nav class="flex gap-x-0.5 overflow-x-auto px-3 sm:px-4" aria-label="Article types">
          <RouterLink
            v-for="type in dashboard.types || []"
            :key="type.value"
            :to="{ name: 'support.knowledge.index', query: { type: type.value } }"
            class="shrink-0 border-b-2 border-transparent px-3.5 py-2.5 text-sm font-medium text-slate-500 transition-colors hover:border-zinc-300 hover:text-slate-800"
          >
            {{ type.label }}
            <span class="ml-1 text-slate-400">({{ type.count }})</span>
          </RouterLink>
        </nav>
      </div>

      <div class="grid gap-4 lg:grid-cols-3">
        <div class="space-y-4 lg:col-span-2">
          <section class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100">
            <div class="flex items-center justify-between border-b border-zinc-100 px-6 py-5">
              <div>
                <h2 class="text-base font-semibold text-slate-900">Featured</h2>
                <p class="mt-0.5 text-xs text-slate-500">Highlighted articles for agents and customers.</p>
              </div>
            </div>

            <div v-if="!dashboard.featured?.length" class="px-6 py-16 text-center">
              <p class="text-sm font-medium text-slate-900">No featured articles</p>
              <p class="mt-1 text-xs text-slate-500">Mark an article as featured to pin it here.</p>
              <RouterLink
                v-if="can('support.create')"
                :to="{ name: 'support.knowledge.create' }"
                class="mt-6 inline-flex rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
              >
                New article
              </RouterLink>
            </div>

            <div v-else class="grid gap-3 p-3 sm:grid-cols-2">
              <RouterLink
                v-for="article in dashboard.featured"
                :key="article.uuid"
                :to="{ name: 'support.knowledge.show', params: { id: article.uuid } }"
                class="rounded-[12px] px-4 py-4 transition hover:bg-zinc-50"
              >
                <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">
                  {{ article.type_label }}
                </p>
                <p class="mt-1 text-sm font-semibold text-slate-900">{{ article.title }}</p>
                <p class="mt-1 line-clamp-2 text-xs text-slate-500">
                  {{ article.summary || 'No summary' }}
                </p>
              </RouterLink>
            </div>
          </section>

          <section class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100">
            <div class="flex items-center justify-between border-b border-zinc-100 px-6 py-5">
              <div>
                <h2 class="text-base font-semibold text-slate-900">Latest</h2>
                <p class="mt-0.5 text-xs text-slate-500">Recently published knowledge.</p>
              </div>
              <RouterLink
                :to="{ name: 'support.knowledge.index' }"
                class="text-sm font-medium text-brand-700 hover:text-brand-600"
              >
                Browse all
              </RouterLink>
            </div>

            <div v-if="!dashboard.latest?.length" class="px-6 py-16 text-center">
              <p class="text-sm font-medium text-slate-900">No published articles yet</p>
              <p class="mt-1 text-xs text-slate-500">New articles will appear here after they are published.</p>
            </div>

            <ul v-else class="divide-y divide-zinc-50 px-3 py-2">
              <li v-for="article in dashboard.latest" :key="article.uuid">
                <RouterLink
                  :to="{ name: 'support.knowledge.show', params: { id: article.uuid } }"
                  class="flex items-start justify-between gap-3 rounded-[12px] px-3 py-3 transition hover:bg-zinc-50"
                >
                  <div class="min-w-0">
                    <p class="truncate text-sm font-medium text-slate-900">{{ article.title }}</p>
                    <p class="mt-0.5 text-xs text-slate-500">
                      {{ article.type_label }} · {{ article.category?.name || 'Uncategorized' }}
                    </p>
                  </div>
                  <span class="shrink-0 text-xs text-slate-400">{{ article.view_count ?? 0 }} views</span>
                </RouterLink>
              </li>
            </ul>
          </section>
        </div>

        <aside class="space-y-4">
          <section class="rounded-[12px] bg-white p-6 ring-1 ring-zinc-100">
            <h2 class="text-base font-semibold text-slate-900">Categories</h2>
            <p class="mt-0.5 text-xs text-slate-500">Browse by topic.</p>

            <p v-if="!dashboard.categories?.length" class="mt-6 text-sm text-slate-500">
              No categories yet.
            </p>
            <ul v-else class="mt-4 space-y-1">
              <template v-for="category in dashboard.categories" :key="category.uuid">
                <li>
                  <RouterLink
                    :to="{ name: 'support.knowledge.index', query: { category: category.uuid } }"
                    class="flex items-center justify-between rounded-[12px] px-3 py-2 text-sm text-slate-700 transition hover:bg-zinc-50 hover:text-brand-700"
                  >
                    <span class="truncate">{{ category.name }}</span>
                    <span class="text-xs text-slate-400">{{ category.articles_count ?? 0 }}</span>
                  </RouterLink>
                </li>
                <li
                  v-for="child in category.children || []"
                  :key="child.uuid"
                >
                  <RouterLink
                    :to="{ name: 'support.knowledge.index', query: { category: child.uuid } }"
                    class="flex items-center justify-between rounded-[12px] py-2 pl-6 pr-3 text-sm text-slate-600 transition hover:bg-zinc-50 hover:text-brand-700"
                  >
                    <span class="truncate">{{ child.name }}</span>
                    <span class="text-xs text-slate-400">{{ child.articles_count ?? 0 }}</span>
                  </RouterLink>
                </li>
              </template>
            </ul>
          </section>

          <section class="rounded-[12px] bg-white p-6 ring-1 ring-zinc-100">
            <h2 class="text-base font-semibold text-slate-900">Popular</h2>
            <p class="mt-0.5 text-xs text-slate-500">Most viewed published articles.</p>

            <p v-if="!dashboard.popular?.length" class="mt-6 text-sm text-slate-500">
              No popular articles yet.
            </p>
            <ul v-else class="mt-4 space-y-1">
              <li v-for="article in dashboard.popular" :key="article.uuid">
                <RouterLink
                  :to="{ name: 'support.knowledge.show', params: { id: article.uuid } }"
                  class="block rounded-[12px] px-3 py-2 text-sm text-slate-700 transition hover:bg-zinc-50 hover:text-brand-700"
                >
                  {{ article.title }}
                </RouterLink>
              </li>
            </ul>
          </section>
        </aside>
      </div>
    </template>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { RouterLink, useRouter } from 'vue-router';
import {
  BookOpenIcon,
  CheckCircleIcon,
  DocumentTextIcon,
  LinkIcon,
  MagnifyingGlassIcon,
  PlusIcon,
  StarIcon,
} from '@heroicons/vue/24/outline';
import { usePermissions } from '@/composables/usePermissions';
import { useToast } from '@/composables/useToast';
import SupportSubnav from '@/modules/support/components/SupportSubnav.vue';
import { useKnowledgeBaseStore } from '@/modules/support/stores/knowledgeBase';

const store = useKnowledgeBaseStore();
const router = useRouter();
const { can } = usePermissions();
const toast = useToast();
const search = ref('');
const dashboard = computed(() => store.dashboard);

const summaryCards = computed(() => {
  const published = dashboard.value?.statistics?.published ?? 0;
  const drafts = dashboard.value?.statistics?.draft ?? 0;
  const linked = dashboard.value?.statistics?.linked_to_cms ?? 0;
  const featured = dashboard.value?.statistics?.featured ?? 0;

  return [
    {
      label: 'Published',
      value: published,
      hint: 'Live knowledge articles',
      icon: CheckCircleIcon,
      iconBg: published ? 'bg-emerald-50' : 'bg-zinc-100',
      iconColor: published ? 'text-emerald-500' : 'text-slate-500',
    },
    {
      label: 'Drafts',
      value: drafts,
      hint: 'Waiting to be published',
      icon: DocumentTextIcon,
      iconBg: drafts ? 'bg-amber-50' : 'bg-zinc-100',
      iconColor: drafts ? 'text-amber-500' : 'text-slate-500',
    },
    {
      label: 'CMS linked',
      value: linked,
      hint: 'Synced from content',
      icon: LinkIcon,
      iconBg: linked ? 'bg-sky-50' : 'bg-zinc-100',
      iconColor: linked ? 'text-sky-500' : 'text-slate-500',
    },
    {
      label: 'Featured',
      value: featured,
      hint: 'Pinned on this page',
      icon: StarIcon,
      iconBg: featured ? 'bg-brand-50' : 'bg-zinc-100',
      iconColor: featured ? 'text-brand-500' : 'text-slate-500',
    },
  ];
});

watch(
  () => store.error,
  (message) => {
    if (!message || !dashboard.value) return;
    toast.error(message);
    store.error = null;
  },
);

onMounted(() => {
  store.error = null;
  store.fetchDashboard().catch(() => {});
});

function goSearch() {
  router.push({
    name: 'support.knowledge.index',
    query: search.value ? { search: search.value } : {},
  });
}

function reload() {
  store.fetchDashboard().catch(() => {});
}
</script>
