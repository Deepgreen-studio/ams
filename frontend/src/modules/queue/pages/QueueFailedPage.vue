<template>
  <div>
    <PageHeader title="Failed Jobs" description="Inspect, retry, or remove failed background jobs.">
      <template #actions>
        <button
          type="button"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 disabled:opacity-60"
          :disabled="store.saving || !store.failed.length"
          @click="onRetryAll"
        >
          Retry all
        </button>
        <button
          type="button"
          class="rounded-lg border border-rose-300 px-4 py-2 text-sm font-medium text-rose-700 hover:bg-rose-50 disabled:opacity-60"
          :disabled="store.saving || !store.failed.length"
          @click="onFlush"
        >
          Flush all
        </button>
      </template>
    </PageHeader>
    <QueueSubnav />

    <div
      v-if="store.successMessage"
      class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700"
    >
      {{ store.successMessage }}
    </div>
    <div
      v-if="store.error"
      class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ store.error }}
    </div>

    <form
      class="mb-4 flex flex-col gap-3 rounded-xl border border-slate-200 bg-white p-4 md:flex-row md:items-end"
      @submit.prevent="load"
    >
      <div class="flex-1">
        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500"
          >Search</label
        >
        <input
          v-model="filters.search"
          type="search"
          class="w-full h-12 rounded-[12px] border border-slate-300 px-3 text-sm"
        />
      </div>
      <div class="w-full md:w-40">
        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500"
          >Queue</label
        >
        <input
          v-model="filters.queue"
          type="text"
          class="w-full h-12 rounded-[12px] border border-slate-300 px-3 text-sm"
        />
      </div>
      <button
        type="submit"
        class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
      >
        Filter
      </button>
    </form>

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
      <div v-if="store.loading" class="space-y-3 p-6">
        <div v-for="n in 5" :key="n" class="h-10 animate-pulse rounded bg-slate-100" />
      </div>
      <div v-else-if="!store.failed.length" class="px-6 py-12 text-center text-sm text-slate-500">
        No failed jobs.
      </div>
      <table v-else class="min-w-full divide-y divide-slate-200 text-sm">
        <thead class="bg-slate-50">
          <tr>
            <th class="px-4 py-3 text-left font-semibold text-slate-600">Failed at</th>
            <th class="px-4 py-3 text-left font-semibold text-slate-600">Job</th>
            <th class="px-4 py-3 text-left font-semibold text-slate-600">Queue</th>
            <th class="px-4 py-3 text-left font-semibold text-slate-600">Exception</th>
            <th class="px-4 py-3 text-right font-semibold text-slate-600">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-for="item in store.failed" :key="item.uuid" class="hover:bg-slate-50/80">
            <td class="px-4 py-3 text-slate-600">{{ formatDate(item.failed_at) }}</td>
            <td class="px-4 py-3 font-medium text-slate-900">{{ item.display_name }}</td>
            <td class="px-4 py-3 text-slate-700">{{ item.queue }}</td>
            <td class="px-4 py-3 text-xs text-rose-600">{{ item.exception }}</td>
            <td class="px-4 py-3">
              <div class="flex justify-end gap-2">
                <button
                  type="button"
                  class="rounded-md px-2 py-1 text-xs font-medium text-brand-700 hover:bg-brand-50 disabled:opacity-60"
                  :disabled="store.saving"
                  @click="retry(item.uuid)"
                >
                  Retry
                </button>
                <button
                  type="button"
                  class="rounded-md px-2 py-1 text-xs font-medium text-rose-700 hover:bg-rose-50 disabled:opacity-60"
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
    <Pagination :meta="store.failedMeta" :loading="store.loading" @change="onPage" />
  </div>
</template>

<script setup>
import { onMounted, reactive } from 'vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import Pagination from '@/modules/users/components/Pagination.vue';
import QueueSubnav from '@/modules/queue/components/QueueSubnav.vue';
import { useQueueStore } from '@/modules/queue/stores/queue';

const store = useQueueStore();
const filters = reactive({ search: '', queue: '', page: 1, per_page: 10 });

onMounted(() => load());

function load() {
  const params = Object.fromEntries(
    Object.entries(filters).filter(([, v]) => v !== '' && v != null),
  );
  store.fetchFailed(params);
}

function onPage(page) {
  filters.page = page;
  load();
}

async function retry(id) {
  await store.retryFailed(id);
  await load();
}

async function forget(id) {
  await store.forgetFailed(id);
  await load();
}

async function onRetryAll() {
  await store.retryAllFailed();
  await load();
}

async function onFlush() {
  await store.flushFailed();
  await load();
}

function formatDate(value) {
  return value ? new Date(value).toLocaleString() : '—';
}
</script>
