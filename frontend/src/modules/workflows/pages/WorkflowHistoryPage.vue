<template>
  <div>
    <WorkflowsSubnav />

    <div class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100">
      <div class="border-b border-zinc-100 px-6 py-5 sm:px-8 sm:py-6">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
          <div class="relative min-w-0 flex-1 lg:max-w-sm">
            <MagnifyingGlassIcon
              class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
            />
            <input
              v-model="filters.search"
              type="search"
              placeholder="Search comments…"
              class="h-10 w-full rounded-[12px] border border-zinc-200 bg-white py-2 pl-10 pr-3 text-sm text-slate-800 placeholder:text-slate-400 focus:border-brand-500 focus:outline-none focus:ring-0"
              @keyup.enter="applyFilters"
            />
          </div>

          <div class="flex flex-wrap items-center gap-2">
            <SelectBox
              v-model="filters.action"
              wrapper-class="min-w-[10rem]"
              :options="actionOptions"
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
        title="No history yet"
        description="Workflow actions will appear here as instances are started, approved, or completed."
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
              <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Workflow</th>
              <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Action</th>
              <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Actor</th>
              <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Comment</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="log in store.logs"
              :key="log.uuid"
              class="border-b border-zinc-50 last:border-0 transition hover:bg-zinc-50/80"
            >
              <td class="whitespace-nowrap px-5 py-4 text-slate-600">{{ formatDate(log.created_at) }}</td>
              <td class="px-5 py-4 text-slate-800">
                {{ log.instance?.workflow?.name || log.instance?.subject_label || '—' }}
              </td>
              <td class="px-5 py-4">
                <span class="rounded-full bg-sky-50 px-2.5 py-1 text-xs font-medium text-sky-700">
                  {{ log.action_label || log.action }}
                </span>
              </td>
              <td class="px-5 py-4 text-slate-600">{{ log.actor?.full_name || 'System' }}</td>
              <td class="max-w-md px-5 py-4 text-slate-600">{{ log.comment || '—' }}</td>
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
import { onMounted, reactive, watch } from 'vue';
import { MagnifyingGlassIcon } from '@heroicons/vue/24/outline';
import EmptyState from '@/components/ui/EmptyState.vue';
import { useToast } from '@/composables/useToast';
import WorkflowsSubnav from '@/modules/workflows/components/WorkflowsSubnav.vue';
import Pagination from '@/modules/users/components/Pagination.vue';
import SelectBox from '@/modules/users/components/SelectBox.vue';
import { useWorkflowStore } from '@/modules/workflows/stores/workflow';

const store = useWorkflowStore();
const toast = useToast();
const filters = reactive({
  action: '',
  search: '',
  page: 1,
  per_page: 10,
});

watch(
  () => store.error,
  (message) => {
    if (!message) return;
    toast.error(message);
    store.error = null;
  },
);

const actionOptions = [
  { value: '', label: 'All actions' },
  { value: 'started', label: 'Started' },
  { value: 'approved', label: 'Approved' },
  { value: 'rejected', label: 'Rejected' },
  { value: 'escalated', label: 'Escalated' },
  { value: 'timed_out', label: 'Timed out' },
  { value: 'completed', label: 'Completed' },
];

function formatDate(value) {
  if (!value) return '—';
  return new Date(value).toLocaleString();
}

async function load() {
  await store.fetchHistory({
    action: filters.action || undefined,
    search: filters.search || undefined,
    page: filters.page,
    per_page: filters.per_page,
  });
}

function applyFilters() {
  filters.page = 1;
  load();
}

function resetFilters() {
  filters.action = '';
  filters.search = '';
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

onMounted(load);
</script>
