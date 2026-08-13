<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <RouterLink
        :to="{ name: 'analytics.dashboard' }"
        class="inline-flex items-center gap-2 rounded-[12px] border border-zinc-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
      >
        <Squares2X2Icon class="h-4 w-4" />
        Overview
      </RouterLink>
      <RouterLink
        :to="{ name: 'analytics.saved-views' }"
        class="inline-flex items-center gap-2 rounded-[12px] border border-zinc-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
      >
        <BookmarkIcon class="h-4 w-4" />
        Saved views
      </RouterLink>
    </Teleport>

    <AnalyticsSubnav />

    <EnterpriseFilterBar
      v-model="store.filters"
      :categories="store.categories"
      show-search
      @apply="onApply"
      @reset="onApply"
    />

    <div v-if="store.loading && !store.eventsSummary" class="mb-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
      <div v-for="n in 4" :key="n" class="h-28 animate-pulse rounded-[12px] bg-zinc-100" />
    </div>

    <div v-else class="mb-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
      <div
        v-for="card in kpiCards"
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

    <div class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100">
      <div v-if="store.loading && !store.events.length" class="space-y-3 px-6 py-6 sm:px-8">
        <div v-for="n in 6" :key="n" class="h-14 animate-pulse rounded-[12px] bg-zinc-100" />
      </div>

      <EmptyState
        v-else-if="!store.events.length"
        title="No analytics events"
        description="No analytics events were recorded in this period. Try a different date range or category."
      >
        <template #action>
          <button
            type="button"
            class="rounded-[12px] border border-zinc-200 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
            @click="onApply({})"
          >
            Reset filters
          </button>
        </template>
      </EmptyState>

      <div v-else class="overflow-x-auto px-3">
        <table class="min-w-full text-sm">
          <thead>
            <tr class="border-b border-zinc-100">
              <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Occurred</th>
              <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Category</th>
              <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Event</th>
              <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Source</th>
              <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Subject</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="event in store.events"
              :key="event.uuid"
              class="border-b border-zinc-50 last:border-0 transition hover:bg-zinc-50/80"
            >
              <td class="px-5 py-4 text-slate-600">{{ formatDate(event.occurred_at) }}</td>
              <td class="px-5 py-4 capitalize text-slate-600">{{ event.category }}</td>
              <td class="px-5 py-4 font-medium text-slate-900">{{ event.event_name }}</td>
              <td class="px-5 py-4 text-slate-600">{{ event.event_source || '—' }}</td>
              <td class="px-5 py-4 text-slate-600">
                <span v-if="event.subject_type">{{ event.subject_type }}#{{ event.subject_id }}</span>
                <span v-else>—</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-if="store.eventsMeta?.total" class="border-t border-zinc-100 px-6 py-4 sm:px-8">
        <Pagination
          :meta="store.eventsMeta"
          :loading="store.loading"
          @change="onPageChange"
          @per-page="onPerPage"
        />
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, watch } from 'vue';
import { RouterLink } from 'vue-router';
import {
  BoltIcon,
  BookmarkIcon,
  FolderIcon,
  Squares2X2Icon,
  TagIcon,
} from '@heroicons/vue/24/outline';
import { useToast } from '@/composables/useToast';
import EmptyState from '@/components/ui/EmptyState.vue';
import AnalyticsSubnav from '@/modules/analytics/components/AnalyticsSubnav.vue';
import EnterpriseFilterBar from '@/modules/analytics/components/EnterpriseFilterBar.vue';
import { useEnterpriseAnalyticsStore } from '@/modules/analytics/stores/enterpriseAnalytics';
import Pagination from '@/modules/users/components/Pagination.vue';

const store = useEnterpriseAnalyticsStore();
const toast = useToast();

const categoryIconStyles = [
  { icon: FolderIcon, iconBg: 'bg-sky-50', iconColor: 'text-sky-500' },
  { icon: TagIcon, iconBg: 'bg-indigo-50', iconColor: 'text-indigo-500' },
  { icon: FolderIcon, iconBg: 'bg-amber-50', iconColor: 'text-amber-500' },
];

const topCategories = computed(() => {
  const byCategory = store.eventsSummary?.by_category || {};
  return Object.entries(byCategory)
    .sort((a, b) => b[1] - a[1])
    .slice(0, 3);
});

const kpiCards = computed(() => {
  const total = Number(store.eventsSummary?.total || 0);
  const cards = [
    {
      label: 'Total events',
      value: formatNumber(total),
      hint: 'In the selected period',
      icon: BoltIcon,
      iconBg: total ? 'bg-brand-50' : 'bg-zinc-100',
      iconColor: total ? 'text-brand-500' : 'text-slate-500',
    },
  ];

  topCategories.value.forEach(([category, count], index) => {
    const style = categoryIconStyles[index] || categoryIconStyles[0];
    const value = Number(count || 0);
    cards.push({
      label: category,
      value: formatNumber(value),
      hint: 'Top category',
      icon: style.icon,
      iconBg: value ? style.iconBg : 'bg-zinc-100',
      iconColor: value ? style.iconColor : 'text-slate-500',
    });
  });

  return cards;
});

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

function formatNumber(value) {
  return new Intl.NumberFormat().format(Number(value || 0));
}

function formatDate(value) {
  if (!value) return '—';
  return new Date(value).toLocaleString();
}

function onApply(next) {
  store.filters = { ...store.filters, ...next };
  store.fetchEvents({ page: 1 });
  store.fetchEventsSummary();
}

function onPageChange(page) {
  store.fetchEvents({ page }).catch(() => {});
}

function onPerPage(perPage) {
  store.filters = { ...store.filters, per_page: perPage };
  store.fetchEvents({ per_page: perPage, page: 1 }).catch(() => {});
}

onMounted(async () => {
  store.successMessage = null;
  store.error = null;
  if (!store.categories.length) {
    await store.fetchOverview();
  }
  await Promise.all([store.fetchEvents(), store.fetchEventsSummary()]);
});
</script>
