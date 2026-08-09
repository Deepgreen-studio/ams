<template>
  <div>
    <!-- <PageHeader
      title="Content Dashboard"
      description="Headless CMS overview for pages, blogs, FAQs, and custom content types."
    >
      <template #actions>
        <RouterLink
          :to="{ name: 'content.categories' }"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >
          Categories
        </RouterLink>
        <RouterLink
          :to="{ name: 'content.tags' }"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >
          Tags
        </RouterLink>
        <RouterLink
          :to="{ name: 'content.index' }"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >
          Browse content
        </RouterLink>
        <RouterLink
          :to="{ name: 'content.create' }"
          class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
        >
          Create content
        </RouterLink>
      </template>
    </PageHeader> -->
    <Teleport defer to="#page-header-actions">
      <RouterLink
          :to="{ name: 'content.categories' }"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >
          Categories
        </RouterLink>
        <RouterLink
          :to="{ name: 'content.tags' }"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >
          Tags
        </RouterLink>
        <RouterLink
          :to="{ name: 'content.index' }"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >
          Browse content
        </RouterLink>
        <RouterLink
          :to="{ name: 'content.create' }"
          class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
        >
          Create content
        </RouterLink>
    </Teleport>

    <ContentSubnav />

    <div
      v-if="contentStore.error"
      class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ contentStore.error }}
    </div>

    <div class="mb-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
      <div
        v-for="card in statCards"
        :key="card.label"
        class="rounded-xl border border-slate-200 bg-white px-4 py-3"
      >
        <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ card.label }}</p>
        <p class="mt-1 text-2xl font-semibold text-slate-900">{{ card.value }}</p>
      </div>
    </div>

    <div class="grid gap-4 lg:grid-cols-3">
      <div class="rounded-xl border border-slate-200 bg-white p-5 lg:col-span-2">
        <div class="mb-4 flex items-center justify-between">
          <h2 class="text-sm font-semibold text-slate-900">Recent content</h2>
          <RouterLink
            :to="{ name: 'content.index' }"
            class="text-xs font-medium text-brand-700 hover:underline"
            >View all</RouterLink
          >
        </div>
        <div v-if="contentStore.loading" class="space-y-3">
          <div v-for="n in 4" :key="n" class="h-12 animate-pulse rounded bg-slate-100" />
        </div>
        <ul v-else-if="contentStore.contents.length" class="divide-y divide-slate-100">
          <li
            v-for="item in contentStore.contents"
            :key="item.uuid"
            class="flex items-center justify-between py-3"
          >
            <div>
              <RouterLink
                :to="{ name: 'content.show', params: { id: item.uuid } }"
                class="font-medium text-slate-900 hover:text-brand-700"
              >
                {{ item.title }}
              </RouterLink>
              <p class="text-xs text-slate-500">{{ item.type?.name }} · {{ item.status?.name }}</p>
            </div>
            <StatusBadge :status="item.status?.slug" :label="item.status?.name" />
          </li>
        </ul>
        <p v-else class="text-sm text-slate-500">No content entries yet.</p>
      </div>

      <div class="rounded-xl border border-slate-200 bg-white p-5">
        <h2 class="mb-3 text-sm font-semibold text-slate-900">Content types</h2>
        <ul class="space-y-2">
          <li
            v-for="type in contentStore.types"
            :key="type.uuid"
            class="flex items-center justify-between text-sm"
          >
            <span class="text-slate-700">{{ type.name }}</span>
            <span class="text-xs text-slate-400">{{ type.slug }}</span>
          </li>
        </ul>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue';
import { RouterLink } from 'vue-router';
// import PageHeader from '@/components/ui/PageHeader.vue';
import StatusBadge from '@/modules/content/components/StatusBadge.vue';
import ContentSubnav from '@/modules/content/components/ContentSubnav.vue';
import { useContentStore } from '@/modules/content/stores/content';

const contentStore = useContentStore();

const statCards = computed(() => [
  { label: 'Total', value: contentStore.statistics?.total ?? 0 },
  { label: 'Published', value: contentStore.statistics?.published ?? 0 },
  { label: 'Draft', value: contentStore.statistics?.draft ?? 0 },
  { label: 'Featured', value: contentStore.statistics?.featured ?? 0 },
]);

onMounted(async () => {
  await Promise.all([
    contentStore.fetchDashboard(),
    contentStore.fetchCatalog(),
    contentStore.fetchContents({ per_page: 5, sort_by: 'updated_at', sort_dir: 'desc', page: 1 }),
  ]);
});
</script>
