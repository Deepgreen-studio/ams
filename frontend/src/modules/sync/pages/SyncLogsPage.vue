<template>
  <div>
    <!-- <PageHeader
      title="Sync Logs"
      description="Record-level sync actions, skips, conflicts, and failures."
    /> -->
    <SyncSubnav />

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
          placeholder="Message, record key..."
        />
      </div>
      <div class="w-full md:w-40">
        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500"
          >Level</label
        >
        <select
          v-model="filters.level"
          class="w-full h-12 rounded-[12px] border border-slate-300 px-3 text-sm"
        >
          <option value="">All</option>
          <option value="info">Info</option>
          <option value="warning">Warning</option>
          <option value="error">Error</option>
        </select>
      </div>
      <div class="w-full md:w-40">
        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500"
          >Action</label
        >
        <select
          v-model="filters.action"
          class="w-full h-12 rounded-[12px] border border-slate-300 px-3 text-sm"
        >
          <option value="">All</option>
          <option value="imported">Imported</option>
          <option value="updated">Updated</option>
          <option value="exported">Exported</option>
          <option value="skipped">Skipped</option>
          <option value="failed">Failed</option>
          <option value="conflict">Conflict</option>
        </select>
      </div>
      <div class="w-full md:w-56">
        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500"
          >Run UUID</label
        >
        <input
          v-model="filters.sync_run"
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
      <div v-else-if="!store.logs.length" class="px-6 py-12 text-center text-sm text-slate-500">
        No sync logs found.
      </div>
      <table v-else class="min-w-full divide-y divide-slate-200 text-sm">
        <thead class="bg-slate-50">
          <tr>
            <th class="px-4 py-3 text-left font-semibold text-slate-600">When</th>
            <th class="px-4 py-3 text-left font-semibold text-slate-600">Level</th>
            <th class="px-4 py-3 text-left font-semibold text-slate-600">Action</th>
            <th class="px-4 py-3 text-left font-semibold text-slate-600">Record</th>
            <th class="px-4 py-3 text-left font-semibold text-slate-600">Message</th>
            <th class="px-4 py-3 text-right font-semibold text-slate-600">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-for="item in store.logs" :key="item.uuid" class="hover:bg-slate-50/80">
            <td class="px-4 py-3 text-slate-600">{{ formatDate(item.created_at) }}</td>
            <td class="px-4 py-3 capitalize" :class="levelClass(item.level)">{{ item.level }}</td>
            <td class="px-4 py-3 capitalize text-slate-700">{{ item.action || '—' }}</td>
            <td class="px-4 py-3 font-mono text-xs text-slate-700">{{ item.record_key || '—' }}</td>
            <td class="px-4 py-3 text-slate-700">{{ item.message }}</td>
            <td class="px-4 py-3 text-right">
              <button
                type="button"
                class="rounded-md px-2 py-1 text-xs font-medium text-brand-700 hover:bg-brand-50"
                @click="selected = item"
              >
                View
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <Pagination :meta="store.logsMeta" :loading="store.loading" @change="onPage" />

    <div v-if="selected" class="mt-4 rounded-xl border border-slate-200 bg-white p-6">
      <div class="mb-3 flex items-center justify-between">
        <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Log detail</h3>
        <button
          type="button"
          class="text-xs text-slate-500 hover:text-slate-800"
          @click="selected = null"
        >
          Close
        </button>
      </div>
      <pre class="max-h-96 overflow-auto rounded-lg bg-slate-900 p-3 text-xs text-slate-100">{{
        JSON.stringify(selected, null, 2)
      }}</pre>
    </div>
  </div>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue';
import { useRoute } from 'vue-router';
// import PageHeader from '@/components/ui/PageHeader.vue';
import Pagination from '@/modules/users/components/Pagination.vue';
import SyncSubnav from '@/modules/sync/components/SyncSubnav.vue';
import { useSyncStore } from '@/modules/sync/stores/sync';

const route = useRoute();
const store = useSyncStore();
const selected = ref(null);
const filters = reactive({
  search: '',
  level: '',
  action: '',
  sync_run: route.query.sync_run || '',
  page: 1,
  per_page: 10,
});

onMounted(() => load());

function load() {
  selected.value = null;
  store.fetchLogs({ ...filters });
}

function onPage(page) {
  filters.page = page;
  load();
}

function formatDate(value) {
  return value ? new Date(value).toLocaleString() : '—';
}

function levelClass(level) {
  if (level === 'error') return 'text-rose-700';
  if (level === 'warning') return 'text-amber-700';
  return 'text-slate-700';
}
</script>
