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
        <SearchFilters
          embedded
          show-status
          :show-module="false"
          :show-action="false"
          placeholder="Search user, IP, device…"
          :model-value="store.filters"
          @submit="onFilter"
          @reset="onReset"
        />
      </div>

      <LoginHistoryTable
        :items="store.items"
        :loading="store.loading"
        :framed="false"
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
      </LoginHistoryTable>

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
      title="Login details"
      :subtitle="selected?.user?.full_name || selected?.user?.email || ''"
      @close="selected = null"
    />
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import {
  ArrowPathIcon,
  CheckCircleIcon,
  IdentificationIcon,
  ShieldExclamationIcon,
  UsersIcon,
} from '@heroicons/vue/24/outline';
import { useToast } from '@/composables/useToast';
import AuditTabs from '@/modules/audit/components/AuditTabs.vue';
import LogDetailsModal from '@/modules/audit/components/LogDetailsModal.vue';
import LoginHistoryTable from '@/modules/audit/components/LoginHistoryTable.vue';
import SearchFilters from '@/modules/audit/components/SearchFilters.vue';
import { useLoginHistoryStore } from '@/modules/audit/stores/audit';
import Pagination from '@/modules/users/components/Pagination.vue';

const store = useLoginHistoryStore();
const toast = useToast();
const selected = ref(null);
const exporting = ref(false);

const cards = computed(() => {
  const items = store.items || [];
  const total = store.meta?.total ?? items.length;
  const success = items.filter((item) => String(item.status || '').toLowerCase() === 'success').length;
  const failed = items.filter((item) => String(item.status || '').toLowerCase() === 'failed').length;
  const uniqueUsers = new Set(
    items.map((item) => item.user?.uuid || item.user?.email).filter(Boolean),
  ).size;

  return [
    {
      label: 'Total',
      value: total,
      hint: 'Matching sign-in attempts',
      icon: IdentificationIcon,
      iconBg: 'bg-brand-50',
      iconColor: 'text-brand-500',
    },
    {
      label: 'Successful',
      value: success,
      hint: success ? 'On this page' : 'No successful logins on this page',
      icon: CheckCircleIcon,
      iconBg: success ? 'bg-emerald-50' : 'bg-zinc-100',
      iconColor: success ? 'text-emerald-500' : 'text-slate-500',
    },
    {
      label: 'Failed',
      value: failed,
      hint: failed ? 'On this page' : 'No failed attempts on this page',
      icon: ShieldExclamationIcon,
      iconBg: failed ? 'bg-rose-50' : 'bg-emerald-50',
      iconColor: failed ? 'text-rose-500' : 'text-emerald-500',
    },
    {
      label: 'Users',
      value: uniqueUsers,
      hint: uniqueUsers ? 'Distinct accounts on this page' : 'No users on this page',
      icon: UsersIcon,
      iconBg: uniqueUsers ? 'bg-sky-50' : 'bg-zinc-100',
      iconColor: uniqueUsers ? 'text-sky-500' : 'text-slate-500',
    },
  ];
});

watch(
  () => store.error,
  (message) => {
    if (!message) {
      return;
    }
    toast.error(message);
    store.error = null;
  },
);

onMounted(() => {
  store.error = null;
  store.fetchList().catch(() => {});
});

function defaultFilters() {
  return {
    search: '',
    date_from: '',
    date_to: '',
    status: '',
    per_page: 15,
    page: 1,
  };
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
  const text = value == null ? '' : String(value);
  return `"${text.replace(/"/g, '""')}"`;
}

function onExport() {
  exporting.value = true;
  try {
    const header = ['Login', 'User', 'Email', 'Status', 'Browser', 'OS', 'Device', 'IP'];
    const rows = store.items.map((item) => [
      item.login_at || '',
      item.user?.full_name || '',
      item.user?.email || '',
      item.status || '',
      item.browser || '',
      item.operating_system || '',
      item.device || '',
      item.ip_address || '',
    ]);
    const csv = [header, ...rows].map((row) => row.map(csvValue).join(',')).join('\n');
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = `login-history-${Date.now()}.csv`;
    link.click();
    URL.revokeObjectURL(url);
  } finally {
    exporting.value = false;
  }
}
</script>
