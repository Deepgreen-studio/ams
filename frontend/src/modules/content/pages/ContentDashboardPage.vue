<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <RouterLink
        v-if="can('content.create')"
        :to="{ name: 'content.create' }"
        class="rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
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

    <div class="mb-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
      <div
        v-for="card in statCards"
        :key="card.label"
        class="flex items-center justify-between gap-4 rounded-[12px] bg-white px-8 py-7 ring-1 ring-zinc-100 transition hover:ring-brand-200"
      >
        <div class="min-w-0">
          <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ card.label }}</p>
          <p class="mt-1 text-3xl font-bold tracking-tight text-slate-900">{{ card.value }}</p>
        </div>
        <div
          class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-[12px] p-3"
          :class="card.iconBg"
        >
          <component :is="card.icon" class="h-5 w-5" :class="card.iconColor" />
        </div>
      </div>
    </div>

    <div class="grid gap-4 lg:grid-cols-3">
      <div class="rounded-[12px] bg-white p-6 ring-1 ring-zinc-100 lg:col-span-2 sm:p-8">
        <div class="mb-5 flex items-center justify-between gap-3">
          <h2 class="text-base font-semibold text-slate-900">Recent content</h2>
          <RouterLink
            :to="{ name: 'content.index' }"
            class="text-sm font-medium text-brand-600 hover:text-brand-700 hover:underline"
          >
            View all
          </RouterLink>
        </div>

        <div v-if="contentStore.loading" class="space-y-3">
          <div v-for="n in 4" :key="n" class="h-14 animate-pulse rounded-[12px] bg-zinc-100" />
        </div>

        <ul v-else-if="contentStore.contents.length" class="divide-y divide-zinc-100">
          <li
            v-for="item in contentStore.contents"
            :key="item.uuid"
            class="flex items-center justify-between gap-4 py-4 first:pt-0 last:pb-0"
          >
            <div class="min-w-0">
              <RouterLink
                :to="{ name: 'content.show', params: { id: item.uuid } }"
                class="font-medium text-slate-900 hover:text-brand-700"
              >
                {{ item.title }}
              </RouterLink>
              <p class="mt-0.5 text-xs text-slate-500">
                {{ item.type?.name }} · {{ item.status?.name }}
              </p>
            </div>
            <StatusBadge :status="item.status?.slug" :label="item.status?.name" />
          </li>
        </ul>

        <p v-else class="text-sm text-slate-500">No content entries yet.</p>
      </div>

      <div class="rounded-[12px] bg-white p-6 ring-1 ring-zinc-100 sm:p-8">
        <h2 class="mb-4 text-base font-semibold text-slate-900">Content types</h2>
        <ul class="divide-y divide-zinc-100">
          <li
            v-for="type in contentStore.types"
            :key="type.uuid"
            class="flex items-center justify-between gap-3 py-3 first:pt-0 last:pb-0 text-sm"
          >
            <span class="font-medium text-slate-800">{{ type.name }}</span>
            <span class="text-xs text-slate-400">{{ type.slug }}</span>
          </li>
        </ul>
        <p v-if="!contentStore.types.length" class="text-sm text-slate-500">No content types available.</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue';
import { RouterLink } from 'vue-router';
import {
  DocumentTextIcon,
  CheckCircleIcon,
  PencilSquareIcon,
  StarIcon,
} from '@heroicons/vue/24/outline';
import { usePermissions } from '@/composables/usePermissions';
import StatusBadge from '@/modules/content/components/StatusBadge.vue';
import ContentSubnav from '@/modules/content/components/ContentSubnav.vue';
import { useContentStore } from '@/modules/content/stores/content';

const contentStore = useContentStore();
const { can } = usePermissions();

const statCards = computed(() => [
  {
    label: 'Total',
    value: contentStore.statistics?.total ?? 0,
    icon: DocumentTextIcon,
    iconBg: 'bg-brand-50',
    iconColor: 'text-brand-500',
  },
  {
    label: 'Published',
    value: contentStore.statistics?.published ?? 0,
    icon: CheckCircleIcon,
    iconBg: 'bg-emerald-50',
    iconColor: 'text-emerald-600',
  },
  {
    label: 'Draft',
    value: contentStore.statistics?.draft ?? 0,
    icon: PencilSquareIcon,
    iconBg: 'bg-slate-100',
    iconColor: 'text-slate-500',
  },
  {
    label: 'Featured',
    value: contentStore.statistics?.featured ?? 0,
    icon: StarIcon,
    iconBg: 'bg-amber-50',
    iconColor: 'text-amber-600',
  },
]);

onMounted(async () => {
  await Promise.all([
    contentStore.fetchDashboard(),
    contentStore.fetchCatalog(),
    contentStore.fetchContents({ per_page: 5, sort_by: 'updated_at', sort_dir: 'desc', page: 1 }),
  ]);
});
</script>
