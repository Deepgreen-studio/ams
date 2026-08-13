<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <button
        type="button"
        class="rounded-[12px] border border-zinc-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50 disabled:opacity-60"
        :disabled="store.saving || !store.failed.length"
        @click="onRetryAll"
      >
        Retry all
      </button>
      <button
        type="button"
        class="rounded-[12px] border border-rose-200 bg-white px-5 py-2.5 text-sm font-medium text-rose-700 hover:bg-rose-50 disabled:opacity-60"
        :disabled="store.saving || !store.failed.length"
        @click="onFlush"
      >
        Flush all
      </button>
    </Teleport>

    <QueueSubnav />

    <div class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100">
      <div class="border-b border-zinc-100 px-6 py-5 sm:px-8 sm:py-6">
        <form class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between" @submit.prevent="applyFilters">
          <div class="relative min-w-0 flex-1 lg:max-w-sm">
            <MagnifyingGlassIcon
              class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
            />
            <input
              v-model="filters.search"
              type="search"
              placeholder="Search failed jobs…"
              class="h-10 w-full rounded-[12px] border border-zinc-200 bg-white py-2 pl-10 pr-3 text-sm text-slate-800 placeholder:text-slate-400 focus:border-brand-500 focus:outline-none focus:ring-0"
            />
          </div>
          <div class="flex flex-wrap items-center gap-2">
            <input
              v-model="filters.queue"
              type="text"
              placeholder="Queue"
              class="h-10 w-full rounded-[12px] border border-zinc-200 bg-white px-3 text-sm text-slate-800 placeholder:text-slate-400 focus:border-brand-500 focus:outline-none focus:ring-0 md:w-40"
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
        v-else-if="!store.failed.length"
        title="No failed jobs"
        description="Failed queue jobs will appear here so you can retry or remove them."
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
              <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Failed at</th>
              <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Job</th>
              <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Queue</th>
              <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Exception</th>
              <th class="px-5 py-3 text-right text-sm font-semibold text-zinc-500">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="item in store.failed"
              :key="item.uuid"
              class="border-b border-zinc-50 last:border-0 transition hover:bg-zinc-50/80"
            >
              <td class="whitespace-nowrap px-5 py-4 text-slate-500">
                {{ formatDate(item.failed_at) }}
              </td>
              <td class="px-5 py-4 font-medium text-slate-900">{{ item.display_name }}</td>
              <td class="px-5 py-4">
                <span class="rounded-full bg-zinc-100 px-2.5 py-1 text-xs font-medium text-slate-600">
                  {{ item.queue }}
                </span>
              </td>
              <td class="max-w-xs px-5 py-4">
                <p class="line-clamp-2 text-xs text-rose-600">{{ item.exception }}</p>
              </td>
              <td class="px-5 py-4">
                <div class="flex justify-end gap-2">
                  <button
                    type="button"
                    class="rounded-[12px] px-3 py-1.5 text-xs font-medium text-brand-700 hover:bg-brand-50 disabled:opacity-60"
                    :disabled="store.saving"
                    @click="retry(item.uuid)"
                  >
                    Retry
                  </button>
                  <button
                    type="button"
                    class="rounded-[12px] px-3 py-1.5 text-xs font-medium text-rose-700 hover:bg-rose-50 disabled:opacity-60"
                    :disabled="store.saving"
                    @click="forget(item.uuid)"
                  >
                    Remove
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-if="store.failedMeta?.total" class="border-t border-zinc-100 px-6 py-4 sm:px-8">
        <Pagination
          :meta="store.failedMeta"
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
import QueueSubnav from '@/modules/queue/components/QueueSubnav.vue';
import { useQueueStore } from '@/modules/queue/stores/queue';

const store = useQueueStore();
const toast = useToast();
const filters = reactive({ search: '', queue: '', page: 1, per_page: 10 });

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

onMounted(() => {
  store.successMessage = null;
  store.error = null;
  load();
});

function load() {
  const params = Object.fromEntries(
    Object.entries(filters).filter(([, v]) => v !== '' && v != null),
  );
  store.fetchFailed(params);
}

function applyFilters() {
  filters.page = 1;
  load();
}

function resetFilters() {
  filters.search = '';
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

async function retry(id) {
  await store.retryFailed(id);
  await load();
}

async function forget(id) {
  if (!window.confirm('Remove this failed job? This cannot be undone.')) return;
  await store.forgetFailed(id);
  await load();
}

async function onRetryAll() {
  if (!window.confirm('Retry all failed jobs?')) return;
  await store.retryAllFailed();
  await load();
}

async function onFlush() {
  if (!window.confirm('Flush all failed jobs? This cannot be undone.')) return;
  await store.flushFailed();
  await load();
}

function formatDate(value) {
  return value ? new Date(value).toLocaleString() : '—';
}
</script>
