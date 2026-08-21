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
      <button
        type="button"
        class="inline-flex items-center gap-2 rounded-[12px] border border-zinc-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50 disabled:opacity-60"
        :disabled="exporting || !store.events.length"
        @click="onExport"
      >
        Export CSV
      </button>
      <button
        type="button"
        class="inline-flex items-center gap-2 rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
        :disabled="store.loading"
        @click="reload"
      >
        <ArrowPathIcon class="h-4 w-4" :class="{ 'animate-spin': store.loading }" />
        Refresh
      </button>
    </Teleport>

    <AnalyticsSubnav />

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
      <div class="border-b border-zinc-100 px-6 py-5 sm:px-8 sm:py-6">
        <EnterpriseFilterBar
          v-model="store.filters"
          embedded
          show-search
          :categories="store.categories"
          @apply="onApply"
          @reset="onApply"
        />
      </div>

      <AnalyticsEventsTable
        :events="store.events"
        :loading="store.loading"
        :framed="false"
        @select="selected = $event"
      >
        <template #empty-action>
          <button
            type="button"
            class="rounded-[12px] border border-zinc-200 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
            @click="onApply({})"
          >
            Reset filters
          </button>
        </template>
      </AnalyticsEventsTable>

      <div v-if="store.eventsMeta?.total" class="border-t border-zinc-100 px-6 py-4 sm:px-8">
        <Pagination
          :meta="store.eventsMeta"
          :loading="store.loading"
          @change="onPageChange"
          @per-page="onPerPage"
        />
      </div>
    </div>

    <EventDetailsDrawer
      :open="Boolean(selected)"
      :event="selected"
      @close="selected = null"
    />
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { RouterLink } from 'vue-router';
import {
  ArrowPathIcon,
  BoltIcon,
  CodeBracketIcon,
  Cog6ToothIcon,
  DevicePhoneMobileIcon,
  FolderIcon,
  Squares2X2Icon,
  TagIcon,
  UserGroupIcon,
} from '@heroicons/vue/24/outline';
import { useToast } from '@/composables/useToast';
import AnalyticsEventsTable from '@/modules/analytics/components/AnalyticsEventsTable.vue';
import AnalyticsSubnav from '@/modules/analytics/components/AnalyticsSubnav.vue';
import EnterpriseFilterBar from '@/modules/analytics/components/EnterpriseFilterBar.vue';
import EventDetailsDrawer from '@/modules/analytics/components/EventDetailsDrawer.vue';
import { useEnterpriseAnalyticsStore } from '@/modules/analytics/stores/enterpriseAnalytics';
import Pagination from '@/modules/users/components/Pagination.vue';

const store = useEnterpriseAnalyticsStore();
const toast = useToast();
const selected = ref(null);
const exporting = ref(false);

const categoryStyles = {
  business: { icon: FolderIcon, iconBg: 'bg-sky-50', iconColor: 'text-sky-500' },
  customer: { icon: UserGroupIcon, iconBg: 'bg-indigo-50', iconColor: 'text-indigo-500' },
  application: { icon: DevicePhoneMobileIcon, iconBg: 'bg-amber-50', iconColor: 'text-amber-500' },
  api: { icon: CodeBracketIcon, iconBg: 'bg-violet-50', iconColor: 'text-violet-500' },
  operational: { icon: Cog6ToothIcon, iconBg: 'bg-emerald-50', iconColor: 'text-emerald-500' },
};

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

  topCategories.value.forEach(([category, count]) => {
    const style = categoryStyles[String(category).toLowerCase()] || {
      icon: TagIcon,
      iconBg: 'bg-zinc-100',
      iconColor: 'text-slate-500',
    };
    const value = Number(count || 0);
    const share = total ? Math.round((value / total) * 100) : 0;
    cards.push({
      label: formatLabel(category),
      value: formatNumber(value),
      hint: total ? `${share}% of events` : 'No events in this category',
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

function formatLabel(value) {
  if (!value) {
    return '';
  }

  return String(value)
    .replace(/[_-]+/g, ' ')
    .replace(/\b\w/g, (character) => character.toUpperCase());
}

function onApply(next) {
  store.filters = { ...store.filters, ...next };
  store.fetchEvents({ page: 1 }).catch(() => {});
  store.fetchEventsSummary().catch(() => {});
}

function onPageChange(page) {
  store.fetchEvents({ page }).catch(() => {});
}

function onPerPage(perPage) {
  store.filters = { ...store.filters, per_page: perPage };
  store.fetchEvents({ per_page: perPage, page: 1 }).catch(() => {});
}

async function reload() {
  await Promise.all([store.fetchEvents(), store.fetchEventsSummary()]).catch(() => {});
}

function csvValue(value) {
  const text = value == null ? '' : String(value);
  return `"${text.replace(/"/g, '""')}"`;
}

function onExport() {
  exporting.value = true;
  try {
    const header = ['Occurred', 'Category', 'Event', 'Source', 'Subject', 'User', 'Company'];
    const rows = store.events.map((event) => [
      event.occurred_at || '',
      event.category || '',
      event.event_name || '',
      event.event_source || '',
      event.subject_type ? `${event.subject_type}#${event.subject_id || ''}` : '',
      event.user?.full_name || event.user?.email || '',
      event.company?.company_name || '',
    ]);
    const csv = [header, ...rows].map((row) => row.map(csvValue).join(',')).join('\n');
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = `analytics-events-${Date.now()}.csv`;
    link.click();
    URL.revokeObjectURL(url);
  } finally {
    exporting.value = false;
  }
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
