<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <button
        type="button"
        class="inline-flex items-center gap-2 rounded-[12px] border border-zinc-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50 disabled:opacity-60"
        :disabled="exporting || !store.items.length"
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

    <AuditTabs />

    <div v-if="store.loading && !store.meta" class="mb-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
      <div v-for="n in 4" :key="n" class="h-28 animate-pulse rounded-[12px] bg-zinc-100" />
    </div>
    <div v-else class="mb-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
      <div
        v-for="card in cards"
        :key="card.label"
        class="flex items-center justify-between gap-4 rounded-[12px] bg-white px-6 py-5 ring-1 ring-zinc-100 transition hover:ring-brand-200"
      >
        <div class="min-w-0">
          <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ card.label }}</p>
          <p class="mt-1 truncate text-2xl font-bold tracking-tight text-slate-900">{{ card.value }}</p>
          <p v-if="card.hint" class="mt-1 text-xs text-slate-400">{{ card.hint }}</p>
        </div>
        <div class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-[12px]" :class="card.iconBg">
          <component :is="card.icon" class="h-5 w-5" :class="card.iconColor" />
        </div>
      </div>
    </div>

    <div class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100">
      <div class="border-b border-zinc-100 px-6 py-5 sm:px-8 sm:py-6">
        <SearchFilters
          embedded
          placeholder="Search event, module…"
          :model-value="store.filters"
          @submit="onFilter"
          @reset="onReset"
        />
      </div>
      <AuditRecordsTable
        :items="store.items"
        :loading="store.loading"
        :framed="false"
        :columns="columns"
        empty-title="No system events"
        empty-description="Operational events will appear here as the platform records them."
        @select="selected = $event"
      >
        <template #empty-action>
          <button
            type="button"
            class="rounded-[12px] border border-zinc-200 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
            @click="onReset"
          >
            Reset filters
          </button>
        </template>
      </AuditRecordsTable>
      <div v-if="store.meta?.total" class="border-t border-zinc-100 px-6 py-4 sm:px-8">
        <Pagination
          :meta="store.meta"
          :loading="store.loading"
          @change="onPageChange"
          @per-page="onPerPage"
        />
      </div>
    </div>

    <LogDetailsModal
      :open="Boolean(selected)"
      :item="selected"
      title="System event"
      :subtitle="selected?.event || ''"
      @close="selected = null"
    />
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import {
  ArrowPathIcon,
  BoltIcon,
  ExclamationTriangleIcon,
  InformationCircleIcon,
  ShieldExclamationIcon,
} from '@heroicons/vue/24/outline';
import { useToast } from '@/composables/useToast';
import AuditRecordsTable from '@/modules/audit/components/AuditRecordsTable.vue';
import AuditTabs from '@/modules/audit/components/AuditTabs.vue';
import LogDetailsModal from '@/modules/audit/components/LogDetailsModal.vue';
import SearchFilters from '@/modules/audit/components/SearchFilters.vue';
import { useSystemEventsStore } from '@/modules/audit/stores/audit';
import Pagination from '@/modules/users/components/Pagination.vue';

const store = useSystemEventsStore();
const toast = useToast();
const selected = ref(null);
const exporting = ref(false);

const columns = [
  {
    key: 'event',
    label: 'Event',
    type: 'primary',
    subtitle: (item) => item.module || formatDate(item.created_at),
  },
  {
    key: 'level',
    label: 'Level',
    type: 'badge',
  },
  {
    key: 'created_at',
    label: 'When',
    hide: 'hidden lg:table-cell',
    nowrap: true,
    format: (item) => formatDate(item.created_at),
  },
];

const cards = computed(() => {
  const items = store.items || [];
  const total = store.meta?.total ?? items.length;
  const info = countLevel(items, ['info', 'notice']);
  const warning = countLevel(items, ['warning', 'warn']);
  const error = countLevel(items, ['error', 'critical', 'alert', 'emergency']);

  return [
    {
      label: 'Total',
      value: total,
      hint: 'Matching system events',
      icon: BoltIcon,
      iconBg: 'bg-brand-50',
      iconColor: 'text-brand-500',
    },
    {
      label: 'Info',
      value: info,
      hint: info ? 'On this page' : 'No info events on this page',
      icon: InformationCircleIcon,
      iconBg: info ? 'bg-sky-50' : 'bg-zinc-100',
      iconColor: info ? 'text-sky-500' : 'text-slate-500',
    },
    {
      label: 'Warnings',
      value: warning,
      hint: warning ? 'On this page' : 'No warnings on this page',
      icon: ExclamationTriangleIcon,
      iconBg: warning ? 'bg-amber-50' : 'bg-zinc-100',
      iconColor: warning ? 'text-amber-500' : 'text-slate-500',
    },
    {
      label: 'Errors',
      value: error,
      hint: error ? 'On this page' : 'No errors on this page',
      icon: ShieldExclamationIcon,
      iconBg: error ? 'bg-rose-50' : 'bg-emerald-50',
      iconColor: error ? 'text-rose-500' : 'text-emerald-500',
    },
  ];
});

watch(
  () => store.error,
  (message) => {
    if (!message) return;
    toast.error(message);
    store.error = null;
  },
);

onMounted(() => {
  store.error = null;
  store.fetchList().catch(() => {});
});

function countLevel(items, levels) {
  return items.filter((item) => levels.includes(String(item.level || '').toLowerCase())).length;
}

function formatDate(value) {
  return value ? new Date(value).toLocaleString() : '—';
}

function defaultFilters() {
  return { search: '', module: '', action: '', date_from: '', date_to: '', per_page: 15, page: 1 };
}

function onFilter(filters) {
  store.fetchList(filters).catch(() => {});
}

function onReset() {
  store.filters = defaultFilters();
  store.fetchList().catch(() => {});
}

function onPageChange(page) {
  store.fetchList({ page }).catch(() => {});
}

function onPerPage(perPage) {
  store.fetchList({ per_page: perPage, page: 1 }).catch(() => {});
}

function reload() {
  store.fetchList().catch(() => {});
}

function csvValue(value) {
  return `"${String(value ?? '').replace(/"/g, '""')}"`;
}

function onExport() {
  exporting.value = true;
  try {
    const header = ['When', 'Event', 'Module', 'Level'];
    const rows = store.items.map((item) => [item.created_at || '', item.event || '', item.module || '', item.level || '']);
    const csv = [header, ...rows].map((row) => row.map(csvValue).join(',')).join('\n');
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = `system-events-${Date.now()}.csv`;
    link.click();
    URL.revokeObjectURL(url);
  } finally {
    exporting.value = false;
  }
}
</script>
