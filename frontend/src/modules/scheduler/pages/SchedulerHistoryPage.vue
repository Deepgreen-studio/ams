<template>
  <div>
    <SchedulerSubnav />

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

    <RunsTable
      :runs="store.runs"
      :loading="store.loading"
      :meta="store.runMeta"
      empty-title="No job history yet"
      empty-description="Execution history for all scheduled jobs will appear here."
    >
      <template #toolbar>
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
          <div class="relative min-w-0 flex-1 lg:max-w-sm">
            <MagnifyingGlassIcon
              class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
            />
            <input
              v-model="filters.search"
              type="search"
              placeholder="Search jobs or messages…"
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
      </template>

      <template #emptyAction>
        <button
          type="button"
          class="rounded-[12px] border border-zinc-200 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
          @click="resetFilters"
        >
          Reset
        </button>
      </template>

      <template #footer>
        <Pagination
          v-if="store.runMeta?.total"
          :meta="store.runMeta"
          :loading="store.loading"
          @change="onPageChange"
          @per-page="onPerPageChange"
        />
      </template>
    </RunsTable>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, watch } from 'vue';
import {
  CheckCircleIcon,
  ExclamationCircleIcon,
  MagnifyingGlassIcon,
  PlayCircleIcon,
  QueueListIcon,
} from '@heroicons/vue/24/outline';
import { useToast } from '@/composables/useToast';
import Pagination from '@/modules/users/components/Pagination.vue';
import SelectBox from '@/modules/users/components/SelectBox.vue';
import RunsTable from '@/modules/scheduler/components/RunsTable.vue';
import SchedulerSubnav from '@/modules/scheduler/components/SchedulerSubnav.vue';
import { useSchedulerStore } from '@/modules/scheduler/stores/scheduler';

const store = useSchedulerStore();
const toast = useToast();

const filters = reactive({
  search: '',
  status: '',
  page: 1,
  per_page: 20,
});

const statusOptions = [
  { value: '', label: 'All statuses' },
  { value: 'pending', label: 'Pending' },
  { value: 'queued', label: 'Queued' },
  { value: 'running', label: 'Running' },
  { value: 'success', label: 'Success' },
  { value: 'failed', label: 'Failed' },
  { value: 'cancelled', label: 'Cancelled' },
];

const hasStats = computed(() => store.runStatistics != null);

const statCards = computed(() => [
  {
    label: 'Total runs',
    value: store.runStatistics?.total ?? 0,
    icon: QueueListIcon,
    iconBg: 'bg-brand-50',
    iconColor: 'text-brand-500',
  },
  {
    label: 'Success',
    value: store.runStatistics?.success ?? 0,
    icon: CheckCircleIcon,
    iconBg: 'bg-emerald-50',
    iconColor: 'text-emerald-500',
  },
  {
    label: 'Failed',
    value: store.runStatistics?.failed ?? 0,
    icon: ExclamationCircleIcon,
    iconBg: 'bg-rose-50',
    iconColor: 'text-rose-500',
  },
  {
    label: 'Running',
    value: store.runStatistics?.running ?? 0,
    icon: PlayCircleIcon,
    iconBg: 'bg-amber-50',
    iconColor: 'text-amber-500',
  },
]);

watch(
  () => store.error,
  (message) => {
    if (!message) return;
    toast.error(message);
    store.error = null;
  },
);

async function load() {
  await store.fetchHistory({
    search: filters.search || undefined,
    status: filters.status || undefined,
    page: filters.page,
    per_page: filters.per_page,
  });
}

function applyFilters() {
  filters.page = 1;
  load();
}

function resetFilters() {
  filters.search = '';
  filters.status = '';
  filters.page = 1;
  load();
}

function onPageChange(page) {
  filters.page = page;
  load();
}

function onPerPageChange(perPage) {
  filters.per_page = perPage;
  filters.page = 1;
  load();
}

onMounted(() => {
  store.error = null;
  load();
});
</script>
