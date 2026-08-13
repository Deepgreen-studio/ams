<template>
  <div>
    <QueueSubnav />

    <div class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100">
      <div class="border-b border-zinc-100 px-6 py-5 sm:px-8 sm:py-6">
        <form class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between" @submit.prevent="applyFilters">
          <div class="relative min-w-0 flex-1 lg:max-w-sm">
            <MagnifyingGlassIcon
              class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
            />
            <input
              v-model="filters.queue"
              type="search"
              placeholder="Filter by queue name…"
              class="h-10 w-full rounded-[12px] border border-zinc-200 bg-white py-2 pl-10 pr-3 text-sm text-slate-800 placeholder:text-slate-400 focus:border-brand-500 focus:outline-none focus:ring-0"
            />
          </div>
          <div class="flex flex-wrap items-center gap-2">
            <SelectBox
              v-model="filters.type"
              wrapper-class="min-w-[9.5rem]"
              :options="typeOptions"
              @change="applyFilters"
            />
            <button
              type="submit"
              class="h-10 rounded-[12px] bg-brand-600 px-5 text-sm font-medium text-white hover:bg-brand-700"
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
        </form>
      </div>

      <div v-if="store.loading" class="space-y-3 px-6 py-6 sm:px-8">
        <div v-for="n in 6" :key="n" class="h-14 animate-pulse rounded-[12px] bg-zinc-100" />
      </div>

      <EmptyState
        v-else-if="!store.tracks.length"
        title="No running jobs"
        description="Jobs currently tracked as running will appear here."
        class="px-6 py-10 sm:px-8"
      >
        <template #action>
          <button
            type="button"
            class="rounded-[12px] border border-zinc-200 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
            @click="resetFilters"
          >
            Reset filters
          </button>
        </template>
      </EmptyState>

      <div v-else class="overflow-x-auto px-3">
        <table class="min-w-full text-sm">
          <thead>
            <tr class="border-b border-zinc-100">
              <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Job</th>
              <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Queue</th>
              <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Started</th>
              <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Attempts</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="item in store.tracks"
              :key="item.uuid"
              class="border-b border-zinc-50 last:border-0 transition hover:bg-zinc-50/80"
            >
              <td class="px-5 py-4">
                <p class="font-medium text-slate-900">{{ item.display_name }}</p>
                <p class="mt-0.5 text-xs capitalize text-slate-500">
                  {{ item.type }} · {{ item.priority }}
                </p>
              </td>
              <td class="px-5 py-4">
                <span class="rounded-full bg-zinc-100 px-2.5 py-1 text-xs font-medium text-slate-600">
                  {{ item.queue }}
                </span>
              </td>
              <td class="whitespace-nowrap px-5 py-4 text-slate-500">
                {{ formatDate(item.started_at) }}
              </td>
              <td class="px-5 py-4 text-slate-700">{{ item.attempts }}/{{ item.max_tries }}</td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-if="store.tracksMeta?.total" class="border-t border-zinc-100 px-6 py-4 sm:px-8">
        <Pagination
          :meta="store.tracksMeta"
          :loading="store.loading"
          @change="onPage"
          @per-page="onPerPage"
        />
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, reactive, watch } from 'vue';
import { MagnifyingGlassIcon } from '@heroicons/vue/24/outline';
import { useToast } from '@/composables/useToast';
import EmptyState from '@/components/ui/EmptyState.vue';
import Pagination from '@/modules/users/components/Pagination.vue';
import SelectBox from '@/modules/users/components/SelectBox.vue';
import QueueSubnav from '@/modules/queue/components/QueueSubnav.vue';
import { useQueueStore } from '@/modules/queue/stores/queue';

const store = useQueueStore();
const toast = useToast();
const filters = reactive({ type: '', queue: '', page: 1, per_page: 10 });

const typeOptions = [
  { value: '', label: 'All types' },
  { value: 'import', label: 'Import' },
  { value: 'export', label: 'Export' },
  { value: 'webhook', label: 'Webhook' },
  { value: 'sync', label: 'Sync' },
  { value: 'notification', label: 'Notification' },
];

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
  load();
});

function load() {
  const params = Object.fromEntries(
    Object.entries(filters).filter(([, v]) => v !== '' && v != null),
  );
  store.fetchRunning(params);
}

function applyFilters() {
  filters.page = 1;
  load();
}

function resetFilters() {
  filters.type = '';
  filters.queue = '';
  filters.page = 1;
  load();
}

function onPage(page) {
  filters.page = page;
  load();
}

function onPerPage(perPage) {
  filters.per_page = perPage;
  filters.page = 1;
  load();
}

function formatDate(value) {
  return value ? new Date(value).toLocaleString() : '—';
}
</script>
