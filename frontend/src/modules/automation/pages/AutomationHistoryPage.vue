<template>
  <div>
    <AutomationSubnav />

    <div
      v-if="store.error"
      class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ store.error }}
    </div>

    <div v-if="store.loading && !hasStats" class="mb-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
      <div v-for="n in 4" :key="n" class="h-28 animate-pulse rounded-[12px] bg-zinc-100" />
    </div>

    <div v-else class="mb-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
      <div
        v-for="card in statCards"
        :key="card.label"
        class="flex items-center justify-between gap-4 rounded-[12px] bg-white px-6 py-5 ring-1 ring-zinc-100 transition hover:ring-brand-200"
      >
        <div class="min-w-0">
          <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ card.label }}</p>
          <p class="mt-1 text-2xl font-bold tracking-tight text-slate-900">{{ card.value }}</p>
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
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
          <div class="relative min-w-0 flex-1 lg:max-w-sm">
            <MagnifyingGlassIcon
              class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
            />
            <input
              v-model="filters.event_key"
              type="search"
              placeholder="Filter by event key…"
              class="h-10 w-full rounded-[12px] border border-zinc-200 bg-white py-2 pl-10 pr-3 text-sm text-slate-800 placeholder:text-slate-400 focus:border-brand-500 focus:outline-none focus:ring-0"
              @keyup.enter="applyFilters"
            />
          </div>

          <div class="flex flex-wrap items-center gap-2">
            <SelectBox
              v-model="filters.status"
              wrapper-class="min-w-[9.5rem]"
              :options="statusOptions"
              @change="applyFilters"
            />
            <button
              type="button"
              class="h-10 rounded-[12px] bg-brand-600 px-5 text-sm font-medium text-white hover:bg-brand-700"
              @click="applyFilters"
            >
              Apply
            </button>
            <button
              type="button"
              class="h-10 rounded-[12px] border border-zinc-200 px-5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
              @click="resetFilters"
            >
              Reset
            </button>
          </div>
        </div>
      </div>

      <div v-if="store.loading" class="space-y-3 px-6 py-6 sm:px-8">
        <div v-for="n in 6" :key="n" class="h-14 animate-pulse rounded-[12px] bg-zinc-100" />
      </div>

      <EmptyState
        v-else-if="!store.logs.length"
        title="No automation runs yet"
        description="Execution logs will appear here when rules run."
        class="px-6 py-10 sm:px-8"
      >
        <template #action>
          <button
            type="button"
            class="rounded-[12px] border border-zinc-200 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
            @click="resetFilters"
          >
            Reset
          </button>
        </template>
      </EmptyState>

      <div v-else class="overflow-x-auto px-3">
        <table class="min-w-full text-sm">
          <thead>
            <tr class="border-b border-zinc-100">
              <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">When</th>
              <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Rule</th>
              <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Event</th>
              <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Status</th>
              <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Message</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="log in store.logs"
              :key="log.uuid"
              class="border-b border-zinc-50 last:border-0 transition hover:bg-zinc-50/80"
            >
              <td class="whitespace-nowrap px-5 py-4 text-slate-500">
                {{ formatDate(log.created_at) }}
              </td>
              <td class="px-5 py-4 font-medium text-slate-900">
                {{ log.rule?.name || '—' }}
              </td>
              <td class="px-5 py-4">
                <span class="font-mono text-xs text-slate-600">
                  {{ log.event_key || log.trigger_type || '—' }}
                </span>
              </td>
              <td class="px-5 py-4">
                <span
                  class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium"
                  :class="statusClass(log.status)"
                >
                  {{ log.status }}
                </span>
              </td>
              <td class="max-w-md px-5 py-4 text-slate-600">
                <p class="line-clamp-2">{{ log.message || log.error_message || '—' }}</p>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div
        v-if="store.logMeta?.total"
        class="border-t border-zinc-100 px-6 py-4 sm:px-8"
      >
        <Pagination
          :meta="store.logMeta"
          :loading="store.loading"
          @change="onPageChange"
          @per-page="onPerPageChange"
        />
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive } from 'vue';
import {
  CheckCircleIcon,
  ExclamationCircleIcon,
  ForwardIcon,
  MagnifyingGlassIcon,
  QueueListIcon,
} from '@heroicons/vue/24/outline';
import EmptyState from '@/components/ui/EmptyState.vue';
import Pagination from '@/modules/users/components/Pagination.vue';
import SelectBox from '@/modules/users/components/SelectBox.vue';
import AutomationSubnav from '@/modules/automation/components/AutomationSubnav.vue';
import { useAutomationStore } from '@/modules/automation/stores/automation';

const store = useAutomationStore();

const filters = reactive({
  status: '',
  event_key: '',
  page: 1,
  per_page: 20,
});

const statusOptions = [
  { value: '', label: 'All statuses' },
  { value: 'success', label: 'Success' },
  { value: 'failed', label: 'Failed' },
  { value: 'skipped', label: 'Skipped' },
  { value: 'partial', label: 'Partial' },
];

const hasStats = computed(() => store.logStatistics != null);

const statCards = computed(() => [
  {
    label: 'Total runs',
    value: store.logStatistics?.total ?? 0,
    icon: QueueListIcon,
    iconBg: 'bg-brand-50',
    iconColor: 'text-brand-500',
  },
  {
    label: 'Success',
    value: store.logStatistics?.success ?? 0,
    icon: CheckCircleIcon,
    iconBg: 'bg-emerald-50',
    iconColor: 'text-emerald-500',
  },
  {
    label: 'Failed',
    value: store.logStatistics?.failed ?? 0,
    icon: ExclamationCircleIcon,
    iconBg: 'bg-rose-50',
    iconColor: 'text-rose-500',
  },
  {
    label: 'Skipped',
    value: store.logStatistics?.skipped ?? 0,
    icon: ForwardIcon,
    iconBg: 'bg-zinc-100',
    iconColor: 'text-slate-500',
  },
]);

onMounted(reload);

async function reload() {
  await store.fetchLogs({
    status: filters.status || undefined,
    event_key: filters.event_key || undefined,
    page: filters.page,
    per_page: filters.per_page,
  });
}

function applyFilters() {
  filters.page = 1;
  reload();
}

function resetFilters() {
  filters.status = '';
  filters.event_key = '';
  filters.page = 1;
  reload();
}

function onPageChange(page) {
  filters.page = page;
  reload();
}

function onPerPageChange(perPage) {
  filters.per_page = perPage;
  filters.page = 1;
  reload();
}

function statusClass(status) {
  if (status === 'success') return 'bg-emerald-50 text-emerald-700';
  if (status === 'failed') return 'bg-rose-50 text-rose-700';
  if (status === 'skipped') return 'bg-zinc-100 text-slate-600';
  return 'bg-amber-50 text-amber-700';
}

function formatDate(value) {
  if (!value) return '—';
  return new Date(value).toLocaleString();
}
</script>
