<template>
  <div>
    <PageHeader
      title="Running Jobs"
      description="Jobs currently tracked as running by the Queue Monitor."
    />
    <QueueSubnav />

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
      <div class="w-full md:w-40">
        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500"
          >Type</label
        >
        <select
          v-model="filters.type"
          class="w-full h-12 rounded-[12px] border border-slate-300 px-3 text-sm"
        >
          <option value="">All</option>
          <option value="import">Import</option>
          <option value="export">Export</option>
          <option value="webhook">Webhook</option>
          <option value="sync">Sync</option>
          <option value="notification">Notification</option>
        </select>
      </div>
      <div class="w-full md:w-40">
        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500"
          >Queue</label
        >
        <input
          v-model="filters.queue"
          type="text"
          class="w-full h-12 rounded-[12px] border border-slate-300 px-3 text-sm"
          placeholder="imports"
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
      <div v-else-if="!store.tracks.length" class="px-6 py-12 text-center text-sm text-slate-500">
        No running jobs.
      </div>
      <table v-else class="min-w-full divide-y divide-slate-200 text-sm">
        <thead class="bg-slate-50">
          <tr>
            <th class="px-4 py-3 text-left font-semibold text-slate-600">Job</th>
            <th class="px-4 py-3 text-left font-semibold text-slate-600">Queue</th>
            <th class="px-4 py-3 text-left font-semibold text-slate-600">Started</th>
            <th class="px-4 py-3 text-left font-semibold text-slate-600">Attempts</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-for="item in store.tracks" :key="item.uuid">
            <td class="px-4 py-3">
              <p class="font-medium text-slate-900">{{ item.display_name }}</p>
              <p class="text-xs capitalize text-slate-500">{{ item.type }} · {{ item.priority }}</p>
            </td>
            <td class="px-4 py-3 text-slate-700">{{ item.queue }}</td>
            <td class="px-4 py-3 text-slate-600">{{ formatDate(item.started_at) }}</td>
            <td class="px-4 py-3 text-slate-700">{{ item.attempts }}/{{ item.max_tries }}</td>
          </tr>
        </tbody>
      </table>
    </div>
    <Pagination :meta="store.tracksMeta" :loading="store.loading" @change="onPage" />
  </div>
</template>

<script setup>
import { onMounted, reactive } from 'vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import Pagination from '@/modules/users/components/Pagination.vue';
import QueueSubnav from '@/modules/queue/components/QueueSubnav.vue';
import { useQueueStore } from '@/modules/queue/stores/queue';

const store = useQueueStore();
const filters = reactive({ type: '', queue: '', page: 1, per_page: 10 });

onMounted(() => load());

function load() {
  const params = Object.fromEntries(
    Object.entries(filters).filter(([, v]) => v !== '' && v != null),
  );
  store.fetchRunning(params);
}

function onPage(page) {
  filters.page = page;
  load();
}

function formatDate(value) {
  return value ? new Date(value).toLocaleString() : '—';
}
</script>
