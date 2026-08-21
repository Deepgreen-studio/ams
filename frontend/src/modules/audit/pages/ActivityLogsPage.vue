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

    <div class="grid gap-4 lg:grid-cols-3">
      <div class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100 lg:col-span-2">
        <div class="border-b border-zinc-100 px-6 py-5 sm:px-8 sm:py-6">
          <SearchFilters
            embedded
            placeholder="Search description, module…"
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
          empty-title="No activity logs"
          empty-description="Platform activity will appear here as users take actions."
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

      <div class="rounded-[12px] bg-white p-5 ring-1 ring-zinc-100">
        <h3 class="mb-4 text-xs font-semibold uppercase tracking-wide text-slate-500">Timeline</h3>
        <TimelineComponent :items="store.items.slice(0, 8)" />
      </div>
    </div>

    <LogDetailsModal
      :open="Boolean(selected)"
      :item="selected"
      title="Activity details"
      :subtitle="selected?.description || ''"
      @close="selected = null"
    />
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { ArrowPathIcon, ClockIcon, CubeIcon, DocumentTextIcon, UsersIcon } from '@heroicons/vue/24/outline';
import { useToast } from '@/composables/useToast';
import AuditRecordsTable from '@/modules/audit/components/AuditRecordsTable.vue';
import AuditTabs from '@/modules/audit/components/AuditTabs.vue';
import LogDetailsModal from '@/modules/audit/components/LogDetailsModal.vue';
import SearchFilters from '@/modules/audit/components/SearchFilters.vue';
import TimelineComponent from '@/modules/audit/components/TimelineComponent.vue';
import { auditService } from '@/modules/audit/services/auditService';
import { useActivityStore } from '@/modules/audit/stores/audit';
import Pagination from '@/modules/users/components/Pagination.vue';

const store = useActivityStore();
const toast = useToast();
const selected = ref(null);
const exporting = ref(false);

const columns = [
  {
    key: 'description',
    label: 'Activity',
    type: 'primary',
    subtitle: (item) => formatDate(item.created_at),
  },
  {
    key: 'module',
    label: 'Module',
    type: 'badge',
    hide: 'hidden md:table-cell',
  },
  {
    key: 'user',
    label: 'User',
    hide: 'hidden lg:table-cell',
    format: (item) => item.user?.full_name || item.user?.email || 'System',
  },
];

const cards = computed(() => {
  const items = store.items || [];
  const total = store.meta?.total ?? items.length;
  const modules = new Set(items.map((item) => item.module).filter(Boolean)).size;
  const users = new Set(items.map((item) => item.user?.email || item.user?.uuid).filter(Boolean)).size;

  return [
    {
      label: 'Total',
      value: total,
      hint: 'Matching activity records',
      icon: DocumentTextIcon,
      iconBg: 'bg-brand-50',
      iconColor: 'text-brand-500',
    },
    {
      label: 'Modules',
      value: modules,
      hint: modules ? 'On this page' : 'No modules on this page',
      icon: CubeIcon,
      iconBg: modules ? 'bg-sky-50' : 'bg-zinc-100',
      iconColor: modules ? 'text-sky-500' : 'text-slate-500',
    },
    {
      label: 'Actors',
      value: users,
      hint: users ? 'Distinct users on this page' : 'No users on this page',
      icon: UsersIcon,
      iconBg: users ? 'bg-indigo-50' : 'bg-zinc-100',
      iconColor: users ? 'text-indigo-500' : 'text-slate-500',
    },
    {
      label: 'This page',
      value: items.length,
      hint: 'Records currently shown',
      icon: ClockIcon,
      iconBg: items.length ? 'bg-emerald-50' : 'bg-zinc-100',
      iconColor: items.length ? 'text-emerald-500' : 'text-slate-500',
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

async function onExport() {
  exporting.value = true;
  try {
    const params = Object.fromEntries(
      Object.entries(store.filters).filter(([, value]) => value !== '' && value != null),
    );
    const { data } = await auditService.exportActivityLogs(params);
    const url = URL.createObjectURL(data);
    const link = document.createElement('a');
    link.href = url;
    link.download = `activity-logs-${Date.now()}.csv`;
    link.click();
    URL.revokeObjectURL(url);
  } finally {
    exporting.value = false;
  }
}
</script>
