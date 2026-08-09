<template>
  <div>
    <!-- <PageHeader
      title="Sync History"
      description="Review every synchronization run with progress and record outcomes."
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
      <div class="w-full md:w-40">
        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500"
          >Status</label
        >
        <select
          v-model="filters.status"
          class="w-full h-12 rounded-[12px] border border-slate-300 px-3 text-sm"
        >
          <option value="">All</option>
          <option value="queued">Queued</option>
          <option value="running">Running</option>
          <option value="completed">Completed</option>
          <option value="failed">Failed</option>
          <option value="cancelled">Cancelled</option>
        </select>
      </div>
      <div class="w-full md:w-40">
        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500"
          >Trigger</label
        >
        <select
          v-model="filters.trigger"
          class="w-full h-12 rounded-[12px] border border-slate-300 px-3 text-sm"
        >
          <option value="">All</option>
          <option value="manual">Manual</option>
          <option value="automatic">Automatic</option>
          <option value="scheduled">Scheduled</option>
          <option value="background">Background</option>
        </select>
      </div>
      <div class="w-full md:w-40">
        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500"
          >Mode</label
        >
        <select
          v-model="filters.mode"
          class="w-full h-12 rounded-[12px] border border-slate-300 px-3 text-sm"
        >
          <option value="">All</option>
          <option value="full">Full</option>
          <option value="incremental">Incremental</option>
        </select>
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
      <div v-else-if="!store.runs.length" class="px-6 py-12 text-center text-sm text-slate-500">
        No sync runs found.
      </div>
      <table v-else class="min-w-full divide-y divide-slate-200 text-sm">
        <thead class="bg-slate-50">
          <tr>
            <th class="px-4 py-3 text-left font-semibold text-slate-600">Started</th>
            <th class="px-4 py-3 text-left font-semibold text-slate-600">Config</th>
            <th class="px-4 py-3 text-left font-semibold text-slate-600">Progress</th>
            <th class="px-4 py-3 text-left font-semibold text-slate-600">Records</th>
            <th class="px-4 py-3 text-right font-semibold text-slate-600">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-for="item in store.runs" :key="item.uuid" class="hover:bg-slate-50/80">
            <td class="px-4 py-3 text-slate-600">
              <p>{{ formatDate(item.started_at || item.created_at) }}</p>
              <p class="text-xs capitalize text-slate-500">{{ item.trigger }} · {{ item.mode }}</p>
            </td>
            <td class="px-4 py-3">
              <p class="font-medium text-slate-900">{{ item.config?.name || '—' }}</p>
              <p class="text-xs capitalize text-slate-500">
                {{ item.status }} · {{ item.direction }}
              </p>
            </td>
            <td class="px-4 py-3 min-w-[10rem]">
              <SyncProgressBar :percent="item.progress_percent" :status="item.status" />
            </td>
            <td class="px-4 py-3 text-xs text-slate-600">
              T{{ item.total_records }} / I{{ item.imported }} / U{{ item.updated }} / F{{
                item.failed
              }}
              / S{{ item.skipped }}
            </td>
            <td class="px-4 py-3">
              <div class="flex justify-end gap-2">
                <button
                  type="button"
                  class="rounded-md px-2 py-1 text-xs font-medium text-brand-700 hover:bg-brand-50"
                  @click="selected = item"
                >
                  Details
                </button>
                <RouterLink
                  :to="{ name: 'sync.logs', query: { sync_run: item.uuid } }"
                  class="rounded-md px-2 py-1 text-xs font-medium text-slate-700 hover:bg-slate-100"
                  >Logs</RouterLink
                >
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <Pagination :meta="store.runsMeta" :loading="store.loading" @change="onPage" />

    <div v-if="selected" class="mt-4 rounded-xl border border-slate-200 bg-white p-6">
      <div class="mb-3 flex items-center justify-between">
        <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Run detail</h3>
        <button
          type="button"
          class="text-xs text-slate-500 hover:text-slate-800"
          @click="selected = null"
        >
          Close
        </button>
      </div>
      <dl class="mb-4 grid gap-3 sm:grid-cols-3 text-sm">
        <div>
          <dt class="text-slate-500">Started</dt>
          <dd>{{ formatDate(selected.started_at) }}</dd>
        </div>
        <div>
          <dt class="text-slate-500">Completed</dt>
          <dd>{{ formatDate(selected.completed_at) }}</dd>
        </div>
        <div>
          <dt class="text-slate-500">Failed</dt>
          <dd>{{ formatDate(selected.failed_at) }}</dd>
        </div>
      </dl>
      <pre class="max-h-96 overflow-auto rounded-lg bg-slate-900 p-3 text-xs text-slate-100">{{
        JSON.stringify(selected, null, 2)
      }}</pre>
    </div>
  </div>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue';
import { RouterLink } from 'vue-router';
// import PageHeader from '@/components/ui/PageHeader.vue';
import Pagination from '@/modules/users/components/Pagination.vue';
import SyncProgressBar from '@/modules/sync/components/SyncProgressBar.vue';
import SyncSubnav from '@/modules/sync/components/SyncSubnav.vue';
import { useSyncStore } from '@/modules/sync/stores/sync';

const store = useSyncStore();
const selected = ref(null);
const filters = reactive({ status: '', trigger: '', mode: '', page: 1, per_page: 10 });

onMounted(() => load());

function load() {
  selected.value = null;
  store.fetchRuns({ ...filters });
}

function onPage(page) {
  filters.page = page;
  load();
}

function formatDate(value) {
  return value ? new Date(value).toLocaleString() : '—';
}
</script>
