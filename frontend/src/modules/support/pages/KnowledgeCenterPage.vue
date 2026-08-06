<template>
  <div>
    <PageHeader title="Knowledge Center" description="Articles, guides, FAQs, tutorials, videos, and release notes">
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

    <div
      v-if="store.error"
      class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ store.error }}
    </div>

    <div v-if="store.loading && !dashboard" class="h-40 animate-pulse rounded-xl bg-slate-100" />

    <template v-else-if="dashboard">
      <form class="mb-6" @submit.prevent="goSearch">
        <div class="flex flex-col gap-2 sm:flex-row">
          <input
            v-model="search"
            type="search"
            class="input flex-1"
            placeholder="Search the knowledge base…"
          />
          <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white">
            Search
          </button>
        </div>
      </form>

      <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-xl border border-slate-200 bg-white p-4">
          <p class="text-xs uppercase tracking-wide text-slate-500">Published</p>
          <p class="mt-1 text-2xl font-semibold">{{ dashboard.statistics?.published ?? 0 }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4">
          <p class="text-xs uppercase tracking-wide text-slate-500">Drafts</p>
          <p class="mt-1 text-2xl font-semibold">{{ dashboard.statistics?.draft ?? 0 }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4">
          <p class="text-xs uppercase tracking-wide text-slate-500">CMS linked</p>
          <p class="mt-1 text-2xl font-semibold">{{ dashboard.statistics?.linked_to_cms ?? 0 }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4">
          <p class="text-xs uppercase tracking-wide text-slate-500">Featured</p>
          <p class="mt-1 text-2xl font-semibold">{{ dashboard.statistics?.featured ?? 0 }}</p>
        </div>
      </div>

      <div class="mb-6 flex flex-wrap gap-2">
        <RouterLink
          v-for="type in dashboard.types || []"
          :key="type.value"
          :to="{ name: 'support.knowledge.index', query: { type: type.value } }"
          class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 hover:bg-slate-50"
        >
          {{ type.label }}
          <span class="ml-1 text-slate-400">({{ type.count }})</span>
        </RouterLink>
      </div>

      <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
          <section class="rounded-xl border border-slate-200 bg-white p-5">
            <h3 class="mb-4 text-sm font-semibold text-slate-900">Featured</h3>
            <div v-if="!dashboard.featured?.length" class="text-sm text-slate-500">No featured articles.</div>
            <div v-else class="grid gap-3 sm:grid-cols-2">
              <RouterLink
                v-for="article in dashboard.featured"
                :key="article.uuid"
                :to="{ name: 'support.knowledge.show', params: { id: article.uuid } }"
                class="rounded-lg border border-slate-100 p-3 hover:border-brand-200 hover:bg-brand-50/40"
              >
                <p class="text-xs uppercase tracking-wide text-slate-400">{{ article.type_label }}</p>
                <p class="mt-1 font-medium text-slate-900">{{ article.title }}</p>
                <p class="mt-1 line-clamp-2 text-xs text-slate-500">{{ article.summary }}</p>
              </RouterLink>
            </div>
          </section>

          <section class="rounded-xl border border-slate-200 bg-white p-5">
            <div class="mb-4 flex items-center justify-between">
              <h3 class="text-sm font-semibold text-slate-900">Latest</h3>
              <RouterLink :to="{ name: 'support.knowledge.index' }" class="text-xs font-medium text-brand-700">
                Browse all
              </RouterLink>
            </div>
            <div class="divide-y divide-slate-100">
              <RouterLink
                v-for="article in dashboard.latest"
                :key="article.uuid"
                :to="{ name: 'support.knowledge.show', params: { id: article.uuid } }"
                class="flex items-start justify-between gap-3 py-3 hover:bg-slate-50"
              >
                <div>
                  <p class="font-medium text-slate-900">{{ article.title }}</p>
                  <p class="text-xs text-slate-500">{{ article.type_label }} · {{ article.category?.name || 'Uncategorized' }}</p>
                </div>
                <span class="text-xs text-slate-400">{{ article.view_count }} views</span>
              </RouterLink>
            </div>
          </section>
        </div>

        <aside class="space-y-4">
          <section class="rounded-xl border border-slate-200 bg-white p-5">
            <h3 class="mb-3 text-sm font-semibold text-slate-900">Categories</h3>
            <ul class="space-y-2 text-sm">
              <li v-for="category in dashboard.categories || []" :key="category.uuid">
                <RouterLink
                  :to="{ name: 'support.knowledge.index', query: { category: category.uuid } }"
                  class="flex items-center justify-between text-slate-700 hover:text-brand-700"
                >
                  <span>{{ category.name }}</span>
                  <span class="text-xs text-slate-400">{{ category.articles_count }}</span>
                </RouterLink>
              </li>
            </ul>
          </section>

          <section class="rounded-xl border border-slate-200 bg-white p-5">
            <h3 class="mb-3 text-sm font-semibold text-slate-900">Popular</h3>
            <ul class="space-y-2 text-sm">
              <li v-for="article in dashboard.popular || []" :key="article.uuid">
                <RouterLink
                  :to="{ name: 'support.knowledge.show', params: { id: article.uuid } }"
                  class="text-slate-700 hover:text-brand-700"
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
import { computed, onMounted, ref } from 'vue';
import { RouterLink, useRouter } from 'vue-router';
import PageHeader from '@/components/ui/PageHeader.vue';
import SupportSubnav from '@/modules/support/components/SupportSubnav.vue';
import { useKnowledgeBaseStore } from '@/modules/support/stores/knowledgeBase';

const store = useKnowledgeBaseStore();
const router = useRouter();
const search = ref('');
const dashboard = computed(() => store.dashboard);

onMounted(() => store.fetchDashboard());

function goSearch() {
  router.push({
    name: 'support.knowledge.index',
    query: search.value ? { search: search.value } : {},
  });
}
</script>

<style scoped>
.input {
  width: 100%;
  border-radius: 0.5rem;
  border: 1px solid #cbd5e1;
  padding: 0.625rem 0.75rem;
  font-size: 0.875rem;
}
</style>
