<template>
  <div>
    <SyncSubnav />

    <div class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100">
      <div class="border-b border-zinc-100 px-6 py-5 sm:px-8 sm:py-6">
        <form
          class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-end"
          @submit.prevent="applyFilters"
        >
          <div class="flex flex-wrap items-center gap-2">
            <SelectBox
              v-model="filters.status"
              wrapper-class="min-w-[9.5rem]"
              :options="statusOptions"
              @change="applyFilters"
            />
            <SelectBox
              v-model="filters.trigger"
              wrapper-class="min-w-[9.5rem]"
              :options="triggerOptions"
              @change="applyFilters"
            />
            <SelectBox
              v-model="filters.mode"
              wrapper-class="min-w-[9.5rem]"
              :options="modeOptions"
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
        v-else-if="!store.runs.length"
        title="No sync runs found"
        description="Execution history for synchronization configs will appear here."
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
              <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Started</th>
              <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Config</th>
              <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Progress</th>
              <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Records</th>
              <th class="px-5 py-3 text-right text-sm font-semibold text-zinc-500">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="item in store.runs"
              :key="item.uuid"
              class="border-b border-zinc-50 last:border-0 transition hover:bg-zinc-50/80"
            >
              <td class="whitespace-nowrap px-5 py-4 text-slate-500">
                <p>{{ formatDate(item.started_at || item.created_at) }}</p>
                <p class="mt-0.5 text-xs capitalize text-slate-400">
                  {{ item.trigger }} · {{ item.mode }}
                </p>
              </td>
              <td class="px-5 py-4">
                <p class="font-medium text-slate-900">{{ item.config?.name || '—' }}</p>
                <span
                  class="mt-1 inline-flex rounded-full px-2.5 py-1 text-xs font-medium capitalize"
                  :class="statusClass(item.status)"
                >
                  {{ item.status }}
                </span>
              </td>
              <td class="min-w-[10rem] px-5 py-4">
                <SyncProgressBar :percent="item.progress_percent" :status="item.status" />
              </td>
              <td class="px-5 py-4 text-xs text-slate-600">
                {{ item.total_records }} total · {{ item.imported }} in · {{ item.updated }} upd ·
                {{ item.failed }} fail · {{ item.skipped }} skip
              </td>
              <td class="px-5 py-4">
                <div class="flex justify-end gap-2">
                  <button
                    type="button"
                    class="rounded-[12px] px-3 py-1.5 text-xs font-medium text-brand-700 hover:bg-brand-50"
                    @click="selected = item"
                  >
                    Details
                  </button>
                  <RouterLink
                    :to="{ name: 'sync.logs', query: { sync_run: item.uuid } }"
                    class="rounded-[12px] px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-zinc-100"
                  >
                    Logs
                  </RouterLink>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-if="store.runsMeta?.total" class="border-t border-zinc-100 px-6 py-4 sm:px-8">
        <Pagination
          :meta="store.runsMeta"
          :loading="store.loading"
          @change="onPage"
          @per-page="onPerPage"
        />
      </div>
    </div>

    <div v-if="selected" class="mt-4 rounded-[12px] bg-white p-6 ring-1 ring-zinc-100">
      <div class="mb-4 flex items-center justify-between gap-3">
        <h3 class="text-base font-semibold text-slate-900">Run detail</h3>
        <button
          type="button"
          class="rounded-[12px] px-3 py-1.5 text-xs font-medium text-slate-500 hover:bg-zinc-100 hover:text-slate-800"
          @click="selected = null"
        >
          Close
        </button>
      </div>
      <dl class="mb-4 grid gap-3 text-sm sm:grid-cols-3">
        <div>
          <dt class="text-xs text-slate-500">Started</dt>
          <dd class="mt-1 text-slate-800">{{ formatDate(selected.started_at) }}</dd>
        </div>
        <div>
          <dt class="text-xs text-slate-500">Completed</dt>
          <dd class="mt-1 text-slate-800">{{ formatDate(selected.completed_at) }}</dd>
        </div>
        <div>
          <dt class="text-xs text-slate-500">Failed</dt>
          <dd class="mt-1 text-slate-800">{{ formatDate(selected.failed_at) }}</dd>
        </div>
      </dl>
      <pre class="max-h-96 overflow-auto rounded-[12px] bg-slate-900 p-4 text-xs text-slate-100">{{
        JSON.stringify(selected, null, 2)
      }}</pre>
    </div>
  </div>
</template>

<script setup>
import { onMounted, reactive, ref, watch } from 'vue';
import { RouterLink } from 'vue-router';
import { useToast } from '@/composables/useToast';
import EmptyState from '@/components/ui/EmptyState.vue';
import Pagination from '@/modules/users/components/Pagination.vue';
import SelectBox from '@/modules/users/components/SelectBox.vue';
import SyncProgressBar from '@/modules/sync/components/SyncProgressBar.vue';
import SyncSubnav from '@/modules/sync/components/SyncSubnav.vue';
import { useSyncStore } from '@/modules/sync/stores/sync';

const store = useSyncStore();
const toast = useToast();
const selected = ref(null);
const filters = reactive({ status: '', trigger: '', mode: '', page: 1, per_page: 10 });

const statusOptions = [
  { value: '', label: 'All statuses' },
  { value: 'queued', label: 'Queued' },
  { value: 'running', label: 'Running' },
  { value: 'completed', label: 'Completed' },
  { value: 'failed', label: 'Failed' },
  { value: 'cancelled', label: 'Cancelled' },
];

const triggerOptions = [
  { value: '', label: 'All triggers' },
  { value: 'manual', label: 'Manual' },
  { value: 'automatic', label: 'Automatic' },
  { value: 'scheduled', label: 'Scheduled' },
  { value: 'background', label: 'Background' },
];

const modeOptions = [
  { value: '', label: 'All modes' },
  { value: 'full', label: 'Full' },
  { value: 'incremental', label: 'Incremental' },
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
  store.fetchRuns({ ...filters });
}

function applyFilters() {
  filters.page = 1;
  load();
}

function resetFilters() {
  filters.status = '';
  filters.trigger = '';
  filters.mode = '';
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

function statusClass(status) {
  if (status === 'completed') return 'bg-emerald-50 text-emerald-700';
  if (status === 'failed') return 'bg-rose-50 text-rose-700';
  if (status === 'running') return 'bg-amber-50 text-amber-700';
  if (status === 'queued') return 'bg-sky-50 text-sky-700';
  return 'bg-zinc-100 text-slate-600';
}

function formatDate(value) {
  return value ? new Date(value).toLocaleString() : '—';
}
</script>
