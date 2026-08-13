<template>
  <div>
    <SyncSubnav />

    <div class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100">
      <div class="border-b border-zinc-100 px-6 py-5 sm:px-8 sm:py-6">
        <form
          class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between"
          @submit.prevent="applyFilters"
        >
          <div class="relative min-w-0 flex-1 lg:max-w-sm">
            <MagnifyingGlassIcon
              class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
            />
            <input
              v-model="filters.search"
              type="search"
              placeholder="Search message or record key…"
              class="h-10 w-full rounded-[12px] border border-zinc-200 bg-white py-2 pl-10 pr-3 text-sm text-slate-800 placeholder:text-slate-400 focus:border-brand-500 focus:outline-none focus:ring-0"
            />
          </div>
          <div class="flex flex-wrap items-center gap-2">
            <SelectBox
              v-model="filters.level"
              wrapper-class="min-w-[8.5rem]"
              :options="levelOptions"
              @change="applyFilters"
            />
            <SelectBox
              v-model="filters.action"
              wrapper-class="min-w-[9.5rem]"
              :options="actionOptions"
              @change="applyFilters"
            />
            <input
              v-model="filters.sync_run"
              type="text"
              placeholder="Run UUID"
              class="h-10 w-full rounded-[12px] border border-zinc-200 bg-white px-3 text-sm text-slate-800 placeholder:text-slate-400 focus:border-brand-500 focus:outline-none focus:ring-0 md:w-48"
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
        v-else-if="!store.logs.length"
        title="No sync logs found"
        description="Record-level sync actions, skips, conflicts, and failures will appear here."
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
              <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">When</th>
              <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Level</th>
              <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Action</th>
              <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Record</th>
              <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Message</th>
              <th class="px-5 py-3 text-right text-sm font-semibold text-zinc-500">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="item in store.logs"
              :key="item.uuid"
              class="border-b border-zinc-50 last:border-0 transition hover:bg-zinc-50/80"
            >
              <td class="whitespace-nowrap px-5 py-4 text-slate-500">
                {{ formatDate(item.created_at) }}
              </td>
              <td class="px-5 py-4">
                <span
                  class="rounded-full px-2.5 py-1 text-xs font-medium capitalize"
                  :class="levelClass(item.level)"
                >
                  {{ item.level }}
                </span>
              </td>
              <td class="px-5 py-4 capitalize text-slate-600">{{ item.action || '—' }}</td>
              <td class="px-5 py-4 font-mono text-xs text-slate-600">
                {{ item.record_key || '—' }}
              </td>
              <td class="max-w-xs px-5 py-4">
                <p class="line-clamp-2 text-slate-700">{{ item.message }}</p>
              </td>
              <td class="px-5 py-4 text-right">
                <button
                  type="button"
                  class="rounded-[12px] px-3 py-1.5 text-xs font-medium text-brand-700 hover:bg-brand-50"
                  @click="selected = item"
                >
                  View
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-if="store.logsMeta?.total" class="border-t border-zinc-100 px-6 py-4 sm:px-8">
        <Pagination
          :meta="store.logsMeta"
          :loading="store.loading"
          @change="onPage"
          @per-page="onPerPage"
        />
      </div>
    </div>

    <div v-if="selected" class="mt-4 rounded-[12px] bg-white p-6 ring-1 ring-zinc-100">
      <div class="mb-4 flex items-center justify-between gap-3">
        <h3 class="text-base font-semibold text-slate-900">Log detail</h3>
        <button
          type="button"
          class="rounded-[12px] px-3 py-1.5 text-xs font-medium text-slate-500 hover:bg-zinc-100 hover:text-slate-800"
          @click="selected = null"
        >
          Close
        </button>
      </div>
      <pre class="max-h-96 overflow-auto rounded-[12px] bg-slate-900 p-4 text-xs text-slate-100">{{
        JSON.stringify(selected, null, 2)
      }}</pre>
    </div>
  </div>
</template>

<script setup>
import { onMounted, reactive, ref, watch } from 'vue';
import { useRoute } from 'vue-router';
import { MagnifyingGlassIcon } from '@heroicons/vue/24/outline';
import { useToast } from '@/composables/useToast';
import EmptyState from '@/components/ui/EmptyState.vue';
import Pagination from '@/modules/users/components/Pagination.vue';
import SelectBox from '@/modules/users/components/SelectBox.vue';
import SyncSubnav from '@/modules/sync/components/SyncSubnav.vue';
import { useSyncStore } from '@/modules/sync/stores/sync';

const route = useRoute();
const store = useSyncStore();
const toast = useToast();
const selected = ref(null);
const filters = reactive({
  search: '',
  level: '',
  action: '',
  sync_run: route.query.sync_run || '',
  page: 1,
  per_page: 10,
});

const levelOptions = [
  { value: '', label: 'All levels' },
  { value: 'info', label: 'Info' },
  { value: 'warning', label: 'Warning' },
  { value: 'error', label: 'Error' },
];

const actionOptions = [
  { value: '', label: 'All actions' },
  { value: 'imported', label: 'Imported' },
  { value: 'updated', label: 'Updated' },
  { value: 'exported', label: 'Exported' },
  { value: 'skipped', label: 'Skipped' },
  { value: 'failed', label: 'Failed' },
  { value: 'conflict', label: 'Conflict' },
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
  selected.value = null;
  store.fetchLogs({ ...filters });
}

function applyFilters() {
  filters.page = 1;
  load();
}

function resetFilters() {
  filters.search = '';
  filters.level = '';
  filters.action = '';
  filters.sync_run = '';
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

function levelClass(level) {
  if (level === 'error') return 'bg-rose-50 text-rose-700';
  if (level === 'warning') return 'bg-amber-50 text-amber-700';
  return 'bg-sky-50 text-sky-700';
}
</script>
